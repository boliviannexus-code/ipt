<?php

namespace Tests\Feature\Security;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\MeasurementUnit;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MultiCompanyDataBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_update_requests_cannot_modify_other_company_locations(): void
    {
        [$company, $otherCompany, $user] = $this->companyUser([
            'branches.update',
            'warehouses.update',
            'point-of-sales.update',
        ]);
        $ownBranch = Branch::factory()->for($company)->create();
        $ownWarehouse = Warehouse::factory()->for($ownBranch)->create(['company_id' => $company->id]);
        $otherBranch = Branch::factory()->for($otherCompany)->create(['name' => 'Sucursal protegida']);
        $otherWarehouse = Warehouse::factory()->for($otherBranch)->create([
            'company_id' => $otherCompany->id,
            'name' => 'Almacen protegido',
        ]);
        $otherPointOfSale = PointOfSale::factory()->for($otherBranch)->create([
            'company_id' => $otherCompany->id,
            'warehouse_id' => $otherWarehouse->id,
            'name' => 'POS protegido',
        ]);

        $this
            ->actingAs($user)
            ->put(route('branches.update', $otherBranch), [
                'company_id' => $company->id,
                'name' => 'Sucursal tomada',
                'code' => $otherBranch->code,
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->put(route('warehouses.update', $otherWarehouse), [
                'branch_id' => $ownBranch->id,
                'name' => 'Almacen tomado',
                'code' => $otherWarehouse->code,
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->put(route('point-of-sales.update', $otherPointOfSale), [
                'branch_id' => $ownBranch->id,
                'warehouse_id' => $ownWarehouse->id,
                'name' => 'POS tomado',
                'users' => [$user->id],
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('branches', ['id' => $otherBranch->id, 'name' => 'Sucursal protegida']);
        $this->assertDatabaseHas('warehouses', ['id' => $otherWarehouse->id, 'name' => 'Almacen protegido']);
        $this->assertDatabaseHas('point_of_sales', ['id' => $otherPointOfSale->id, 'name' => 'POS protegido']);
    }

    public function test_direct_update_requests_cannot_modify_other_company_catalogs_through_api(): void
    {
        [$company, $otherCompany, $user] = $this->companyUser(['categories.update', 'products.update']);
        $ownCategory = Category::factory()->create(['company_id' => $company->id]);
        $ownUnit = MeasurementUnit::factory()->create(['company_id' => $company->id]);
        $otherCategory = Category::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Categoria protegida']);
        $otherProduct = Product::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Producto protegido']);

        Sanctum::actingAs($user);

        $this
            ->putJson(route('api.v1.categories.update', $otherCategory), [
                'name' => 'Categoria tomada',
                'is_active' => true,
            ])
            ->assertForbidden();

        $this
            ->putJson(route('api.v1.products.update', $otherProduct), [
                'name' => 'Producto tomado',
                'barcode' => '9000000000001',
                'category_id' => $ownCategory->id,
                'measurement_unit_id' => $ownUnit->id,
                'purchase_price' => 10,
                'sale_price' => 15,
                'minimum_stock' => 1,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $otherCategory->id, 'name' => 'Categoria protegida']);
        $this->assertDatabaseHas('products', ['id' => $otherProduct->id, 'name' => 'Producto protegido']);
    }

    public function test_company_user_cannot_view_or_update_other_company_user(): void
    {
        [$company, $otherCompany, $user] = $this->companyUser(['users.view', 'users.create', 'users.edit']);
        $otherUser = User::factory()->for($otherCompany)->create([
            'name' => 'Usuario protegido',
            'email' => 'protegido@example.test',
        ]);

        $this
            ->actingAs($user)
            ->get(route('users.show', $otherUser))
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->put(route('users.update', $otherUser), [
                'name' => 'Usuario tomado',
                'company_id' => $company->id,
                'email' => 'tomado@example.test',
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(route('users.store'), [
                'name' => 'Nuevo usuario',
                'company_id' => $otherCompany->id,
                'email' => 'nuevo@example.test',
                'password' => 'password-seguro',
                'password_confirmation' => 'password-seguro',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $otherUser->id, 'name' => 'Usuario protegido']);
        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@example.test',
            'company_id' => $company->id,
        ]);
    }

    public function test_datatables_do_not_leak_foreign_records_even_when_filters_use_other_company_ids(): void
    {
        [$company, $otherCompany, $user] = $this->companyUser([
            'purchases.view',
            'sales.view',
            'inventory.view',
        ]);
        $ownWarehouse = $this->warehouseForCompany($company);
        $otherWarehouse = $this->warehouseForCompany($otherCompany);
        $ownProduct = Product::factory()->create(['company_id' => $company->id, 'name' => 'Producto visible']);
        $otherProduct = Product::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Producto filtrado ajeno']);
        $otherSupplier = Supplier::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Proveedor filtrado ajeno']);
        $otherCustomer = Customer::query()->create([
            'name' => 'Cliente filtrado ajeno',
            'company_id' => $otherCompany->id,
            'document_number' => 'CLI-FILTRO',
            'is_active' => true,
        ]);

        Purchase::factory()->create([
            'supplier_id' => $otherSupplier->id,
            'warehouse_id' => $ownWarehouse->id,
            'reference' => 'COMPRA-INCONSISTENTE',
            'purchase_date' => now()->toDateString(),
        ]);
        $cashRegister = CashRegister::factory()->create([
            'point_of_sale_id' => PointOfSale::factory()->forWarehouse($ownWarehouse->id)->create()->id,
            'branch_id' => $ownWarehouse->branch_id,
            'user_id' => $user->id,
            'status' => 'closed',
        ]);
        \App\Models\Sale::query()->create([
            'customer_id' => $otherCustomer->id,
            'customer_name' => $otherCustomer->name,
            'branch_id' => $ownWarehouse->branch_id,
            'warehouse_id' => $ownWarehouse->id,
            'user_id' => $user->id,
            'cash_register_id' => $cashRegister->id,
            'point_of_sale_id' => $cashRegister->point_of_sale_id,
            'receipt_number' => 'VENTA-INCONSISTENTE',
            'sequence_number' => 1,
            'sale_date' => now(),
            'subtotal' => 10,
            'discount' => 0,
            'tax' => 0,
            'total' => 10,
            'status' => 'completed',
        ]);
        $this->inventoryMovement($ownProduct, $ownWarehouse, $user, 5);
        $this->inventoryMovement($otherProduct, $ownWarehouse, $user, 7);
        $this->inventoryMovement($otherProduct, $otherWarehouse, User::factory()->for($otherCompany)->create(), 9);

        $this
            ->actingAs($user)
            ->getJson(route('datatables.purchases', ['supplier_id' => $otherSupplier->id]))
            ->assertOk()
            ->assertDontSee('COMPRA-INCONSISTENTE')
            ->assertDontSee('Proveedor filtrado ajeno');

        $this
            ->actingAs($user)
            ->getJson(route('datatables.sales'))
            ->assertOk()
            ->assertDontSee('VENTA-INCONSISTENTE')
            ->assertDontSee('Cliente filtrado ajeno');

        $this
            ->actingAs($user)
            ->getJson(route('datatables.stock', ['product_id' => $otherProduct->id]))
            ->assertOk()
            ->assertDontSee('Producto filtrado ajeno');

        $this
            ->actingAs($user)
            ->getJson(route('datatables.kardex', ['product_id' => $otherProduct->id]))
            ->assertOk()
            ->assertDontSee('Producto filtrado ajeno');
    }

    /**
     * @param array<int, string> $permissions
     */
    private function companyUser(array $permissions): array
    {
        $company = Company::factory()->create(['name' => 'Empresa Segura A']);
        $otherCompany = Company::factory()->create(['name' => 'Empresa Segura B']);
        $user = User::factory()->for($company)->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        return [$company, $otherCompany, $user];
    }

    private function warehouseForCompany(Company $company): Warehouse
    {
        $branch = Branch::factory()->for($company)->create();

        return Warehouse::factory()->for($branch)->create(['company_id' => $company->id]);
    }

    private function inventoryMovement(Product $product, Warehouse $warehouse, User $user, int $quantity): InventoryMovement
    {
        return InventoryMovement::query()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => $quantity,
            'package_quantity' => $quantity,
            'units_per_package' => 1,
            'reference_type' => 'security-test',
        ]);
    }
}
