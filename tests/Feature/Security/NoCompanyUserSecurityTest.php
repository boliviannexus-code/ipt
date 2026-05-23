<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PointOfSale;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NoCompanyUserSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_company_and_without_super_admin_cannot_operate_company_data(): void
    {
        foreach (['products.view', 'products.create', 'point-of-sales.create'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $user = User::factory()->create(['company_id' => null]);
        $user->givePermissionTo(['products.view', 'products.create', 'point-of-sales.create']);

        $this->assertFalse(CompanyContext::canOperate($user));
        $this->assertSame(-1, CompanyContext::id($user));

        $this
            ->actingAs($user)
            ->get(route('point-of-sales.create'))
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->postJson(route('products.store'), [
                'name' => 'Producto flotante bloqueado',
                'barcode' => 'NO-COMPANY-001',
                'category_id' => 1,
                'measurement_unit_id' => 1,
                'purchase_price' => 10,
                'sale_price' => 15,
                'minimum_stock' => 1,
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_point_of_sale_assignment_only_lists_users_from_actor_company(): void
    {
        Permission::findOrCreate('point-of-sales.create');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        $actor->givePermissionTo('point-of-sales.create');

        $ownUser = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'pos-propio@example.test',
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'email' => 'pos-ajeno@example.test',
            'is_active' => true,
        ]);

        $branch = Branch::factory()->for($company)->create();
        Warehouse::factory()->for($branch)->create(['company_id' => $company->id]);
        Branch::factory()->for($otherCompany)->create();

        $this
            ->actingAs($actor)
            ->get(route('point-of-sales.create'))
            ->assertOk()
            ->assertSee($ownUser->email)
            ->assertDontSee($otherUser->email);
    }

    public function test_super_admin_without_company_keeps_global_access(): void
    {
        Permission::findOrCreate('point-of-sales.create');
        Role::findOrCreate('super_admin')->givePermissionTo('point-of-sales.create');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $superAdmin = User::factory()->create(['company_id' => null]);
        $superAdmin->assignRole('super_admin');

        $ownUser = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'global-uno@example.test',
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'email' => 'global-dos@example.test',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::factory()->for(Branch::factory()->for($company))->create(['company_id' => $company->id]);
        PointOfSale::factory()->forWarehouse($warehouse->id)->create();

        $this->assertTrue(CompanyContext::canOperate($superAdmin));
        $this->assertNull(CompanyContext::id($superAdmin));

        $this
            ->actingAs($superAdmin)
            ->get(route('point-of-sales.create'))
            ->assertOk()
            ->assertSee($ownUser->email)
            ->assertSee($otherUser->email);
    }

    public function test_super_admin_with_company_can_assign_no_company_but_keeps_company_filtered_context(): void
    {
        foreach (['users.view', 'users.create'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $company = Company::factory()->create(['name' => 'Empresa Principal']);
        $otherCompany = Company::factory()->create(['name' => 'Empresa Visible']);
        Role::findOrCreate('super_admin')->givePermissionTo(['users.view', 'users.create']);

        $superAdmin = User::factory()->create(['company_id' => $company->id]);
        $superAdmin->assignRole('super_admin');

        $this->assertTrue(CompanyContext::canOperate($superAdmin));
        $this->assertSame($company->id, CompanyContext::id($superAdmin));

        $this
            ->actingAs($superAdmin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Sin empresa')
            ->assertSee('Empresa Principal')
            ->assertDontSee('Empresa Visible');

        $this
            ->actingAs($superAdmin)
            ->post(route('users.store'), [
                'company_id' => null,
                'name' => 'Usuario sin empresa',
                'email' => 'sin-empresa@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'sin-empresa@example.test',
            'company_id' => null,
        ]);

        $this->assertDatabaseHas('companies', ['id' => $otherCompany->id]);
    }

    public function test_non_super_admin_cannot_assign_user_without_company(): void
    {
        foreach (['users.view', 'users.create'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $company = Company::factory()->create(['name' => 'Empresa Local']);
        $actor = User::factory()->create(['company_id' => $company->id]);
        $actor->givePermissionTo(['users.view', 'users.create']);

        $this
            ->actingAs($actor)
            ->get(route('users.create'))
            ->assertOk()
            ->assertDontSee('Sin empresa')
            ->assertSee('Empresa Local');

        $this
            ->actingAs($actor)
            ->post(route('users.store'), [
                'company_id' => null,
                'name' => 'Usuario bloqueado',
                'email' => 'bloqueado-sin-empresa@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('company_id');

        $this->assertDatabaseMissing('users', [
            'email' => 'bloqueado-sin-empresa@example.test',
        ]);
    }

    public function test_changing_user_company_removes_point_of_sale_assignments_from_previous_company(): void
    {
        Permission::findOrCreate('users.edit');
        Role::findOrCreate('super_admin')->givePermissionTo('users.edit');

        $oldCompany = Company::factory()->create();
        $newCompany = Company::factory()->create();
        $superAdmin = User::factory()->create(['company_id' => null]);
        $superAdmin->assignRole('super_admin');
        $targetUser = User::factory()->create([
            'company_id' => $oldCompany->id,
            'name' => 'Cajero reasignado',
            'email' => 'cajero-reasignado@example.test',
        ]);

        $oldWarehouse = Warehouse::factory()
            ->for(Branch::factory()->for($oldCompany))
            ->create(['company_id' => $oldCompany->id]);
        $newWarehouse = Warehouse::factory()
            ->for(Branch::factory()->for($newCompany))
            ->create(['company_id' => $newCompany->id]);
        $oldPointOfSale = PointOfSale::factory()->forWarehouse($oldWarehouse->id)->create();
        $newPointOfSale = PointOfSale::factory()->forWarehouse($newWarehouse->id)->create();
        $oldPointOfSale->users()->sync([$targetUser->id]);
        $newPointOfSale->users()->sync([$targetUser->id]);

        $this
            ->actingAs($superAdmin)
            ->put(route('users.update', $targetUser), [
                'company_id' => $newCompany->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('point_of_sale_user', [
            'point_of_sale_id' => $oldPointOfSale->id,
            'user_id' => $targetUser->id,
        ]);
        $this->assertDatabaseHas('point_of_sale_user', [
            'point_of_sale_id' => $newPointOfSale->id,
            'user_id' => $targetUser->id,
        ]);
    }
}
