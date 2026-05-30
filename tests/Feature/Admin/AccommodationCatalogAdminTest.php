<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\PrivateSpaceType;
use App\Models\Space;
use App\Models\SpaceMode;
use App\Models\User;
use Database\Seeders\AccommodationCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccommodationCatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_global_super_admin_can_open_accommodation_catalogs(): void
    {
        $permission = Permission::findOrCreate('accommodation-catalogs.manage');
        $role = Role::findOrCreate('super_admin');
        $role->givePermissionTo($permission);

        $companyUser = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $companyUser->assignRole($role);
        $companyUser->givePermissionTo($permission);

        $this
            ->actingAs($companyUser)
            ->get(route('admin.accommodation-catalogs.index', 'space-modes'))
            ->assertForbidden();

        $globalAdmin = User::factory()->create(['company_id' => null]);
        $globalAdmin->assignRole($role);
        $globalAdmin->givePermissionTo($permission);

        $this
            ->actingAs($globalAdmin)
            ->get(route('admin.accommodation-catalogs.index', 'space-modes'))
            ->assertOk()
            ->assertSee('Modalidades');

        $this
            ->actingAs($globalAdmin)
            ->get(route('admin.accommodation-catalogs.create', 'bed-types'))
            ->assertOk()
            ->assertSee('Capacidad');
    }

    public function test_super_admin_can_create_bed_type_with_capacity_and_generated_slug(): void
    {
        $admin = $this->globalSuperAdmin();

        $this
            ->actingAs($admin)
            ->post(route('admin.accommodation-catalogs.store', 'bed-types'), [
                'name' => 'Cama nido',
                'description' => 'Cama auxiliar',
                'capacity' => 1,
                'sort_order' => 30,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.accommodation-catalogs.index', 'bed-types'));

        $this->assertDatabaseHas('bed_types', [
            'name' => 'Cama nido',
            'slug' => 'cama-nido',
            'capacity' => 1,
            'is_active' => true,
        ]);
    }

    public function test_bed_type_capacity_is_required(): void
    {
        $admin = $this->globalSuperAdmin();

        $this
            ->actingAs($admin)
            ->post(route('admin.accommodation-catalogs.store', 'bed-types'), [
                'name' => 'Cama sin capacidad',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('capacity');
    }

    public function test_protected_space_modes_cannot_be_disabled(): void
    {
        $admin = $this->globalSuperAdmin();
        $this->seed(AccommodationCatalogSeeder::class);
        $privateMode = SpaceMode::where('slug', 'privado')->firstOrFail();

        $this
            ->actingAs($admin)
            ->patch(route('admin.accommodation-catalogs.toggle', ['space-modes', $privateMode->id]))
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($privateMode->refresh()->is_active);
    }

    public function test_used_catalog_record_cannot_be_deleted(): void
    {
        $admin = $this->globalSuperAdmin();
        $this->seed(AccommodationCatalogSeeder::class);

        $company = Company::factory()->create();
        $mode = SpaceMode::where('slug', 'privado')->firstOrFail();
        $type = PrivateSpaceType::where('slug', 'casa')->firstOrFail();

        Space::create([
            'company_id' => $company->id,
            'space_mode_id' => $mode->id,
            'private_space_type_id' => $type->id,
            'slug' => 'casa-demo',
            'status' => 'active',
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.accommodation-catalogs.destroy', ['private-space-types', $type->id]))
            ->assertRedirect()
            ->assertSessionHas('error', 'No se puede eliminar porque ya esta siendo usado.');

        $this->assertFalse($type->refresh()->trashed());
    }

    public function test_inactive_catalog_is_hidden_from_active_scope_but_visible_in_historical_relation(): void
    {
        $this->seed(AccommodationCatalogSeeder::class);

        $company = Company::factory()->create();
        $mode = SpaceMode::where('slug', 'privado')->firstOrFail();
        $type = PrivateSpaceType::where('slug', 'casa')->firstOrFail();
        $type->update(['is_active' => false]);

        $space = Space::create([
            'company_id' => $company->id,
            'space_mode_id' => $mode->id,
            'private_space_type_id' => $type->id,
            'slug' => 'historial-casa',
            'status' => 'inactive',
        ]);

        $this->assertFalse(PrivateSpaceType::active()->whereKey($type->id)->exists());
        $this->assertSame('Casa', $space->fresh()->privateSpaceType->name);
    }

    private function globalSuperAdmin(): User
    {
        $permission = Permission::findOrCreate('accommodation-catalogs.manage');
        $role = Role::findOrCreate('super_admin');
        $role->givePermissionTo($permission);

        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole($role);
        $user->givePermissionTo($permission);

        return $user;
    }
}
