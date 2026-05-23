<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryLocationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_branch(): void
    {
        $user = $this->userWithPermissions(['branches.create']);

        $response = $this
            ->actingAs($user)
            ->post(route('branches.store'), [
                'name' => 'Sucursal Central',
                'code' => 'CENTRAL',
                'phone' => '77777777',
                'address' => 'Av. Principal 123',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', [
            'name' => 'Sucursal Central',
            'code' => 'CENTRAL',
            'is_active' => true,
        ]);
    }

    public function test_user_can_create_a_warehouse_for_a_branch(): void
    {
        $user = $this->userWithPermissions(['warehouses.create']);
        $branch = Branch::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('warehouses.store'), [
                'branch_id' => $branch->id,
                'name' => 'Almacen principal',
                'code' => 'ALM-CENTRAL',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', [
            'branch_id' => $branch->id,
            'name' => 'Almacen principal',
            'code' => 'ALM-CENTRAL',
        ]);
    }

    public function test_deleting_a_branch_soft_deletes_its_warehouses(): void
    {
        $user = $this->userWithPermissions(['branches.delete']);
        $branch = Branch::factory()
            ->has(Warehouse::factory()->count(2))
            ->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('branches.destroy', $branch));

        $response->assertRedirect(route('branches.index'));
        $this->assertSoftDeleted('branches', ['id' => $branch->id]);

        foreach ($branch->warehouses as $warehouse) {
            $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id]);
        }
    }

    /**
     * @param array<int, string> $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }
}
