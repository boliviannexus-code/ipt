<?php

namespace Tests\Feature\Pos;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\MeasurementUnit;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PosSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_pos_sale_with_open_cash_register(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        $cashRegister = CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $product = Product::factory()->create(['company_id' => $warehouse->company_id, 'sale_price' => 3]);
        $presentation = Presentation::factory()->create([
            'company_id' => $warehouse->company_id,
            'name' => 'Caja x 10',
            'units_per_package' => 10,
        ]);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 50,
            'package_quantity' => 5,
            'units_per_package' => 10,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 2,
                        'unit_price' => 30,
                        'discount' => 5,
                    ],
                ],
            ])
            ->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('sales', [
            'cash_register_id' => $cashRegister->id,
            'customer_id' => null,
            'point_of_sale_id' => $pointOfSale->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'subtotal' => '60.00',
            'discount' => '5.00',
            'total' => '55.00',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('sale_details', [
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'package_quantity' => 2,
            'units_per_package' => 10,
            'quantity' => 20,
            'subtotal' => '55.00',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => InventoryMovementType::Sale->value,
            'quantity' => -20,
            'package_quantity' => -2,
            'reference_type' => 'sale',
        ]);
    }

    public function test_pos_product_picker_shows_stock_in_open_register_warehouse(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $unit = MeasurementUnit::factory()->create([
            'company_id' => $user->company_id,
            'abbreviation' => 'U',
        ]);
        $product = Product::factory()->create([
            'company_id' => $user->company_id,
            'measurement_unit_id' => $unit->id,
            'name' => 'Producto con stock visible',
        ]);
        $presentation = Presentation::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 12,
            'package_quantity' => 12,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Producto con stock visible - 12 U');
    }

    public function test_quick_sale_screen_shows_categories_products_and_unit_stock(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $category = Category::factory()->create(['company_id' => $user->company_id, 'name' => 'Bebidas']);
        $otherCategory = Category::factory()->create(['name' => 'Otra empresa']);
        $unit = MeasurementUnit::factory()->create(['company_id' => $user->company_id, 'abbreviation' => 'U']);
        $presentation = Presentation::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);
        $product = Product::factory()->create([
            'company_id' => $user->company_id,
            'category_id' => $category->id,
            'measurement_unit_id' => $unit->id,
            'name' => 'Agua personal',
            'sale_price' => 4,
        ]);
        Product::factory()->create([
            'company_id' => $otherCategory->company_id,
            'category_id' => $otherCategory->id,
            'name' => 'Producto ajeno',
        ]);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 8,
            'package_quantity' => 8,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Venta agil')
            ->assertSee('Bebidas')
            ->assertSee('Agua personal')
            ->assertSee('4.00')
            ->assertSee('8 U')
            ->assertDontSee('Producto ajeno');
    }

    public function test_quick_sale_screen_disables_products_without_unit_stock(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $category = Category::factory()->create(['company_id' => $user->company_id]);
        $unit = MeasurementUnit::factory()->create(['company_id' => $user->company_id, 'abbreviation' => 'U']);
        Presentation::factory()->create([
            'company_id' => $user->company_id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);
        Product::factory()->create([
            'company_id' => $user->company_id,
            'category_id' => $category->id,
            'measurement_unit_id' => $unit->id,
            'name' => 'Sin stock unitario',
        ]);

        $this
            ->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Sin stock unitario')
            ->assertSee('Sin stock');
    }

    public function test_quick_sale_screen_requires_unit_presentation(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $category = Category::factory()->create(['company_id' => $user->company_id]);
        Product::factory()->create([
            'company_id' => $user->company_id,
            'category_id' => $category->id,
            'name' => 'Producto sin unidad',
        ]);

        $this
            ->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Crea una presentacion activa de 1 unidad');
    }

    public function test_pos_sale_reuses_customer_history_by_document_number(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $customer = Customer::query()->create([
            'name' => 'Cliente Historico',
            'company_id' => $warehouse->company_id,
            'document_number' => '789456',
            'is_active' => true,
        ]);
        $product = Product::factory()->create(['company_id' => $warehouse->company_id, 'sale_price' => 3]);
        $presentation = Presentation::factory()->create([
            'company_id' => $warehouse->company_id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 5,
            'package_quantity' => 5,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'customer_document_number' => '789456',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 1,
                        'unit_price' => 3,
                    ],
                ],
            ])
            ->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'customer_name' => 'Cliente Historico',
            'customer_document_number' => '789456',
            'total' => '3.00',
        ]);
        $this->assertSame(1, Customer::query()->where('document_number', '789456')->count());
    }

    public function test_pos_sale_creates_customer_with_document_and_name_when_needed(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $product = Product::factory()->create(['company_id' => $warehouse->company_id, 'sale_price' => 3]);
        $presentation = Presentation::factory()->create([
            'company_id' => $warehouse->company_id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 5,
            'package_quantity' => 5,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'customer_document_number' => '123456',
                'customer_name' => 'Cliente Nuevo',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 1,
                        'unit_price' => 3,
                    ],
                ],
            ])
            ->assertRedirect(route('pos.index'));

        $customer = Customer::query()->where('document_number', '123456')->firstOrFail();

        $this->assertSame('Cliente Nuevo', $customer->name);
        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'customer_name' => 'Cliente Nuevo',
            'customer_document_number' => '123456',
            'total' => '3.00',
        ]);
    }

    public function test_pos_sale_with_name_only_does_not_create_customer_record(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $product = Product::factory()->create(['company_id' => $warehouse->company_id, 'sale_price' => 3]);
        $presentation = Presentation::factory()->create([
            'company_id' => $warehouse->company_id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 5,
            'package_quantity' => 5,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'customer_name' => 'Cliente de Mostrador',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 1,
                        'unit_price' => 3,
                    ],
                ],
            ])
            ->assertRedirect(route('pos.index'));

        $this->assertDatabaseMissing('customers', [
            'name' => 'Cliente de Mostrador',
        ]);
        $this->assertDatabaseHas('sales', [
            'customer_id' => null,
            'customer_name' => 'Cliente de Mostrador',
            'customer_document_number' => null,
            'total' => '3.00',
        ]);
    }

    public function test_pos_sale_requires_open_cash_register(): void
    {
        $user = $this->userWithPosAccess();
        $product = Product::factory()->create(['company_id' => $user->company_id]);
        $presentation = Presentation::factory()->create(['company_id' => $user->company_id]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 1,
                        'unit_price' => 10,
                    ],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_pos_sale_rejects_insufficient_stock(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => $user->company_id]);
        $pointOfSale = PointOfSale::factory()->forWarehouse($warehouse->id)->create();
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $pointOfSale->branch_id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $product = Product::factory()->create(['company_id' => $pointOfSale->company_id]);
        $presentation = Presentation::factory()->create(['company_id' => $pointOfSale->company_id, 'units_per_package' => 10]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 1,
                        'unit_price' => 10,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items');
    }

    public function test_pos_sale_can_be_paid_with_multiple_payment_methods(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $cash = PaymentMethod::query()->firstOrCreate(['name' => 'Efectivo', 'company_id' => $warehouse->company_id], ['is_active' => true]);
        $qr = PaymentMethod::query()->firstOrCreate(['name' => 'QR', 'company_id' => $warehouse->company_id], ['is_active' => true]);
        $product = Product::factory()->create(['company_id' => $warehouse->company_id, 'sale_price' => 3]);
        $presentation = Presentation::factory()->create([
            'company_id' => $warehouse->company_id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 5,
            'package_quantity' => 5,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 2,
                        'unit_price' => 3,
                    ],
                ],
                'payment_mode' => 'mixed',
                'payments' => [
                    [
                        'payment_method_id' => $cash->id,
                        'amount' => 2,
                    ],
                    [
                        'payment_method_id' => $qr->id,
                        'amount' => 4,
                        'reference' => 'QR-123',
                    ],
                ],
            ])
            ->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('sale_payments', [
            'payment_method_id' => $cash->id,
            'payment_method_name' => 'Efectivo',
            'amount' => '2.00',
        ]);
        $this->assertDatabaseHas('sale_payments', [
            'payment_method_id' => $qr->id,
            'payment_method_name' => 'QR',
            'amount' => '4.00',
            'reference' => 'QR-123',
        ]);
    }

    public function test_cash_pos_sale_accepts_received_amount_and_stores_change(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $cash = PaymentMethod::query()->firstOrCreate(['name' => 'Efectivo', 'company_id' => $warehouse->company_id], ['is_active' => true]);
        $product = Product::factory()->create(['company_id' => $warehouse->company_id, 'sale_price' => 15]);
        $presentation = Presentation::factory()->create([
            'company_id' => $warehouse->company_id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 5,
            'package_quantity' => 5,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'payment_mode' => 'cash',
                'cash_payment_method_id' => $cash->id,
                'cash_received' => 100,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 1,
                        'unit_price' => 15,
                    ],
                ],
            ])
            ->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('sale_payments', [
            'payment_method_id' => $cash->id,
            'payment_method_name' => 'Efectivo',
            'amount' => '15.00',
            'received_amount' => '100.00',
            'change_amount' => '85.00',
        ]);
    }

    public function test_pos_sale_rejects_payment_total_mismatch(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $cash = PaymentMethod::query()->firstOrCreate(['name' => 'Efectivo', 'company_id' => $warehouse->company_id], ['is_active' => true]);
        $product = Product::factory()->create(['company_id' => $warehouse->company_id, 'sale_price' => 3]);
        $presentation = Presentation::factory()->create([
            'company_id' => $warehouse->company_id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 5,
            'package_quantity' => 5,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 2,
                        'unit_price' => 3,
                    ],
                ],
                'payment_mode' => 'mixed',
                'payments' => [
                    [
                        'payment_method_id' => $cash->id,
                        'amount' => 5,
                    ],
                ],
            ])
            ->assertSessionHasErrors('payments');
    }

    public function test_receipt_numbers_are_independent_per_point_of_sale(): void
    {
        $firstUser = $this->userWithPosAccess();
        $secondUser = User::factory()->create(['company_id' => $firstUser->company_id]);
        $secondUser->givePermissionTo('pos.access');
        $firstBranch = Branch::factory()->create(['company_id' => $firstUser->company_id]);
        $secondBranch = Branch::factory()->create(['company_id' => $firstUser->company_id]);
        $firstWarehouse = Warehouse::factory()->for($firstBranch)->create();
        $secondWarehouse = Warehouse::factory()->for($secondBranch)->create();
        $firstPointOfSale = PointOfSale::factory()->forWarehouse($firstWarehouse->id)->create([
            'receipt_prefix' => 'PV-A',
            'receipt_next_number' => 25,
            'receipt_digits' => 4,
        ]);
        $secondPointOfSale = PointOfSale::factory()->forWarehouse($secondWarehouse->id)->create([
            'receipt_prefix' => 'PV-B',
            'receipt_next_number' => 1,
            'receipt_digits' => 6,
        ]);
        $firstPointOfSale->users()->sync([$firstUser->id]);
        $secondPointOfSale->users()->sync([$secondUser->id]);
        $firstCashRegister = CashRegister::factory()->create([
            'point_of_sale_id' => $firstPointOfSale->id,
            'branch_id' => $firstBranch->id,
            'user_id' => $firstUser->id,
            'status' => 'open',
        ]);
        $secondCashRegister = CashRegister::factory()->create([
            'point_of_sale_id' => $secondPointOfSale->id,
            'branch_id' => $secondBranch->id,
            'user_id' => $secondUser->id,
            'status' => 'open',
        ]);
        $firstProduct = Product::factory()->create(['company_id' => $firstUser->company_id, 'sale_price' => 3]);
        $secondProduct = Product::factory()->create(['company_id' => $firstUser->company_id, 'sale_price' => 3]);
        $presentation = Presentation::factory()->create([
            'company_id' => $firstUser->company_id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);

        foreach ([[$firstProduct, $firstWarehouse, $firstUser], [$secondProduct, $secondWarehouse, $secondUser]] as [$product, $warehouse, $user]) {
            InventoryMovement::query()->create([
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'presentation_name' => $presentation->name,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'type' => InventoryMovementType::Purchase,
                'quantity' => 5,
                'package_quantity' => 5,
                'units_per_package' => 1,
                'reference_type' => 'test',
            ]);
        }

        $this->actingAs($firstUser)->post(route('pos.sales.store'), [
            'items' => [[
                'product_id' => $firstProduct->id,
                'presentation_id' => $presentation->id,
                'package_quantity' => 1,
                'unit_price' => 3,
            ]],
        ])->assertRedirect(route('pos.index'));

        $this->actingAs($secondUser)->post(route('pos.sales.store'), [
            'items' => [[
                'product_id' => $secondProduct->id,
                'presentation_id' => $presentation->id,
                'package_quantity' => 1,
                'unit_price' => 3,
            ]],
        ])->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('sales', [
            'cash_register_id' => $firstCashRegister->id,
            'receipt_number' => 'PV-A-0025',
            'sequence_number' => 25,
        ]);
        $this->assertDatabaseHas('sales', [
            'cash_register_id' => $secondCashRegister->id,
            'receipt_number' => 'PV-B-000001',
            'sequence_number' => 1,
        ]);
        $this->assertSame(26, $firstPointOfSale->refresh()->receipt_next_number);
        $this->assertSame(2, $secondPointOfSale->refresh()->receipt_next_number);
    }

    private function userWithPosAccess(): User
    {
        Permission::findOrCreate('pos.access');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('pos.access');

        return $user;
    }
}
