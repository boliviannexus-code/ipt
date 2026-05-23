<?php

namespace Tests\Feature\Reports;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashRegisterExpense;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BasicReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_permission_can_view_company_scoped_reports(): void
    {
        Permission::findOrCreate('reports.view');

        [$company, $user, $warehouse, $pointOfSale, $cashRegister] = $this->context();
        [$otherCompany, $otherUser, $otherWarehouse, $otherPointOfSale, $otherCashRegister] = $this->context();
        $user->givePermissionTo('reports.view');
        $otherUser->givePermissionTo('reports.view');

        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Producto propio']);
        $otherProduct = Product::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Producto ajeno']);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Proveedor propio']);

        $sale = Sale::query()->create([
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'cash_register_id' => $cashRegister->id,
            'point_of_sale_id' => $pointOfSale->id,
            'receipt_number' => 'OWN-000001',
            'sequence_number' => 1,
            'sale_date' => now(),
            'subtotal' => 25,
            'discount' => 0,
            'tax' => 0,
            'total' => 25,
            'status' => 'completed',
        ]);
        $sale->details()->create([
            'product_id' => $product->id,
            'presentation_name' => 'Unidad',
            'package_quantity' => 5,
            'units_per_package' => 1,
            'quantity' => 5,
            'unit_price' => 5,
            'discount' => 0,
            'subtotal' => 25,
        ]);
        $purchase = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'reference' => 'COMPRA-OWN',
            'sequence_number' => 1,
            'purchase_date' => now()->toDateString(),
            'subtotal' => 10,
            'tax' => 0,
            'total' => 10,
            'status' => 'completed',
        ]);
        $purchase->details()->create([
            'product_id' => $product->id,
            'presentation_name' => 'Unidad',
            'package_quantity' => 10,
            'units_per_package' => 1,
            'quantity' => 10,
            'unit_price' => 1,
            'subtotal' => 10,
        ]);
        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 10,
            'package_quantity' => 10,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);
        CashRegisterExpense::query()->create([
            'cash_register_id' => $cashRegister->id,
            'point_of_sale_id' => $pointOfSale->id,
            'user_id' => $user->id,
            'responsible_name' => 'Caja propia',
            'detail' => 'Gasto propio',
            'amount' => 3,
            'spent_at' => now(),
        ]);

        Sale::query()->create([
            'branch_id' => $otherWarehouse->branch_id,
            'warehouse_id' => $otherWarehouse->id,
            'user_id' => $otherUser->id,
            'cash_register_id' => $otherCashRegister->id,
            'point_of_sale_id' => $otherPointOfSale->id,
            'receipt_number' => 'OTHER-000001',
            'sequence_number' => 1,
            'sale_date' => now(),
            'subtotal' => 99,
            'discount' => 0,
            'tax' => 0,
            'total' => 99,
            'status' => 'completed',
        ]);
        InventoryMovement::query()->create([
            'product_id' => $otherProduct->id,
            'warehouse_id' => $otherWarehouse->id,
            'user_id' => $otherUser->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 99,
            'package_quantity' => 99,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->get(route('reports.index', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Reportes')
            ->assertSee('Producto propio')
            ->assertDontSee('Producto ajeno')
            ->assertDontSee('OTHER-000001');

        $this
            ->actingAs($user)
            ->get(route('reports.index', [
                'type' => 'sales-products',
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Ventas por producto')
            ->assertSee('Producto propio')
            ->assertDontSee('Sin compras en el periodo.')
            ->assertDontSee('Producto ajeno');

        $this
            ->actingAs($user)
            ->get(route('reports.print', [
                'type' => 'sales-products',
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($company->name)
            ->assertSee('Imprimir / guardar PDF')
            ->assertSee('Producto propio')
            ->assertDontSee('Producto ajeno');
    }

    public function test_user_without_reports_permission_cannot_view_reports(): void
    {
        $user = User::factory()->create(['company_id' => Company::factory()]);

        $this
            ->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    /**
     * @return array{0: Company, 1: User, 2: Warehouse, 3: PointOfSale, 4: CashRegister}
     */
    private function context(): array
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
            'opened_at' => now(),
            'status' => 'open',
        ]);

        return [$company, $user, $warehouse, $pointOfSale, $cashRegister];
    }
}
