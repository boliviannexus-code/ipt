<?php

namespace Tests\Feature\Security;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\MeasurementUnit;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FloatingDataPurgeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reports_and_deletes_floating_pre_company_data(): void
    {
        $user = User::factory()->create(['company_id' => null]);
        $branch = Branch::factory()->create(['company_id' => null]);
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => null]);
        $pointOfSale = PointOfSale::factory()->for($branch)->create([
            'company_id' => null,
            'warehouse_id' => $warehouse->id,
        ]);
        $pointOfSale->users()->sync([$user->id]);
        $supplier = Supplier::factory()->create(['company_id' => null]);
        $customer = Customer::query()->create([
            'name' => 'Cliente flotante',
            'company_id' => null,
            'document_number' => 'FLOAT-CUST',
            'is_active' => true,
        ]);
        $category = Category::factory()->create(['company_id' => null]);
        $unit = MeasurementUnit::factory()->create(['company_id' => null]);
        $product = Product::factory()
            ->for($category)
            ->for($unit, 'measurementUnit')
            ->create(['company_id' => null]);
        $presentation = Presentation::factory()->create(['company_id' => null]);
        $paymentMethod = PaymentMethod::query()->create([
            'name' => 'Pago flotante',
            'company_id' => null,
            'is_active' => true,
        ]);
        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
        ]);
        $purchase->details()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'package_quantity' => 1,
            'units_per_package' => 1,
            'quantity' => 1,
            'unit_price' => 10,
            'subtotal' => 10,
        ]);
        $cashRegister = CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);
        $sale = Sale::query()->create([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'cash_register_id' => $cashRegister->id,
            'point_of_sale_id' => $pointOfSale->id,
            'receipt_number' => 'FLOAT-SALE',
            'sequence_number' => 1,
            'sale_date' => now(),
            'subtotal' => 10,
            'discount' => 0,
            'tax' => 0,
            'total' => 10,
            'status' => 'completed',
        ]);
        $sale->details()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'package_quantity' => 1,
            'units_per_package' => 1,
            'quantity' => 1,
            'unit_price' => 10,
            'discount' => 0,
            'subtotal' => 10,
        ]);
        $sale->payments()->create([
            'payment_method_id' => $paymentMethod->id,
            'payment_method_name' => $paymentMethod->name,
            'amount' => 10,
        ]);
        $cashRegister->expenses()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'user_id' => $user->id,
            'responsible_name' => 'Caja',
            'detail' => 'Gasto flotante',
            'amount' => 1,
            'spent_at' => now(),
        ]);
        $movement = InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 1,
            'package_quantity' => 1,
            'units_per_package' => 1,
            'reference_type' => 'floating-test',
        ]);

        Artisan::call('companies:purge-floating-data');
        $this->assertStringContainsString('Dry run only', Artisan::output());
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        Artisan::call('companies:purge-floating-data', ['--force' => true]);

        foreach ([
            'sales' => $sale->id,
            'purchases' => $purchase->id,
            'inventory_movements' => $movement->id,
            'cash_registers' => $cashRegister->id,
            'point_of_sales' => $pointOfSale->id,
            'warehouses' => $warehouse->id,
            'branches' => $branch->id,
            'products' => $product->id,
            'categories' => $category->id,
            'measurement_units' => $unit->id,
            'presentations' => $presentation->id,
            'payment_methods' => $paymentMethod->id,
            'suppliers' => $supplier->id,
            'customers' => $customer->id,
        ] as $table => $id) {
            $this->assertDatabaseMissing($table, ['id' => $id]);
        }

        $this->assertDatabaseMissing('point_of_sale_user', ['point_of_sale_id' => $pointOfSale->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
