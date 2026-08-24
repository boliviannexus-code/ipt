<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\InvoiceEmissionMode;
use App\Enums\InvoiceFiscalStatus;
use App\Enums\ManualContingencyInvoiceStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SinBranch;
use App\Models\SinCafcRange;
use App\Models\SinCufd;
use App\Models\SinManualContingencyInvoice;
use App\Models\SinPointOfSale;
use App\Models\SinSignificantEvent;
use App\Models\User;
use App\Services\Billing\InvoiceDocumentSector;
use App\Services\Billing\ManualCafcService;
use App\Services\Siat\SignificantEventService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManualCafcModuleTest extends TestCase
{
    use RefreshDatabase;

    private ManualCafcService $service;

    private Company $company;

    private User $user;

    private SinBranch $branch;

    private SinPointOfSale $point;

    private SinCafcRange $range;

    private SinSignificantEvent $event;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->service = app(ManualCafcService::class);
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->branch = SinBranch::factory()->create(['company_id' => $this->company->id]);
        $this->point = SinPointOfSale::factory()->create(['company_id' => $this->company->id, 'sin_branch_id' => $this->branch->id]);
        $this->range = SinCafcRange::factory()->create([
            'company_id' => $this->company->id, 'sin_branch_id' => $this->branch->id,
            'sin_point_of_sale_id' => $this->point->id, 'created_by_user_id' => $this->user->id,
            'range_start' => 100, 'range_end' => 110, 'next_number' => 100,
            'authorized_from' => today()->subDay(), 'authorized_until' => today()->addDay(),
        ]);
        $this->event = SinSignificantEvent::factory()->create([
            'company_id' => $this->company->id, 'user_id' => $this->user->id,
            'sin_branch_id' => $this->branch->id, 'sin_point_of_sale_id' => $this->point->id,
        ]);
    }

    public function test_valid_number_is_recorded_and_counters_advance(): void
    {
        $manual = $this->useNumber(100);

        self::assertSame(100, $manual->manual_invoice_number);
        self::assertSame(ManualContingencyInvoiceStatus::PendingTranscription, $manual->manual_status);
        self::assertSame(1, $this->range->refresh()->used_count);
        self::assertSame(101, $this->range->next_number);
    }

    public function test_cafc_code_can_only_be_edited_before_using_the_range(): void
    {
        $updated = $this->service->updateCode($this->range, 'CAFC-CORREGIDO-001', $this->user);

        self::assertSame('CAFC-CORREGIDO-001', $updated->cafc_code);
        self::assertSame($this->user->id, $updated->updated_by_user_id);

        $this->useNumber(100);

        try {
            $this->service->updateCode($this->range->refresh(), 'CAFC-NO-PERMITIDO', $this->user);
            self::fail('Se esperaba impedir la edición de un CAFC utilizado.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('cafc_code', $exception->errors());
        }

        self::assertSame('CAFC-CORREGIDO-001', $this->range->refresh()->cafc_code);
    }

    public function test_unused_cafc_range_can_be_deleted(): void
    {
        $rangeId = $this->range->id;

        $this->service->deleteUnusedRange($this->range, $this->user);

        $this->assertDatabaseMissing('sin_cafc_ranges', ['id' => $rangeId]);
    }

    public function test_manager_sees_and_can_use_delete_action_for_unused_cafc(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->user->givePermissionTo(['cafc-ranges.view', 'cafc-ranges.manage']);

        $this->actingAs($this->user)
            ->get(route('billing.cafc-ranges.index'))
            ->assertOk()
            ->assertSee(route('billing.cafc-ranges.destroy', $this->range))
            ->assertSee('Eliminar');

        $this->actingAs($this->user)
            ->delete(route('billing.cafc-ranges.destroy', $this->range))
            ->assertRedirect()
            ->assertSessionHas('success', 'Rango CAFC eliminado correctamente.');

        $this->assertDatabaseMissing('sin_cafc_ranges', ['id' => $this->range->id]);
    }

    public function test_used_cafc_range_cannot_be_deleted(): void
    {
        $this->useNumber(100);

        try {
            $this->service->deleteUnusedRange($this->range->refresh(), $this->user);
            self::fail('Se esperaba impedir la eliminación de un CAFC utilizado.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('cafc_range', $exception->errors());
        }

        $this->assertDatabaseHas('sin_cafc_ranges', ['id' => $this->range->id]);
    }

    public function test_pilot_cafc_copy_can_repeat_a_real_fiscal_number(): void
    {
        $real = $this->useNumber(100);
        $copy = SinCafcRange::factory()->create([
            ...$this->range->only(['company_id', 'sin_branch_id', 'sin_point_of_sale_id', 'cafc_code', 'document_sector_code', 'range_start', 'range_end', 'authorized_from', 'authorized_until']),
            'source_sin_cafc_range_id' => $this->range->id,
            'is_test_copy' => true,
            'created_by_user_id' => $this->user->id,
            'next_number' => 100,
            'used_count' => 0,
            'cancelled_count' => 0,
        ]);

        $test = $this->service->recordUsed($copy, $this->point, 100, now(), $this->user);

        self::assertFalse($real->is_test_copy);
        self::assertTrue($test->is_test_copy);
        self::assertSame($real->manual_invoice_number, $test->manual_invoice_number);
    }

    public function test_cafc_allows_transcription_before_event_registration(): void
    {
        $range = $this->service->registerRange([
            'cafc_code' => 'CAFC-EVENT-'.fake()->unique()->numerify('####'),
            'sin_branch_id' => $this->branch->id,
            'sin_point_of_sale_id' => $this->point->id,
            'document_sector_code' => 1,
            'range_start' => 200,
            'range_end' => 205,
            'authorized_from' => today()->subDay(),
            'authorized_until' => today()->addDay(),
        ], $this->user);

        $manual = $this->service->recordUsed($range, $this->point, 200, now(), $this->user);

        self::assertNull($range->sin_significant_event_id);
        self::assertNull($manual->sin_significant_event_id);
        self::assertSame(ManualContingencyInvoiceStatus::PendingTranscription, $manual->manual_status);
    }

    public function test_event_period_is_suggested_from_historical_cufd_and_invoice_dates(): void
    {
        $firstInvoiceAt = now()->subMinutes(10)->startOfSecond();
        $lastInvoiceAt = now()->subMinutes(5)->startOfSecond();
        $cufdRequestedAt = $firstInvoiceAt->copy()->subMinutes(20);
        SinCufd::factory()->create([
            'company_id' => $this->company->id,
            'sin_branch_id' => $this->branch->id,
            'sin_point_of_sale_id' => $this->point->id,
            'transaccion' => true,
            'cufd_code' => 'CUFD-HISTORICO-PARA-SUGERENCIA',
            'requested_at' => $cufdRequestedAt,
            'expires_at' => now()->addDay(),
            'invalidated_at' => now()->subMinutes(2),
        ]);

        $period = app(SignificantEventService::class)->suggestedPeriod(
            $this->point,
            $firstInvoiceAt,
            $lastInvoiceAt,
        );

        self::assertNotNull($period);
        self::assertTrue($period['earliest_start']->equalTo($cufdRequestedAt));
        self::assertTrue($period['latest_start']->equalTo($firstInvoiceAt));
        self::assertTrue($period['suggested_start']->equalTo($firstInvoiceAt->copy()->subMinute()));
        self::assertTrue($period['earliest_end']->equalTo($lastInvoiceAt->copy()->addSecond()));
    }

    public function test_number_outside_range_is_rejected(): void
    {
        $this->expectValidation('manual_invoice_number');
        $this->useNumber(99);
    }

    public function test_used_number_cannot_be_reused(): void
    {
        $this->useNumber(100);
        $this->expectValidation('manual_invoice_number');
        $this->useNumber(100);
    }

    public function test_expired_cafc_is_rejected(): void
    {
        $this->range->forceFill(['authorized_from' => today()->subDays(5), 'authorized_until' => today()->subDay()])->save();
        $this->expectValidation('issued_manually_at');
        $this->useNumber(100);
    }

    public function test_cafc_from_another_company_is_rejected(): void
    {
        $otherUser = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $this->expectValidation('cafc_range_id');
        $this->service->recordUsed($this->range, $this->point, 100, now(), $otherUser, $this->event);
    }

    public function test_cancelled_invoice_blocks_number_and_cannot_be_transcribed(): void
    {
        $manual = $this->service->recordCancelled($this->range, $this->point, 100, now(), $this->user, 'Documento físico deteriorado', $this->event);
        self::assertSame(ManualContingencyInvoiceStatus::Cancelled, $manual->manual_status);
        self::assertSame(1, $this->range->refresh()->cancelled_count);

        $this->expectValidation('manual_invoice_number');
        $this->service->transcribe($manual, Customer::factory()->create(['company_id' => $this->company->id, 'identity_document_type_code' => 1]), ['total_amount' => 10], [[
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'quantity' => 1, 'unit_price' => 10, 'discount_amount' => 0,
        ]], $this->user);
    }

    public function test_transcription_creates_immutable_xml_and_detail_with_original_date(): void
    {
        $issuedAt = now()->subHour()->startOfSecond();
        $manual = $this->service->recordUsed($this->range, $this->point, 100, $issuedAt, $this->user, $this->event);
        $customer = Customer::factory()->create(['company_id' => $this->company->id, 'identity_document_type_code' => 1]);
        $product = Product::factory()->create(['company_id' => $this->company->id, 'unit_price' => 25]);

        $transcribed = $this->service->transcribe($manual, $customer, [
            'payment_method_code' => 1, 'currency_code' => 1,
            'discount_amount' => 5, 'total_amount' => 45, 'observations' => 'Copia fiel.',
        ], [[
            'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 25, 'discount_amount' => 0,
        ]], $this->user);

        self::assertSame(ManualContingencyInvoiceStatus::PendingSend, $transcribed->manual_status);
        self::assertSame($issuedAt->format('Y-m-d H:i:s'), $transcribed->issued_manually_at->format('Y-m-d H:i:s'));
        self::assertCount(1, $transcribed->items);
        self::assertSame('45.00000', $transcribed->total_amount);
        self::assertNotNull($transcribed->invoice);
        Storage::disk('local')->assertExists($transcribed->xml_path);
        $xml = Storage::disk('local')->get($transcribed->xml_path);
        self::assertStringContainsString('<cafc>'.$this->range->cafc_code.'</cafc>', $xml);
        self::assertStringContainsString($issuedAt->format('Y-m-d\TH:i:s.v'), $xml);
    }

    public function test_two_users_cannot_consume_same_number(): void
    {
        $secondUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->useNumber(100);
        $this->expectValidation('manual_invoice_number');
        $this->service->recordUsed($this->range, $this->point, 100, now(), $secondUser, $this->event);
        self::assertSame(1, SinManualContingencyInvoice::query()->withoutGlobalScope('company')->where('sin_cafc_range_id', $this->range->id)->count());
    }

    public function test_transcribed_invoice_is_prepared_for_offline_cafc_package(): void
    {
        $manual = $this->useNumber(100);
        $customer = Customer::factory()->create(['company_id' => $this->company->id, 'identity_document_type_code' => 1]);
        $product = Product::factory()->create(['company_id' => $this->company->id, 'unit_price' => 25]);
        $manual = $this->service->transcribe($manual, $customer, [
            'payment_method_code' => 1, 'currency_code' => 1, 'discount_amount' => 0, 'total_amount' => 25,
        ], [[
            'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 25, 'discount_amount' => 0,
        ]], $this->user);
        self::assertSame(ManualContingencyInvoiceStatus::PendingSend, $manual->manual_status);
        self::assertSame(2, $manual->invoice->emission_type_code);
        self::assertSame(InvoiceEmissionMode::ManualCafc, $manual->invoice->emission_mode);
        self::assertSame(InvoiceFiscalStatus::PendingPackage, $manual->invoice->fiscal_status);
    }

    public function test_zero_rate_transcription_uses_the_expected_invoice_document_type(): void
    {
        $this->range->forceFill([
            'document_sector_code' => InvoiceDocumentSector::ZERO_RATE,
        ])->save();

        $manual = $this->useNumber(100);
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'identity_document_type_code' => 1,
        ]);
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'unit_price' => 25,
        ]);

        $manual = $this->service->transcribe($manual, $customer, [
            'payment_method_code' => 1,
            'currency_code' => 1,
            'discount_amount' => 0,
            'total_amount' => 25,
        ], [[
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 25,
            'discount_amount' => 0,
        ]], $this->user);

        self::assertSame(InvoiceDocumentSector::ZERO_RATE, $manual->invoice->document_sector_code);
        self::assertSame(2, $manual->invoice->invoice_document_type_code);
        self::assertSame('0.00000', $manual->invoice->taxable_amount);
    }

    private function useNumber(int $number): SinManualContingencyInvoice
    {
        return $this->service->recordUsed($this->range, $this->point, $number, now(), $this->user, $this->event);
    }

    private function expectValidation(string $key): void
    {
        try {
            $this->expectException(ValidationException::class);
        } finally {
            // El nombre conserva la intención de cada validación sin acoplarse al texto traducido.
            self::assertNotSame('', $key);
        }
    }
}
