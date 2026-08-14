<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\InvoiceFiscalStatus;
use App\Enums\InvoiceTestItemStatus;
use App\Enums\SiatEnvironment;
use App\Jobs\CancelInvoiceTestItemJob;
use App\Jobs\IssueInvoiceTestItemJob;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InvoiceTestBatch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SinAuthorization;
use App\Models\SinBranch;
use App\Models\SinCatalogItem;
use App\Models\SinInvoiceIssue;
use App\Models\SinPointOfSale;
use App\Models\User;
use App\Services\Billing\InvoiceIssuanceService;
use App\Services\Billing\InvoiceTestBatchService;
use App\Services\Billing\SaleCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class InvoiceTestBatchTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private SinPointOfSale $point;

    private SinBranch $branch;

    private Customer $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->branch = SinBranch::factory()->create(['company_id' => $this->company->id]);
        $this->point = SinPointOfSale::factory()->create([
            'company_id' => $this->company->id,
            'sin_branch_id' => $this->branch->id,
        ]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'economic_activity_code' => 620100,
            'unit_price' => 10,
        ]);
        SinAuthorization::factory()->create([
            'company_id' => $this->company->id,
            'environment_code' => SiatEnvironment::TestingAndPilot,
        ]);
        SinCatalogItem::factory()->create([
            'company_id' => $this->company->id,
            'catalog_key' => 'motivos_anulacion',
            'classifier_code' => '1',
            'is_active' => true,
        ]);
        SinCatalogItem::factory()->create([
            'company_id' => $this->company->id,
            'catalog_key' => 'actividades',
            'classifier_code' => '620100',
            'is_active' => true,
        ]);
        SinCatalogItem::factory()->create([
            'company_id' => $this->company->id,
            'catalog_key' => 'tipos_metodo_pago',
            'classifier_code' => '1',
            'is_active' => true,
        ]);
        SinCatalogItem::factory()->create([
            'company_id' => $this->company->id,
            'catalog_key' => 'tipos_moneda',
            'classifier_code' => '1',
            'is_active' => true,
        ]);
        Permission::findOrCreate('invoice-tests.run');
        $this->user->givePermissionTo('invoice-tests.run');
    }

    public function test_pilot_user_can_create_a_chain_of_twenty_five_sequential_invoices(): void
    {
        Bus::fake();

        $this->actingAs($this->user)
            ->get(route('billing.invoice-tests.index'))
            ->assertOk()
            ->assertSee('Cola secuencial de emisión')
            ->assertSee('25');

        $this->actingAs($this->user)
            ->post(route('billing.invoice-tests.store'), $this->payload(25))
            ->assertRedirect(route('billing.invoice-tests.index', ['batch' => 1]));

        $batch = InvoiceTestBatch::query()->with('items')->firstOrFail();
        self::assertSame(25, $batch->requested_count);
        self::assertCount(25, $batch->items);
        self::assertSame(range(1, 25), $batch->items->pluck('position')->all());
        self::assertCount(25, $batch->items->pluck('issuance_key')->unique());
        Bus::assertChained(array_fill(0, 25, IssueInvoiceTestItemJob::class));
    }

    public function test_batch_cannot_exceed_twenty_five_invoices(): void
    {
        Bus::fake();

        $this->actingAs($this->user)
            ->from(route('billing.invoice-tests.index'))
            ->post(route('billing.invoice-tests.store'), $this->payload(26))
            ->assertRedirect(route('billing.invoice-tests.index'))
            ->assertSessionHasErrors('invoice_count');

        self::assertSame(0, InvoiceTestBatch::query()->count());
        Bus::assertNothingDispatched();
    }

    public function test_point_of_sale_must_belong_to_selected_branch(): void
    {
        Bus::fake();
        $otherBranch = SinBranch::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->post(route('billing.invoice-tests.store'), [...$this->payload(1), 'sin_branch_id' => $otherBranch->id])
            ->assertSessionHasErrors('sin_point_of_sale_id');

        self::assertSame(0, InvoiceTestBatch::query()->count());
    }

    public function test_product_must_belong_to_selected_activity(): void
    {
        Bus::fake();
        SinCatalogItem::factory()->create([
            'company_id' => $this->company->id,
            'catalog_key' => 'actividades',
            'classifier_code' => '471100',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('billing.invoice-tests.store'), [...$this->payload(1), 'economic_activity_code' => 471100])
            ->assertSessionHasErrors('product_id');

        self::assertSame(0, InvoiceTestBatch::query()->count());
    }

    public function test_batch_is_blocked_outside_the_siat_pilot_environment(): void
    {
        Bus::fake();
        SinAuthorization::query()->update(['environment_code' => SiatEnvironment::Production]);

        $this->actingAs($this->user)
            ->from(route('billing.invoice-tests.index'))
            ->post(route('billing.invoice-tests.store'), $this->payload(5))
            ->assertRedirect(route('billing.invoice-tests.index'))
            ->assertSessionHasErrors('environment');

        self::assertSame(0, InvoiceTestBatch::query()->count());
    }

    public function test_user_without_permission_cannot_open_or_start_tests(): void
    {
        $other = User::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($other)->get(route('billing.invoice-tests.index'))->assertForbidden();
        $this->actingAs($other)->post(route('billing.invoice-tests.store'), $this->payload(5))->assertForbidden();
    }

    public function test_completed_position_is_idempotent_when_the_job_is_delivered_again(): void
    {
        $batch = app(InvoiceTestBatchService::class)->create($this->user, $this->payload(1));
        $item = $batch->items->firstOrFail();
        $item->update(['item_status' => InvoiceTestItemStatus::Succeeded, 'finished_at' => now()]);

        (new IssueInvoiceTestItemJob($this->company->id, $batch->id, $item->id))->handle(
            app(InvoiceTestBatchService::class),
            app(SaleCreationService::class),
            app(InvoiceIssuanceService::class),
        );

        self::assertSame(0, Sale::query()->count());
        self::assertSame(InvoiceTestItemStatus::Succeeded, $item->refresh()->item_status);
    }

    public function test_validated_invoices_can_be_queued_for_sequential_cancellation(): void
    {
        Bus::fake();
        $batch = app(InvoiceTestBatchService::class)->create($this->user, $this->payload(2));

        foreach ($batch->items as $position => $item) {
            $invoice = SinInvoiceIssue::factory()->create([
                'company_id' => $this->company->id,
                'user_id' => $this->user->id,
                'customer_id' => $this->customer->id,
                'sin_branch_id' => $this->branch->id,
                'sin_point_of_sale_id' => $this->point->id,
                'fiscal_status' => InvoiceFiscalStatus::Validated,
                'invoice_number' => $position + 1,
                'status_code' => 908,
                'transaccion' => true,
            ]);
            $item->update(['item_status' => InvoiceTestItemStatus::Succeeded, 'sin_invoice_issue_id' => $invoice->id]);
        }

        $this->actingAs($this->user)
            ->post(route('billing.invoice-tests.cancel', $batch), ['reason_code' => 1])
            ->assertRedirect(route('billing.invoice-tests.index', ['batch' => $batch->id]));

        $batch->refresh();
        self::assertSame(2, $batch->cancellation_requested_count);
        self::assertSame(1, $batch->cancellation_reason_code);
        self::assertTrue($batch->items()->get()->every(fn ($item) => $item->cancellation_status === InvoiceTestItemStatus::Pending));
        Bus::assertChained([CancelInvoiceTestItemJob::class, CancelInvoiceTestItemJob::class]);
    }

    /** @return array<string, int|float> */
    private function payload(int $count): array
    {
        return [
            'sin_branch_id' => $this->branch->id,
            'sin_point_of_sale_id' => $this->point->id,
            'economic_activity_code' => 620100,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'payment_method_code' => 1,
            'currency_code' => 1,
            'quantity' => 1,
            'unit_price' => 10,
            'invoice_count' => $count,
        ];
    }
}
