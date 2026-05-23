<?php

namespace Tests\Feature\Roles;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_and_permission_labels_are_displayed_in_spanish_without_changing_values(): void
    {
        $actor = User::factory()->create();
        Permission::findOrCreate('roles.assign-permissions');
        Permission::findOrCreate('categories.view');
        $actor->givePermissionTo('roles.assign-permissions');

        $role = Role::findOrCreate('manager');
        $role->givePermissionTo('categories.view');

        $response = $this
            ->actingAs($actor)
            ->get(route('roles.permissions.form', $role));

        $response->assertOk();
        $response->assertSee('Gerente');
        $response->assertSee('Categorias');
        $response->assertSee('Categorias: Ver');
        $response->assertSee('value="categories.view"', false);
    }

    public function test_role_permissions_can_be_saved_without_role_name(): void
    {
        $actor = User::factory()->create();
        Permission::findOrCreate('roles.assign-permissions');
        $actor->givePermissionTo('roles.assign-permissions');

        $role = Role::findOrCreate('manager');
        Permission::findOrCreate('categories.view');

        $response = $this
            ->actingAs($actor)
            ->patch(route('roles.permissions', $role), [
                'permissions' => ['categories.view'],
            ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertTrue($role->fresh()->hasPermissionTo('categories.view'));
    }

    public function test_all_category_permissions_can_be_removed_from_super_admin(): void
    {
        $actor = User::factory()->create();
        $requiredPermissions = [
            'roles.assign-permissions',
            'roles.view',
            'roles.edit',
        ];
        $categoryPermissions = [
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.update',
            'categories.delete',
        ];

        foreach ([...$requiredPermissions, ...$categoryPermissions] as $permission) {
            Permission::findOrCreate($permission);
        }

        $actor->givePermissionTo('roles.assign-permissions');

        $role = Role::findOrCreate('super_admin');
        $role->syncPermissions([...$requiredPermissions, ...$categoryPermissions]);

        $response = $this
            ->actingAs($actor)
            ->patch(route('roles.permissions', $role), [
                'permissions' => $requiredPermissions,
            ]);

        $response->assertRedirect(route('roles.index'));

        $role->refresh();
        $this->assertTrue($role->hasAllPermissions($requiredPermissions));
        $this->assertFalse($role->hasAnyPermission($categoryPermissions));
    }
}
