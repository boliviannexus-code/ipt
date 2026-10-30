<?php

namespace Tests\Feature\Access;

use App\Models\Company;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MenuPermissionAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_menu_link_is_visible_with_its_route_permissions_alone(): void
    {
        PermissionCatalog::register();
        $user = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        // The teacher menu also requires a linked teacher profile.
        $user->setAttribute('personnel_id', 1);
        $this->actingAs($user);
        $source = file_get_contents(resource_path('views/layouts/partials/sidebar.blade.php'));
        preg_match_all("/route\('([^']+)'\)/", $source, $matches);
        foreach (array_unique($matches[1]) as $name) {
            if ($name === 'super-admin.contracts.index') {
                continue; // Reserved to the global administrator by a separate middleware.
            }
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $permissions = $name === 'dashboard' ? ['dashboard.view'] : $this->routePermissions($name);
            $this->assertNotEmpty($permissions, "Menu route without configurable permission: {$name}");
            $user->syncPermissions($permissions);
            $html = view('layouts.partials.sidebar')->render();
            $this->assertStringContainsString('href="'.route($name).'"', $html, "Hidden menu link: {$name}");
        }
    }

    public function test_all_route_and_menu_permissions_exist_in_the_catalog_and_role_form(): void
    {
        PermissionCatalog::register();
        foreach (Route::getRoutes() as $route) {
            foreach ($this->middlewarePermissions($route->gatherMiddleware()) as $permission) {
                $this->assertContains($permission, PermissionCatalog::NAMES, $route->getName() ?? $route->uri());
            }
        }
        $source = file_get_contents(resource_path('views/layouts/partials/sidebar.blade.php'));
        preg_match_all("/(?:can\\(|@can\\()'([^']+)'/", $source, $matches);
        foreach ($matches[1] as $name) {
            $this->assertContains($name, PermissionCatalog::NAMES);
        }
        $actor = User::factory()->create();
        $actor->givePermissionTo('roles.assign-permissions');
        $role = Role::findOrCreate('audit-role', 'web');
        $response = $this->actingAs($actor)->get(route('roles.permissions.form', $role))->assertOk();
        foreach (PermissionCatalog::NAMES as $name) {
            $response->assertSee('value="'.$name.'"', false);
        }
        $this->patch(route('roles.permissions', $role), ['permissions' => PermissionCatalog::NAMES])->assertRedirect();
        $this->assertEqualsCanonicalizing(PermissionCatalog::NAMES, $role->fresh()->permissions->pluck('name')->all());
        $this->patch(route('roles.permissions', $role), ['permissions' => []])->assertRedirect();
        $this->assertCount(0, $role->fresh()->permissions);
    }

    public function test_consultation_permissions_do_not_require_management_permissions(): void
    {
        PermissionCatalog::register();
        $user = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $this->actingAs($user);
        foreach (['areas', 'positions', 'personnel', 'permissions'] as $module) {
            $user->syncPermissions([$module.'.view']);
            $this->get(route($module.'.index'))->assertOk();
            $this->get(route($module.'.create'))->assertForbidden();
            $user->syncPermissions([]);
            $this->get(route($module.'.index'))->assertForbidden();
        }
    }

    public function test_registration_restores_missing_permissions_and_preserves_configured_access(): void
    {
        $role = Role::findOrCreate('configured-role', 'web');
        $custom = Permission::findOrCreate('custom.view', 'web');
        $role->givePermissionTo($custom);
        Permission::where('name', 'enrollment-reports.view')->delete();
        PermissionCatalog::register();
        PermissionCatalog::register();
        $this->assertTrue(Permission::where('name', 'enrollment-reports.view')->exists());
        $this->assertSame(['custom.view'], $role->fresh()->permissions->pluck('name')->all());
        $this->assertSame(count(PermissionCatalog::NAMES), Permission::whereIn('name', PermissionCatalog::NAMES)->count());
    }

    public function test_system_permissions_cannot_be_renamed_or_deleted_but_custom_permissions_can(): void
    {
        PermissionCatalog::register();
        $actor = User::factory()->create();
        $actor->givePermissionTo(['permissions.edit', 'permissions.delete']);
        $system = Permission::findByName('academic-control.view', 'web');
        $this->actingAs($actor)->putJson(route('permissions.update', $system), ['name' => 'renamed.view'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->deleteJson(route('permissions.destroy', $system), [], ['X-Requested-With' => 'XMLHttpRequest'])->assertUnprocessable();
        $this->assertSame('academic-control.view', $system->fresh()->name);
        $custom = Permission::findOrCreate('custom.view', 'web');
        $this->put(route('permissions.update', $custom), ['name' => 'custom.edit'])->assertRedirect();
        $this->delete(route('permissions.destroy', $custom))->assertRedirect();
        $this->assertDatabaseMissing('permissions', ['id' => $custom->id]);
    }

    public function test_selected_company_is_recognized_in_the_global_administrator_menu(): void
    {
        PermissionCatalog::register();
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
        $user->givePermissionTo(['sin-api-tokens.view', 'siat-communication.view', 'siat-cuis.view', 'siat-catalogs.view', 'siat-branches.view', 'invoice-tests.run']);
        $response = $this->actingAs($user)->withSession(['active_company_id' => $company->id])
            ->get(route('account.edit'))->assertOk();
        foreach (['sin-api-token.index', 'siat.communication.index', 'siat.cuis.index', 'siat.catalogs.index', 'siat.branches.index', 'billing.invoice-tests.index'] as $name) {
            $response->assertSee('href="'.route($name).'"', false);
        }
        $response->assertDontSee('href="'.route('billing.invoices.issue.index').'"', false);
    }

    public function test_resource_actions_have_only_their_required_permission(): void
    {
        foreach (['areas', 'positions', 'personnel'] as $module) {
            foreach (['index', 'create', 'store', 'edit', 'update', 'destroy'] as $action) {
                $this->assertSame([$module.($action === 'index' ? '.view' : '.manage')], $this->routePermissions($module.'.'.$action));
            }
        }
        foreach (['index' => 'view', 'show' => 'view', 'create' => 'create', 'store' => 'create', 'edit' => 'edit', 'update' => 'edit', 'destroy' => 'delete'] as $action => $permission) {
            $this->assertSame(['permissions.'.$permission], $this->routePermissions('permissions.'.$action));
        }
    }

    public function test_permission_catalog_can_be_searched_by_menu_name(): void
    {
        PermissionCatalog::register();
        $actor = User::factory()->create();
        $actor->givePermissionTo('permissions.view');
        $query = http_build_query([
            'draw' => 1, 'start' => 0, 'length' => 15,
            'search' => ['value' => 'Ponderaciones', 'regex' => false],
            'columns' => [['data' => 'name', 'name' => 'name', 'searchable' => 'true', 'orderable' => 'true']],
        ]);
        $response = $this->actingAs($actor)->getJson(route('datatables.permissions').'?'.$query)->assertOk();
        $this->assertSame(2, $response->json('recordsFiltered'));
        $this->assertEqualsCanonicalizing(
            ['academic-control.view', 'academic-control.manage'],
            Permission::whereIn('id', array_column($response->json('data'), 'id'))->pluck('name')->all(),
        );
    }

    public function test_unchecking_every_permission_in_the_role_edit_form_clears_access(): void
    {
        PermissionCatalog::register();
        $actor = User::factory()->create();
        $actor->givePermissionTo(['roles.edit', 'roles.assign-permissions']);
        $role = Role::findOrCreate('configured-role', 'web');
        $role->givePermissionTo('students.view');
        $this->actingAs($actor)->put(route('roles.update', $role), ['name' => $role->name, 'permissions_submitted' => '1'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertCount(0, $role->fresh()->permissions);
        $role->givePermissionTo('students.view');
        $this->put(route('roles.update', $role), ['name' => $role->name])->assertRedirect();
        $this->assertTrue($role->fresh()->hasPermissionTo('students.view'));
    }

    public function test_creating_or_editing_a_role_cannot_bypass_the_assign_permissions_access(): void
    {
        PermissionCatalog::register();
        $actor = User::factory()->create();
        $actor->givePermissionTo(['roles.create', 'roles.edit']);
        $role = Role::findOrCreate('target-role', 'web');
        $this->actingAs($actor)->post(route('roles.store'), ['name' => 'unauthorized-role', 'permissions' => ['roles.assign-permissions']])
            ->assertForbidden();
        $this->put(route('roles.update', $role), ['name' => $role->name, 'permissions' => ['roles.assign-permissions']])
            ->assertForbidden();
        $this->get(route('roles.edit', $role))->assertOk()->assertDontSee('data-permission-matrix', false);
        $this->put(route('roles.update', $role), ['name' => 'renamed-role'])->assertRedirect();
        $this->assertCount(0, $role->fresh()->permissions);
    }

    private function routePermissions(string $name): array
    {
        return $this->middlewarePermissions(Route::getRoutes()->getByName($name)->gatherMiddleware());
    }

    private function middlewarePermissions(array $middleware): array
    {
        $names = [];
        foreach ($middleware as $item) {
            if (str_starts_with($item, 'permission:')) {
                array_push($names, ...explode('|', explode(',', substr($item, strlen('permission:')))[0]));
            }
        }

        return array_values(array_unique($names));
    }
}
