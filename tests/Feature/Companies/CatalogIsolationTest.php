<?php

namespace Tests\Feature\Companies;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\MeasurementUnit;
use App\Models\PaymentMethod;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\PointOfSale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CatalogIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_only_sees_own_products_in_datatable(): void
    {
        Permission::findOrCreate('products.view');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('products.view');

        Product::factory()->create(['company_id' => $company->id, 'name' => 'Producto Propio']);
        Product::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Producto Ajeno']);

        $this
            ->actingAs($user)
            ->getJson(route('datatables.products'))
            ->assertOk()
            ->assertSee('Producto Propio')
            ->assertDontSee('Producto Ajeno');
    }

    public function test_product_created_by_company_user_gets_user_company(): void
    {
        Permission::findOrCreate('products.create');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('products.create');
        $category = Category::factory()->create(['company_id' => $company->id]);
        $unit = MeasurementUnit::factory()->create(['company_id' => $company->id]);

        $this
            ->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('products.store'), [
                'name' => 'Producto Empresa',
                'barcode' => '1234567890123',
                'category_id' => $category->id,
                'measurement_unit_id' => $unit->id,
                'purchase_price' => 10,
                'sale_price' => 15,
                'minimum_stock' => 1,
                'is_active' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('products', [
            'name' => 'Producto Empresa',
            'company_id' => $company->id,
        ]);
    }

    public function test_company_user_cannot_create_product_with_other_company_category(): void
    {
        Permission::findOrCreate('products.create');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('products.create');
        $category = Category::factory()->create(['company_id' => $otherCompany->id]);
        $unit = MeasurementUnit::factory()->create(['company_id' => $company->id]);

        $this
            ->actingAs($user)
            ->post(route('products.store'), [
                'name' => 'Producto Invalido',
                'barcode' => '1234567890124',
                'category_id' => $category->id,
                'measurement_unit_id' => $unit->id,
                'purchase_price' => 10,
                'sale_price' => 15,
                'minimum_stock' => 1,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('category_id');
    }

    public function test_company_user_cannot_update_other_company_product_through_api(): void
    {
        Permission::findOrCreate('products.update');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('products.update');
        $category = Category::factory()->create(['company_id' => $company->id]);
        $unit = MeasurementUnit::factory()->create(['company_id' => $company->id]);
        $otherProduct = Product::factory()->create(['company_id' => $otherCompany->id]);

        Sanctum::actingAs($user);

        $this
            ->putJson(route('api.v1.products.update', $otherProduct), [
                'name' => 'Intento de toma',
                'barcode' => '9999999999999',
                'category_id' => $category->id,
                'measurement_unit_id' => $unit->id,
                'purchase_price' => 10,
                'sale_price' => 15,
                'minimum_stock' => 1,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('products', [
            'id' => $otherProduct->id,
            'name' => 'Intento de toma',
            'company_id' => $company->id,
        ]);
    }

    public function test_company_user_only_sees_own_suppliers_in_datatable(): void
    {
        Permission::findOrCreate('suppliers.view');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('suppliers.view');

        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Proveedor Propio']);
        Supplier::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Proveedor Ajeno']);

        $this
            ->actingAs($user)
            ->getJson(route('datatables.suppliers'))
            ->assertOk()
            ->assertSee('Proveedor Propio')
            ->assertDontSee('Proveedor Ajeno');
    }

    public function test_company_user_cannot_view_other_company_supplier(): void
    {
        Permission::findOrCreate('suppliers.view');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('suppliers.view');
        $supplier = Supplier::factory()->create(['company_id' => $otherCompany->id]);

        $this
            ->actingAs($user)
            ->get(route('suppliers.show', $supplier))
            ->assertForbidden();
    }

    public function test_purchase_rejects_catalogs_from_other_company(): void
    {
        foreach (['purchases.create', 'purchases.view'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['purchases.create', 'purchases.view']);
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $otherCompany->id]);
        $presentation = Presentation::factory()->create(['company_id' => $company->id, 'units_per_package' => 1]);

        $this
            ->actingAs($user)
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'purchase_date' => '2026-05-21',
                'items' => [[
                    'product_id' => $product->id,
                    'presentation_id' => $presentation->id,
                    'package_quantity' => 1,
                    'unit_price' => 10,
                ]],
            ])
            ->assertSessionHasErrors('items.0.product_id');
    }

    public function test_pos_rejects_payment_method_from_other_company(): void
    {
        Permission::findOrCreate('pos.access');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('pos.access');
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => $company->id]);
        $pointOfSale = PointOfSale::factory()->for($branch)->create([
            'warehouse_id' => $warehouse->id,
            'company_id' => $company->id,
        ]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $presentation = Presentation::factory()->create(['company_id' => $company->id, 'units_per_package' => 1]);
        $paymentMethod = PaymentMethod::query()->create([
            'name' => 'Tarjeta ajena',
            'company_id' => $otherCompany->id,
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'payment_mode' => 'mixed',
                'payments' => [[
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => 10,
                ]],
                'items' => [[
                    'product_id' => $product->id,
                    'presentation_id' => $presentation->id,
                    'package_quantity' => 1,
                    'unit_price' => 10,
                ]],
            ])
            ->assertSessionHasErrors('payments.0.payment_method_id');
    }

    public function test_pos_rejects_customer_from_other_company(): void
    {
        Permission::findOrCreate('pos.access');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('pos.access');
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => $company->id]);
        $pointOfSale = PointOfSale::factory()->for($branch)->create([
            'warehouse_id' => $warehouse->id,
            'company_id' => $company->id,
        ]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $customer = Customer::query()->create([
            'name' => 'Cliente Ajeno',
            'company_id' => $otherCompany->id,
            'document_number' => 'CLI-AJENO',
            'is_active' => true,
        ]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $presentation = Presentation::factory()->create(['company_id' => $company->id, 'units_per_package' => 1]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'customer_id' => $customer->id,
                'payment_mode' => 'cash',
                'items' => [[
                    'product_id' => $product->id,
                    'presentation_id' => $presentation->id,
                    'package_quantity' => 1,
                    'unit_price' => 10,
                ]],
            ])
            ->assertSessionHasErrors('customer_id');
    }
}
