<?php

namespace Tests\Feature\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_can_recount_base_units(): void
    {
        Permission::findOrCreate('inventory.movements');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('inventory.movements');
        $warehouse = $this->warehouseFor($company, 'Central');
        $product = Product::factory()->create(['company_id' => $company->id]);

        app(InventoryService::class)->register([
            'operation' => 'in',
            'warehouse_id' => $warehouse->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10],
            ],
        ], $user->id);

        $this
            ->actingAs($user)
            ->post(route('inventory.adjustment.store'), [
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'counted_quantity' => 7,
                'reason' => 'perdida',
                'notes' => 'Conteo fisico',
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => InventoryMovementType::AdjustmentOut->value,
            'quantity' => -3,
            'reference_type' => 'stock_recount',
            'notes' => 'Motivo: Perdida. Conteo fisico | Reajuste de stock: conteo fisico 7 frente a sistema 10.',
        ]);
        $this->assertSame(7, (int) $product->inventoryMovements()->where('warehouse_id', $warehouse->id)->sum('quantity'));
    }

    public function test_company_user_can_recount_unit_presentation(): void
    {
        Permission::findOrCreate('inventory.movements');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('inventory.movements');
        $warehouse = $this->warehouseFor($company, 'Central');
        $product = Product::factory()->create(['company_id' => $company->id]);
        $unitPresentation = Presentation::factory()->create([
            'company_id' => $company->id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);

        app(InventoryService::class)->register([
            'operation' => 'in',
            'warehouse_id' => $warehouse->id,
            'items' => [
                ['product_id' => $product->id, 'presentation_id' => $unitPresentation->id, 'package_quantity' => 10],
            ],
        ], $user->id);

        $this
            ->actingAs($user)
            ->post(route('inventory.adjustment.store'), [
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'presentation_id' => $unitPresentation->id,
                'counted_quantity' => 8,
                'reason' => 'robo',
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'presentation_id' => $unitPresentation->id,
            'type' => InventoryMovementType::AdjustmentOut->value,
            'quantity' => -2,
            'package_quantity' => -2,
            'reference_type' => 'stock_recount',
            'notes' => 'Motivo: Robo. Reajuste de stock: conteo fisico 8 frente a sistema 10.',
        ]);
    }

    public function test_company_user_can_recount_presentation_packages(): void
    {
        Permission::findOrCreate('inventory.movements');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('inventory.movements');
        $warehouse = $this->warehouseFor($company, 'Central');
        $product = Product::factory()->create(['company_id' => $company->id]);
        $presentation = Presentation::factory()->create([
            'company_id' => $company->id,
            'name' => 'Caja x 6',
            'units_per_package' => 6,
        ]);

        app(InventoryService::class)->register([
            'operation' => 'in',
            'warehouse_id' => $warehouse->id,
            'items' => [
                ['product_id' => $product->id, 'presentation_id' => $presentation->id, 'package_quantity' => 4],
            ],
        ], $user->id);

        $this
            ->actingAs($user)
            ->post(route('inventory.adjustment.store'), [
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'counted_quantity' => 6,
                'reason' => 'conteo_fisico',
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'presentation_id' => $presentation->id,
            'type' => InventoryMovementType::AdjustmentIn->value,
            'quantity' => 12,
            'package_quantity' => 2,
            'reference_type' => 'stock_recount',
        ]);
    }

    public function test_company_user_cannot_recount_other_company_stock(): void
    {
        Permission::findOrCreate('inventory.movements');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('inventory.movements');
        $warehouse = $this->warehouseFor($otherCompany, 'Ajeno');
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this
            ->actingAs($user)
            ->post(route('inventory.adjustment.store'), [
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'counted_quantity' => 3,
                'reason' => 'otros',
            ])
            ->assertSessionHasErrors('warehouse_id');

        $this->assertDatabaseMissing('inventory_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'reference_type' => 'stock_recount',
        ]);
    }

    private function warehouseFor(Company $company, string $name): Warehouse
    {
        $branch = Branch::factory()->for($company)->create();

        return Warehouse::factory()->for($branch)->create([
            'company_id' => $company->id,
            'name' => $name,
        ]);
    }
}
