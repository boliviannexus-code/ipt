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

class WarehouseTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_can_transfer_stock_between_own_warehouses(): void
    {
        Permission::findOrCreate('inventory.movements');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('inventory.movements');
        $source = $this->warehouseFor($company, 'Origen');
        $target = $this->warehouseFor($company, 'Destino');
        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Producto transferible']);

        app(InventoryService::class)->register([
            'operation' => 'in',
            'warehouse_id' => $source->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10],
            ],
        ], $user->id);

        $this
            ->actingAs($user)
            ->post(route('inventory.transfers.store'), [
                'operation' => 'transfer',
                'source_warehouse_id' => $source->id,
                'target_warehouse_id' => $target->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 4],
                ],
                'notes' => 'Reposicion entre almacenes',
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $source->id,
            'type' => InventoryMovementType::TransferOut->value,
            'quantity' => -4,
            'reference_type' => 'warehouse_transfer',
            'notes' => 'Reposicion entre almacenes',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $target->id,
            'type' => InventoryMovementType::TransferIn->value,
            'quantity' => 4,
            'reference_type' => 'warehouse_transfer',
            'notes' => 'Reposicion entre almacenes',
        ]);

        $this->assertSame(6, (int) $product->inventoryMovements()->where('warehouse_id', $source->id)->sum('quantity'));
        $this->assertSame(4, (int) $product->inventoryMovements()->where('warehouse_id', $target->id)->sum('quantity'));
    }

    public function test_transfer_form_only_lists_company_scoped_resources(): void
    {
        Permission::findOrCreate('inventory.movements');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('inventory.movements');
        $ownWarehouse = $this->warehouseFor($company, 'Almacen propio');
        $otherWarehouse = $this->warehouseFor($otherCompany, 'Almacen ajeno');
        Product::factory()->create(['company_id' => $company->id, 'name' => 'Producto propio']);
        Product::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Producto ajeno']);

        $this
            ->actingAs($user)
            ->get(route('inventory.transfers.create'))
            ->assertOk()
            ->assertSee('Almacen propio')
            ->assertSee('Producto propio')
            ->assertDontSee('Almacen ajeno')
            ->assertDontSee('Producto ajeno');

        $this->assertDatabaseHas('warehouses', ['id' => $ownWarehouse->id]);
        $this->assertDatabaseHas('warehouses', ['id' => $otherWarehouse->id]);
    }

    public function test_base_transfer_can_use_unit_presentation_stock(): void
    {
        Permission::findOrCreate('inventory.movements');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('inventory.movements');
        $source = $this->warehouseFor($company, 'Origen');
        $target = $this->warehouseFor($company, 'Destino');
        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Producto unidad']);
        $unitPresentation = Presentation::factory()->create([
            'company_id' => $company->id,
            'name' => 'Unidad',
            'units_per_package' => 1,
        ]);

        app(InventoryService::class)->register([
            'operation' => 'in',
            'warehouse_id' => $source->id,
            'items' => [
                ['product_id' => $product->id, 'presentation_id' => $unitPresentation->id, 'package_quantity' => 100],
            ],
        ], $user->id);

        $this
            ->actingAs($user)
            ->post(route('inventory.transfers.store'), [
                'operation' => 'transfer',
                'source_warehouse_id' => $source->id,
                'target_warehouse_id' => $target->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 40],
                ],
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $source->id,
            'presentation_id' => $unitPresentation->id,
            'type' => InventoryMovementType::TransferOut->value,
            'quantity' => -40,
            'package_quantity' => -40,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $target->id,
            'presentation_id' => $unitPresentation->id,
            'type' => InventoryMovementType::TransferIn->value,
            'quantity' => 40,
            'package_quantity' => 40,
        ]);
    }

    public function test_company_user_cannot_transfer_stock_to_other_company_warehouse(): void
    {
        Permission::findOrCreate('inventory.movements');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('inventory.movements');
        $source = $this->warehouseFor($company, 'Origen propio');
        $target = $this->warehouseFor($otherCompany, 'Destino ajeno');
        $product = Product::factory()->create(['company_id' => $company->id]);

        app(InventoryService::class)->register([
            'operation' => 'in',
            'warehouse_id' => $source->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10],
            ],
        ], $user->id);

        $this
            ->actingAs($user)
            ->post(route('inventory.transfers.store'), [
                'operation' => 'transfer',
                'source_warehouse_id' => $source->id,
                'target_warehouse_id' => $target->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 4],
                ],
            ])
            ->assertSessionHasErrors('target_warehouse_id');

        $this->assertDatabaseMissing('inventory_movements', [
            'warehouse_id' => $target->id,
            'product_id' => $product->id,
            'type' => InventoryMovementType::TransferIn->value,
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
