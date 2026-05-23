<?php

namespace Tests\Feature\Sales;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CashRegisterService;
use App\Services\InventoryService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SaleVoidTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_permission_can_void_sale_and_return_stock(): void
    {
        Permission::findOrCreate('sales.view');
        Permission::findOrCreate('sales.void');

        [$company, $user, $warehouse, $cashRegister] = $this->saleContext();
        $user->givePermissionTo(['sales.view', 'sales.void']);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $presentation = Presentation::factory()->create(['company_id' => $company->id, 'units_per_package' => 1]);
        $sale = $this->saleFor($cashRegister, $user, $warehouse, $product, $presentation, 4);

        $this->assertSame(6, (int) InventoryMovement::query()->where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->sum('quantity'));

        $this
            ->actingAs($user)
            ->post(route('sales.void', $sale), [
                'void_reason' => 'Cliente devolvio la compra',
            ])
            ->assertRedirect(route('sales.cash-registers.show', $cashRegister));

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'voided',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => InventoryMovementType::AdjustmentIn->value,
            'quantity' => 4,
            'package_quantity' => 4,
            'reference_type' => 'sale_void',
            'reference_id' => $sale->id,
        ]);
        $this->assertSame(10, (int) InventoryMovement::query()->where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->sum('quantity'));

        $summary = app(CashRegisterService::class)->cashSummary($cashRegister->refresh());
        $this->assertSame(0, $summary['sales_count']);
        $this->assertEquals(0.0, $summary['sales_total']);
    }

    public function test_user_without_void_permission_cannot_void_sale(): void
    {
        Permission::findOrCreate('sales.view');

        [$company, $user, $warehouse, $cashRegister] = $this->saleContext();
        $user->givePermissionTo('sales.view');
        $product = Product::factory()->create(['company_id' => $company->id]);
        $presentation = Presentation::factory()->create(['company_id' => $company->id, 'units_per_package' => 1]);
        $sale = $this->saleFor($cashRegister, $user, $warehouse, $product, $presentation, 2);

        $this
            ->actingAs($user)
            ->post(route('sales.void', $sale), [
                'void_reason' => 'Sin permiso',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'completed',
        ]);
    }

    public function test_company_user_cannot_void_other_company_sale(): void
    {
        Permission::findOrCreate('sales.view');
        Permission::findOrCreate('sales.void');

        [$company, $user] = $this->saleContext();
        [$otherCompany, $otherUser, $warehouse, $cashRegister] = $this->saleContext();
        $user->givePermissionTo(['sales.view', 'sales.void']);
        $otherUser->givePermissionTo(['sales.view', 'sales.void']);
        $product = Product::factory()->create(['company_id' => $otherCompany->id]);
        $presentation = Presentation::factory()->create(['company_id' => $otherCompany->id, 'units_per_package' => 1]);
        $sale = $this->saleFor($cashRegister, $otherUser, $warehouse, $product, $presentation, 2);

        $this
            ->actingAs($user)
            ->post(route('sales.void', $sale), [
                'void_reason' => 'Intento cruzado',
            ])
            ->assertForbidden();

        $this->assertSame($company->id, $user->company_id);
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'completed',
        ]);
    }

    /**
     * @return array{0: Company, 1: User, 2: Warehouse, 3: CashRegister}
     */
    private function saleContext(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $branch = Branch::factory()->for($company)->create();
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => $company->id]);
        $pointOfSale = PointOfSale::factory()->forWarehouse($warehouse->id)->create(['company_id' => $company->id]);
        $cashRegister = CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'opening_amount' => 0,
            'status' => 'open',
        ]);

        return [$company, $user, $warehouse, $cashRegister];
    }

    private function saleFor(CashRegister $cashRegister, User $user, Warehouse $warehouse, Product $product, Presentation $presentation, int $packages): Sale
    {
        app(InventoryService::class)->register([
            'operation' => 'in',
            'warehouse_id' => $warehouse->id,
            'items' => [
                ['product_id' => $product->id, 'presentation_id' => $presentation->id, 'package_quantity' => 10],
            ],
        ], $user->id);

        $cash = PaymentMethod::query()->create([
            'company_id' => $user->company_id,
            'name' => 'Efectivo',
            'is_active' => true,
        ]);

        return app(SaleService::class)->createFromPos([
            'payment_mode' => 'cash',
            'cash_payment_method_id' => $cash->id,
            'cash_received' => $packages * 10,
            'items' => [
                [
                    'product_id' => $product->id,
                    'presentation_id' => $presentation->id,
                    'package_quantity' => $packages,
                    'unit_price' => 10,
                    'discount' => 0,
                ],
            ],
        ], $cashRegister, $user);
    }
}
