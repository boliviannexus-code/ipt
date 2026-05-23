<?php

namespace Tests\Feature\Purchases;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PurchaseCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_purchase_form_loads_with_active_catalogs(): void
    {
        Permission::findOrCreate('purchases.create');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('purchases.create');

        $branch = Branch::factory()->create(['company_id' => $company->id]);
        Warehouse::factory()->for($branch)->create(['company_id' => $company->id]);
        Supplier::factory()->create(['company_id' => $company->id]);
        Product::factory()->create(['company_id' => $company->id]);
        Presentation::factory()->create(['company_id' => $company->id]);

        $this
            ->actingAs($user)
            ->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Nueva compra')
            ->assertSee('Agregar producto');
    }

    public function test_user_can_create_purchase_and_register_stock_by_presentation(): void
    {
        Permission::findOrCreate('purchases.view');
        Permission::findOrCreate('purchases.create');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['purchases.view', 'purchases.create']);

        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $warehouse->company_id]);
        $product = Product::factory()->create(['company_id' => $warehouse->company_id, 'purchase_price' => 25.50]);
        $presentation = Presentation::factory()->create([
            'company_id' => $warehouse->company_id,
            'name' => 'Caja x 10',
            'units_per_package' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'purchase_date' => '2026-05-20',
                'notes' => 'Ingreso inicial',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 2,
                        'unit_price' => 255,
                    ],
                ],
            ]);

        $purchase = Purchase::query()->firstOrFail();

        $response->assertRedirect(route('purchases.show', $purchase));

        $this->assertSame($branch->id.'-'.$warehouse->id.'-000001', $purchase->reference);
        $this->assertSame(1, $purchase->sequence_number);
        $this->assertSame('510.00', $purchase->total);

        $this->assertDatabaseHas('purchase_details', [
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => 'Caja x 10',
            'package_quantity' => 2,
            'units_per_package' => 10,
            'quantity' => 20,
            'unit_price' => '255.00',
            'subtotal' => '510.00',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => InventoryMovementType::Purchase->value,
            'quantity' => 20,
            'package_quantity' => 2,
            'units_per_package' => 10,
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
        ]);

        $this->assertSame(20, (int) InventoryMovement::query()->sum('quantity'));
    }

    public function test_purchase_reference_increments_by_warehouse(): void
    {
        Permission::findOrCreate('purchases.view');
        Permission::findOrCreate('purchases.create');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['purchases.view', 'purchases.create']);

        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $warehouse->company_id]);
        $presentation = Presentation::factory()->create(['company_id' => $warehouse->company_id, 'units_per_package' => 1]);

        foreach ([1, 2] as $expectedSequence) {
            $this
                ->actingAs($user)
                ->post(route('purchases.store'), [
                    'warehouse_id' => $warehouse->id,
                    'purchase_date' => '2026-05-20',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'presentation_id' => $presentation->id,
                            'package_quantity' => 1,
                            'unit_price' => 10,
                        ],
                    ],
                ])
                ->assertRedirect();

            $this->assertDatabaseHas('purchases', [
                'warehouse_id' => $warehouse->id,
                'sequence_number' => $expectedSequence,
                'reference' => $branch->id.'-'.$warehouse->id.'-'.str_pad((string) $expectedSequence, 6, '0', STR_PAD_LEFT),
            ]);
        }
    }
}
