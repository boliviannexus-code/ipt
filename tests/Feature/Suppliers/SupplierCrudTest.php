<?php

namespace Tests\Feature\Suppliers;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SupplierCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_suppliers(): void
    {
        $user = User::factory()->create();
        $permissions = [
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        $this
            ->actingAs($user)
            ->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Proveedores');

        $createResponse = $this
            ->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('suppliers.store'), [
                'name' => 'Proveedor Demo',
                'company_name' => 'Empresa Demo SRL',
                'email' => 'proveedor@example.test',
                'phone' => '70000000',
                'address' => 'Av. Principal 123',
                'is_active' => true,
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('success', true);

        $supplier = Supplier::query()->where('company_name', 'Empresa Demo SRL')->firstOrFail();

        $this
            ->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->putJson(route('suppliers.update', $supplier), [
                'name' => 'Proveedor Actualizado',
                'company_name' => 'Empresa Actualizada SRL',
                'email' => 'proveedor.actualizado@example.test',
                'phone' => '71111111',
                'address' => 'Calle Actualizada 456',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Proveedor Actualizado',
            'company_name' => 'Empresa Actualizada SRL',
            'is_active' => false,
        ]);

        $this
            ->actingAs($user)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'));

        $this->assertSoftDeleted('suppliers', [
            'id' => $supplier->id,
        ]);
    }
}
