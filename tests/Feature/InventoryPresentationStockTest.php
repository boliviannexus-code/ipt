<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\Presentation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryPresentationStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_tracks_total_stock_and_packages_by_presentation(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->for(Branch::factory())->create();
        $companyId = $warehouse->company_id;
        $product = Product::factory()
            ->for(Category::factory(['company_id' => $companyId]))
            ->for(MeasurementUnit::factory(['company_id' => $companyId, 'abbreviation' => 'un']), 'measurementUnit')
            ->create(['company_id' => $companyId]);

        $box10 = Presentation::factory()->create([
            'company_id' => $companyId,
            'name' => 'Caja x 10',
            'units_per_package' => 10,
        ]);
        $box20 = Presentation::factory()->create([
            'company_id' => $companyId,
            'name' => 'Caja x 20',
            'units_per_package' => 20,
        ]);

        app(InventoryService::class)->register([
            'operation' => 'in',
            'warehouse_id' => $warehouse->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'presentation_id' => $box10->id,
                    'package_quantity' => 5,
                ],
                [
                    'product_id' => $product->id,
                    'presentation_id' => $box20->id,
                    'package_quantity' => 3,
                ],
            ],
        ], $user->id);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'presentation_id' => $box10->id,
            'presentation_name' => 'Caja x 10',
            'package_quantity' => 5,
            'units_per_package' => 10,
            'quantity' => 50,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'presentation_id' => $box20->id,
            'presentation_name' => 'Caja x 20',
            'package_quantity' => 3,
            'units_per_package' => 20,
            'quantity' => 60,
        ]);

        $this->assertSame(110, (int) $product->inventoryMovements()->sum('quantity'));
    }

    public function test_package_can_be_defragmented_into_units_without_changing_total_stock(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->for(Branch::factory())->create();
        $companyId = $warehouse->company_id;
        $product = Product::factory()
            ->for(Category::factory(['company_id' => $companyId]))
            ->for(MeasurementUnit::factory(['company_id' => $companyId, 'abbreviation' => 'un']), 'measurementUnit')
            ->create(['company_id' => $companyId]);
        $unit = Presentation::factory()->create([
            'company_id' => $companyId,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);
        $box = Presentation::factory()->create([
            'company_id' => $companyId,
            'name' => 'Caja x 10',
            'units_per_package' => 10,
        ]);
        $inventory = app(InventoryService::class);

        $inventory->register([
            'operation' => 'in',
            'warehouse_id' => $warehouse->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'presentation_id' => $box->id,
                    'package_quantity' => 3,
                ],
            ],
        ], $user->id);

        $inventory->defragmentPackage([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'presentation_id' => $box->id,
            'package_quantity' => 1,
            'notes' => 'Separar para venta por unidad',
        ], $user->id);

        $this->assertSame(30, (int) $product->inventoryMovements()->sum('quantity'));
        $this->assertSame(2, (int) $product->inventoryMovements()->where('presentation_id', $box->id)->sum('package_quantity'));
        $this->assertSame(10, (int) $product->inventoryMovements()->where('presentation_id', $unit->id)->sum('package_quantity'));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'presentation_id' => $box->id,
            'package_quantity' => -1,
            'quantity' => -10,
            'reference_type' => 'stock_defragmentation',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'presentation_id' => $unit->id,
            'package_quantity' => 10,
            'quantity' => 10,
            'reference_type' => 'stock_defragmentation',
        ]);
    }

    public function test_defragmentation_rejects_more_packages_than_available(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->for(Branch::factory())->create();
        $companyId = $warehouse->company_id;
        $product = Product::factory()
            ->for(Category::factory(['company_id' => $companyId]))
            ->for(MeasurementUnit::factory(['company_id' => $companyId, 'abbreviation' => 'un']), 'measurementUnit')
            ->create(['company_id' => $companyId]);
        $box = Presentation::factory()->create([
            'company_id' => $companyId,
            'name' => 'Caja x 10',
            'units_per_package' => 10,
        ]);
        $inventory = app(InventoryService::class);

        $inventory->register([
            'operation' => 'in',
            'warehouse_id' => $warehouse->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'presentation_id' => $box->id,
                    'package_quantity' => 1,
                ],
            ],
        ], $user->id);

        $inventory->defragmentPackage([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'presentation_id' => $box->id,
            'package_quantity' => 2,
        ], $user->id);
    }
}
