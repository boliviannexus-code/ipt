<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\CafcRangeStatus;
use App\Enums\InvoiceFiscalStatus;
use App\Enums\InvoiceIssuanceDecision;
use App\Enums\SiatAttemptStatus;
use App\Enums\SiatErrorType;
use App\Jobs\SynchronizeOfflineInvoiceJob;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SinApiToken;
use App\Models\SinAuthorization;
use App\Models\SinBranch;
use App\Models\SinCafcRange;
use App\Models\SinCatalogItem;
use App\Models\SinCufd;
use App\Models\SinCuis;
use App\Models\SinPointOfSale;
use App\Models\User;
use App\Services\Billing\Contracts\InvoiceSiatClient;
use App\Services\Billing\InvoiceIssuanceService;
use App\Services\Billing\InvoiceSiatResponse;
use App\Services\Billing\InvoiceTransportException;
use App\Services\Siat\SiatCommunicationService;
use App\Services\Siat\SiatHealthCheckResult;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\Fakes\SequenceInvoiceSiatClient;
use Tests\TestCase;

final class InvoiceIssuanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    public function test_normal_invoice_is_validated_and_confirms_commercial_effects_once(): void
    {
        $context = $this->context();
        $client = $this->simulate(true, [
            $this->response(908, true, 'RECEPTION-908'),
        ]);

        $result = app(InvoiceIssuanceService::class)->issue($context['sale']);

        self::assertSame(InvoiceIssuanceDecision::Online, $result->decision);
        self::assertSame(InvoiceFiscalStatus::Validated, $result->invoice?->fiscal_status);
        self::assertSame('RECEPTION-908', $result->invoice?->reception_code);
        self::assertNotNull($result->invoice?->invoice_number);
        self::assertSame(1, $client->calls);
        self::assertNotNull($context['sale']->refresh()->inventory_applied_at);
        self::assertNotNull($context['sale']->refresh()->payment_registered_at);
        $this->assertDatabaseHas('sin_siat_attempts', ['attempt_status' => SiatAttemptStatus::Succeeded->value]);
        $this->assertDatabaseHas('sin_response_messages', ['message_code' => '9080']);
        Storage::disk('local')->assertExists((string) $result->invoice?->xml_path);
    }

    public function test_observed_invoice_preserves_number_cuf_and_messages(): void
    {
        $context = $this->context();
        $this->simulate(true, [$this->response(904, false, 'RECEPTION-904', 'Factura con observaciones.')]);

        $invoice = app(InvoiceIssuanceService::class)->issue($context['sale'])->invoice;

        self::assertSame(InvoiceFiscalStatus::Observed, $invoice?->fiscal_status);
        self::assertNotNull($invoice?->invoice_number);
        self::assertNotNull($invoice?->cuf);
        $this->assertDatabaseHas('sin_response_messages', [
            'message_code' => '9040',
            'description' => 'Factura con observaciones.',
        ]);
    }

    public function test_rejected_invoice_is_not_recreated_and_keeps_fiscal_identity(): void
    {
        $context = $this->context();
        $client = $this->simulate(true, [$this->response(902, false, null, 'XML rechazado.')]);
        $service = app(InvoiceIssuanceService::class);

        $first = $service->issue($context['sale'])->invoice;
        $second = $service->issue($context['sale'])->invoice;

        self::assertSame(InvoiceFiscalStatus::Rejected, $first?->fiscal_status);
        self::assertSame($first?->id, $second?->id);
        self::assertSame($first?->invoice_number, $second?->invoice_number);
        self::assertSame($first?->cuf, $second?->cuf);
        self::assertSame(1, $client->calls);
        $this->assertDatabaseCount('sin_invoice_issues', 1);
    }

    public function test_timeout_before_send_keeps_same_invoice_pending_without_automatic_resend(): void
    {
        $context = $this->context();
        $client = $this->simulate(true, [
            new InvoiceTransportException('Timeout al crear el transporte.', false, SiatErrorType::Timeout),
        ]);
        $service = app(InvoiceIssuanceService::class);

        $first = $service->issue($context['sale'])->invoice;
        $second = $service->issue($context['sale'])->invoice;

        self::assertSame(InvoiceFiscalStatus::PendingOnlineSend, $first?->fiscal_status);
        self::assertNull($first?->sent_at);
        self::assertSame($first?->id, $second?->id);
        self::assertSame(1, $client->calls);
        $this->assertDatabaseHas('sin_siat_attempts', ['attempt_status' => SiatAttemptStatus::Failed->value]);
    }

    public function test_timeout_after_send_is_uncertain_and_never_creates_or_sends_another_invoice(): void
    {
        $context = $this->context();
        $client = $this->simulate(true, [
            new InvoiceTransportException('Timeout esperando respuesta.', true, SiatErrorType::Timeout),
        ]);
        $service = app(InvoiceIssuanceService::class);

        $first = $service->issue($context['sale'])->invoice;
        $second = $service->issue($context['sale'])->invoice;

        self::assertSame(InvoiceFiscalStatus::UncertainSend, $first?->fiscal_status);
        self::assertNotNull($first?->sent_at);
        self::assertSame($first?->id, $second?->id);
        self::assertSame(1, $client->calls);
        $this->assertDatabaseCount('sin_invoice_issues', 1);
        $this->assertDatabaseHas('sin_siat_attempts', ['attempt_status' => SiatAttemptStatus::Uncertain->value]);
    }

    public function test_offline_invoice_reuses_real_issued_at_stores_immutable_artifacts_and_queues_sync(): void
    {
        $context = $this->context();
        $issuedAt = $context['sale']->issued_at;
        $client = $this->simulate(false, [], contingency: true, errorType: SiatErrorType::NoInternet);

        $result = app(InvoiceIssuanceService::class)->issue($context['sale']);
        $invoice = $result->invoice;

        self::assertSame(InvoiceIssuanceDecision::OfflineDigital, $result->decision);
        self::assertSame(InvoiceFiscalStatus::OfflineIssued, $invoice?->fiscal_status);
        self::assertSame($issuedAt->format('Y-m-d H:i:s'), $invoice?->issued_at?->format('Y-m-d H:i:s'));
        self::assertNotNull($invoice?->sin_significant_event_id);
        self::assertNotNull($invoice?->pdf_hash);
        self::assertSame(0, $client->calls);
        Storage::disk('local')->assertExists((string) $invoice?->xml_path);
        Storage::disk('local')->assertExists((string) $invoice?->pdf_path);
        Queue::assertPushed(SynchronizeOfflineInvoiceJob::class, fn ($job): bool => $job->invoiceId === $invoice?->id);
    }

    public function test_all_offline_invoices_in_an_open_event_reuse_the_event_cufd(): void
    {
        $context = $this->context();
        $this->simulate(false, [], contingency: true, errorType: SiatErrorType::NoInternet);
        $service = app(InvoiceIssuanceService::class);
        $firstInvoice = $service->issue($context['sale'])->invoice;

        SinCufd::factory()->create([
            'company_id' => $context['company']->id,
            'sin_authorization_id' => $context['authorization']->id,
            'sin_branch_id' => $context['branch']->id,
            'sin_point_of_sale_id' => $context['point']->id,
            'sin_cuis_id' => $context['cuis']->id,
            'control_code' => 'CONTROL-NUEVO-QUE-NO-DEBE-USARSE',
            'requested_at' => now()->addMinute(),
            'expires_at' => now()->addDay(),
            'transaccion' => true,
        ]);
        $nextSale = Sale::factory()->create([
            'company_id' => $context['company']->id,
            'user_id' => $context['user']->id,
            'customer_id' => $context['customer']->id,
            'sin_point_of_sale_id' => $context['point']->id,
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'issued_at' => now()->startOfSecond(),
        ]);
        $nextSale->items()->create($context['sale']->items()->firstOrFail()->only([
            'company_id', 'product_id', 'position', 'internal_code', 'description',
            'economic_activity_code', 'siat_product_code', 'measurement_unit_code',
            'quantity', 'unit_price', 'discount_amount', 'subtotal_amount',
        ]));

        $secondInvoice = $service->issue($nextSale)->invoice;

        self::assertSame($firstInvoice?->sin_cufd_id, $secondInvoice?->sin_cufd_id);
        self::assertSame($firstInvoice?->cufd_code, $secondInvoice?->cufd_code);
    }

    public function test_recovered_connection_blocks_new_invoices_until_offline_invoices_are_processed(): void
    {
        $context = $this->context();
        $this->simulate(false, [], contingency: true, errorType: SiatErrorType::SiatUnavailable);
        $offline = app(InvoiceIssuanceService::class)->issue($context['sale'])->invoice;

        $nextSale = Sale::factory()->create([
            'company_id' => $context['company']->id,
            'user_id' => $context['user']->id,
            'customer_id' => $context['customer']->id,
            'sin_point_of_sale_id' => $context['point']->id,
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'issued_at' => now()->startOfSecond(),
        ]);
        $originalItem = $context['sale']->items()->firstOrFail();
        $nextSale->items()->create($originalItem->only([
            'company_id', 'product_id', 'position', 'internal_code', 'description',
            'economic_activity_code', 'siat_product_code', 'measurement_unit_code',
            'quantity', 'unit_price', 'discount_amount', 'subtotal_amount',
        ]));
        $client = $this->simulate(true, [$this->response(908, true, 'SHOULD-NOT-SEND')]);

        $blocked = app(InvoiceIssuanceService::class)->issue($nextSale);

        self::assertSame(InvoiceIssuanceDecision::Blocked, $blocked->decision);
        self::assertNull($blocked->invoice);
        self::assertStringContainsString('procesar todas las facturas emitidas fuera de linea', $blocked->message);
        self::assertSame(0, $client->calls);
        self::assertSame(InvoiceFiscalStatus::OfflineIssued, $offline?->refresh()->fiscal_status);
    }

    public function test_missing_cufd_blocks_online_issuance(): void
    {
        $context = $this->context(withCufd: false);
        $this->simulate(true, []);

        $result = app(InvoiceIssuanceService::class)->issue($context['sale']);

        self::assertSame(InvoiceIssuanceDecision::Blocked, $result->decision);
        self::assertNull($result->invoice);
        $this->assertDatabaseCount('sin_invoice_issues', 0);
    }

    public function test_missing_cufd_during_outage_requires_manual_cafc_when_range_exists(): void
    {
        $context = $this->context(withCufd: false);
        SinCafcRange::factory()->create([
            'company_id' => $context['company']->id,
            'sin_branch_id' => $context['point']->sin_branch_id,
            'sin_point_of_sale_id' => $context['point']->id,
            'document_sector_code' => 1,
            'range_status' => CafcRangeStatus::Available,
            'authorized_from' => now()->subDay(),
            'authorized_until' => now()->addDay(),
        ]);
        $this->simulate(false, [], contingency: true, errorType: SiatErrorType::SiatUnavailable);

        $result = app(InvoiceIssuanceService::class)->issue($context['sale']);

        self::assertSame(InvoiceIssuanceDecision::ManualCafcRequired, $result->decision);
        self::assertNull($result->invoice);
    }

    public function test_already_invoiced_sale_returns_existing_invoice_without_reapplying_effects(): void
    {
        $context = $this->context();
        $client = $this->simulate(true, [$this->response(908, true, 'ONLY-ONCE')]);
        $service = app(InvoiceIssuanceService::class);
        $first = $service->issue($context['sale'])->invoice;
        $inventoryAppliedAt = $context['sale']->refresh()->inventory_applied_at;
        $paymentRegisteredAt = $context['sale']->payment_registered_at;

        $second = $service->issue($context['sale'])->invoice;

        self::assertSame($first?->id, $second?->id);
        self::assertTrue($inventoryAppliedAt?->equalTo($context['sale']->refresh()->inventory_applied_at));
        self::assertTrue($paymentRegisteredAt?->equalTo($context['sale']->payment_registered_at));
        self::assertSame(1, $client->calls);
    }

    public function test_two_competing_requests_for_same_sale_obtain_one_invoice_and_one_number(): void
    {
        $context = $this->context();
        $client = $this->simulate(true, [$this->response(908, true, 'CONCURRENT-1')]);
        $service = app(InvoiceIssuanceService::class);

        $results = [$service->issue($context['sale']), $service->issue($context['sale'])];

        self::assertSame($results[0]->invoice?->id, $results[1]->invoice?->id);
        self::assertSame(1, $results[0]->invoice?->invoice_number);
        self::assertSame(1, $client->calls);
        $this->assertDatabaseCount('sin_invoice_issues', 1);
        $this->assertDatabaseHas('sin_invoice_sequences', ['next_number' => 2]);
    }

    public function test_invoice_sequences_and_records_are_separated_between_companies(): void
    {
        $first = $this->context();
        $second = $this->context();
        $this->simulate(true, [
            $this->response(908, true, 'COMPANY-A'),
            $this->response(908, true, 'COMPANY-B'),
        ]);
        $service = app(InvoiceIssuanceService::class);

        $invoiceA = $service->issue($first['sale'])->invoice;
        $invoiceB = $service->issue($second['sale'])->invoice;

        self::assertNotSame($invoiceA?->company_id, $invoiceB?->company_id);
        self::assertSame(1, $invoiceA?->invoice_number);
        self::assertSame(1, $invoiceB?->invoice_number);
        $this->assertDatabaseHas('sin_invoice_issues', ['company_id' => $first['company']->id, 'sale_id' => $first['sale']->id]);
        $this->assertDatabaseHas('sin_invoice_issues', ['company_id' => $second['company']->id, 'sale_id' => $second['sale']->id]);
    }

    public function test_database_rejects_changes_to_issued_cuf_number_or_xml_identity(): void
    {
        $context = $this->context();
        $this->simulate(true, [$this->response(908, true, 'IMMUTABLE-1')]);
        $invoice = app(InvoiceIssuanceService::class)->issue($context['sale'])->invoice;

        $this->expectException(QueryException::class);
        $invoice?->forceFill([
            'cuf' => 'CUF-MODIFICADO',
            'invoice_number' => 999,
            'xml_path' => 'otra-ruta.xml',
        ])->save();
    }

    public function test_observed_invoice_can_correct_payment_and_resend_same_cuf(): void
    {
        $context = $this->context();
        SinCatalogItem::factory()->create([
            'company_id' => $context['company']->id, 'catalog_key' => 'tipos_metodo_pago',
            'classifier_code' => '2', 'description' => 'TARJETA', 'is_active' => true,
        ]);
        $this->simulate(true, [$this->response(904, false, 'OBS-1', 'Método de pago observado.')]);
        $observed = app(InvoiceIssuanceService::class)->issue($context['sale'])->invoice;
        $originalCuf = $observed?->cuf;
        $originalNumber = $observed?->invoice_number;
        $this->app->instance(InvoiceSiatClient::class, new SequenceInvoiceSiatClient([$this->response(908, true, 'VALID-2')]));

        $result = app(InvoiceIssuanceService::class)->correctPaymentAndResend(
            $observed, 2, '4797123412347896', $context['user']
        );

        self::assertSame(InvoiceFiscalStatus::Validated, $result->invoice?->fiscal_status);
        self::assertSame($originalCuf, $result->invoice?->cuf);
        self::assertSame($originalNumber, $result->invoice?->invoice_number);
        self::assertSame('4797000000007896', $context['sale']->refresh()->masked_card_number);
        $retryXmlPath = $result->invoice->attempts()->where('attempt_number', 2)->firstOrFail()->request_payload['xml_path'];
        self::assertStringContainsString('<numeroTarjeta>4797000000007896</numeroTarjeta>', Storage::disk('local')->get($retryXmlPath));
        $this->assertDatabaseHas('sin_siat_attempts', ['sin_invoice_issue_id' => $observed?->id, 'attempt_number' => 2]);
    }

    /** @return array<string, mixed> */
    private function context(bool $withCufd = true): array
    {
        $company = Company::factory()->create(['is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'is_active' => true, 'identity_document_type_code' => 1]);
        $branch = SinBranch::factory()->create(['company_id' => $company->id, 'branch_code' => 1, 'is_active' => true]);
        $point = SinPointOfSale::factory()->create([
            'company_id' => $company->id,
            'sin_branch_id' => $branch->id,
            'point_of_sale_code' => 1,
            'is_active' => true,
        ]);
        $token = SinApiToken::factory()->create(['company_id' => $company->id]);
        $authorization = SinAuthorization::factory()->create(['company_id' => $company->id, 'tax_id' => '123456789']);
        $cuis = SinCuis::factory()->create([
            'company_id' => $company->id,
            'sin_api_token_id' => $token->id,
            'sin_authorization_id' => $authorization->id,
            'sin_branch_id' => $branch->id,
            'sin_point_of_sale_id' => $point->id,
            'branch_code' => 1,
            'point_of_sale_code' => 1,
            'transaccion' => true,
        ]);
        $cufd = $withCufd ? SinCufd::factory()->create([
            'company_id' => $company->id,
            'sin_api_token_id' => $token->id,
            'sin_authorization_id' => $authorization->id,
            'sin_branch_id' => $branch->id,
            'sin_point_of_sale_id' => $point->id,
            'sin_cuis_id' => $cuis->id,
            'branch_code' => 1,
            'point_of_sale_code' => 1,
            'transaccion' => true,
            'expires_at' => now()->addDay(),
        ]) : null;
        $product = Product::factory()->create(['company_id' => $company->id]);
        SinCatalogItem::factory()->create([
            'company_id' => $company->id,
            'catalog_key' => 'tipos_metodo_pago',
            'classifier_code' => '1',
            'description' => 'EFECTIVO',
            'is_active' => true,
        ]);
        $sale = Sale::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'sin_point_of_sale_id' => $point->id,
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'issued_at' => now()->startOfSecond(),
        ]);
        $sale->items()->create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'position' => 1,
            'internal_code' => $product->internal_code,
            'description' => $product->description,
            'economic_activity_code' => $product->economic_activity_code,
            'siat_product_code' => $product->siat_product_code,
            'measurement_unit_code' => $product->measurement_unit_code,
            'quantity' => 1,
            'unit_price' => 100,
            'discount_amount' => 0,
            'subtotal_amount' => 100,
        ]);

        return compact('company', 'user', 'customer', 'branch', 'point', 'token', 'authorization', 'cuis', 'cufd', 'sale');
    }

    /** @param array<int, InvoiceSiatResponse|\Throwable> $responses */
    private function simulate(
        bool $available,
        array $responses,
        bool $contingency = false,
        SiatErrorType $errorType = SiatErrorType::Available,
    ): SequenceInvoiceSiatClient {
        $health = new SiatHealthCheckResult(
            available: $available,
            errorType: $errorType,
            userMessage: $available ? 'Disponible.' : 'Sin comunicacion.',
            technicalMessage: 'Respuesta simulada.',
            operation: 'verificarComunicacion',
            wsdlUrl: 'https://siat.test/wsdl',
            durationMs: 1,
            requestDurationMs: 1,
            attempts: $contingency ? 3 : 1,
            shouldOpenContingency: $contingency,
            checkedAt: now()->format('d/m/Y H:i:s'),
        );
        $this->mock(SiatCommunicationService::class, function (MockInterface $mock) use ($health): void {
            $mock->shouldReceive('verify')->andReturn($health);
        });
        $client = new SequenceInvoiceSiatClient($responses);
        $this->app->instance(InvoiceSiatClient::class, $client);

        return $client;
    }

    private function response(int $code, bool $transaction, ?string $reception, string $message = 'Procesada.'): InvoiceSiatResponse
    {
        return new InvoiceSiatResponse([
            'RespuestaServicioFacturacion' => [
                'codigoEstado' => $code,
                'codigoRecepcion' => $reception,
                'transaccion' => $transaction,
                'mensajesList' => [[
                    'codigo' => (string) $code.'0',
                    'descripcion' => $message,
                ]],
            ],
        ], 25);
    }
}
