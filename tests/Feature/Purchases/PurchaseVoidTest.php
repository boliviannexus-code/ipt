<?php

namespace Tests\Feature\Purchases;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PurchaseVoidTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_permission_can_void_purchase_and_reverse_stock(): void
    {
        Permission::findOrCreate('purchases.view');
        Permission::findOrCreate('purchases.create');
        Permission::findOrCreate('purchases.void');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['purchases.view', 'purchases.create', 'purchases.void']);
        $warehouse = $this->warehouseFor($company);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $presentation = Presentation::factory()->create([
            'company_id' => $company->id,
            'name' => 'Caja x 10',
            'units_per_package' => 10,
        ]);
        $purchase = $this->purchaseFor($warehouse, $product, $presentation, $user, 2);

        $this
            ->actingAs($user)
            ->post(route('purchases.void', $purchase), [
                'void_reason' => 'Factura duplicada',
            ])
            ->assertRedirect(route('purchases.show', $purchase));

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'voided',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => InventoryMovementType::AdjustmentOut->value,
            'quantity' => -20,
            'package_quantity' => -2,
            'reference_type' => 'purchase_void',
            'reference_id' => $purchase->id,
        ]);
        $this->assertSame(0, (int) InventoryMovement::query()->where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->sum('quantity'));
    }

    public function test_user_without_void_permission_cannot_void_purchase(): void
    {
        Permission::findOrCreate('purchases.view');
        Permission::findOrCreate('purchases.create');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['purchases.view', 'purchases.create']);
        $warehouse = $this->warehouseFor($company);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $presentation = Presentation::factory()->create(['company_id' => $company->id, 'units_per_package' => 1]);
        $purchase = $this->purchaseFor($warehouse, $product, $presentation, $user, 5);

        $this
            ->actingAs($user)
            ->post(route('purchases.void', $purchase), [
                'void_reason' => 'No autorizado',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'completed',
        ]);
    }

    public function test_purchase_cannot_be_voided_when_stock_was_consumed(): void
    {
        Permission::findOrCreate('purchases.view');
        Permission::findOrCreate('purchases.create');
        Permission::findOrCreate('purchases.void');
        Permission::findOrCreate('inventory.movements');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo(['purchases.view', 'purchases.create', 'purchases.void', 'inventory.movements']);
        $warehouse = $this->warehouseFor($company);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $presentation = Presentation::factory()->create(['company_id' => $company->id, 'units_per_package' => 1]);
        $purchase = $this->purchaseFor($warehouse, $product, $presentation, $user, 5);

        app(InventoryService::class)->register([
            'operation' => 'out',
            'warehouse_id' => $warehouse->id,
            'items' => [
                ['product_id' => $product->id, 'presentation_id' => $presentation->id, 'package_quantity' => 2],
            ],
        ], $user->id);

        $this
            ->actingAs($user)
            ->post(route('purchases.void', $purchase), [
                'void_reason' => 'Stock consumido',
            ])
            ->assertSessionHasErrors('purchase');

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'completed',
        ]);
    }

    public function test_company_user_cannot_void_other_company_purchase(): void
    {
        Permission::findOrCreate('purchases.view');
        Permission::findOrCreate('purchases.create');
        Permission::findOrCreate('purchases.void');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $user->givePermissionTo(['purchases.view', 'purchases.create', 'purchases.void']);
        $otherUser->givePermissionTo(['purchases.view', 'purchases.create', 'purchases.void']);
        $warehouse = $this->warehouseFor($otherCompany);
        $product = Product::factory()->create(['company_id' => $otherCompany->id]);
        $presentation = Presentation::factory()->create(['company_id' => $otherCompany->id, 'units_per_package' => 1]);
        $purchase = $this->purchaseFor($warehouse, $product, $presentation, $otherUser, 3);

        $this
            ->actingAs($user)
            ->post(route('purchases.void', $purchase), [
                'void_reason' => 'Intento cruzado',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'completed',
        ]);
    }

    private function purchaseFor(Warehouse $warehouse, Product $product, Presentation $presentation, User $user, int $packages): Purchase
    {
        return app(PurchaseService::class)->create([
            'warehouse_id' => $warehouse->id,
            'purchase_date' => '2026-05-22',
            'items' => [
                [
                    'product_id' => $product->id,
                    'presentation_id' => $presentation->id,
                    'package_quantity' => $packages,
                    'unit_price' => 10,
                ],
            ],
        ], $user->id);
    }

    private function warehouseFor(Company $company): Warehouse
    {
        $branch = Branch::factory()->for($company)->create();

        return Warehouse::factory()->for($branch)->create(['company_id' => $company->id]);
    }
}
