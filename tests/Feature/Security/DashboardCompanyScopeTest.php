<?php

namespace Tests\Feature\Security;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardCompanyScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_dashboard_is_scoped_to_their_company(): void
    {
        Permission::findOrCreate('dashboard.view');

        $company = Company::factory()->create([
            'name' => 'Empresa Local',
            'logo_path' => 'companies/local-logo.png',
        ]);
        $otherCompany = Company::factory()->create(['name' => 'Empresa Ajena']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('dashboard.view');

        Product::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        Product::factory()->create(['company_id' => $otherCompany->id, 'is_active' => true]);
        Category::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        Category::factory()->create(['company_id' => $otherCompany->id, 'is_active' => true]);

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Empresa Local')
            ->assertSee('companies/local-logo.png')
            ->assertSee('Contexto de empresa')
            ->assertSee('<div class="h2 mb-0">1</div>', false)
            ->assertDontSee('<div class="h2 mb-0">2</div>', false)
            ->assertDontSee('Empresa Ajena');
    }

    public function test_global_super_admin_dashboard_sees_all_companies(): void
    {
        Permission::findOrCreate('dashboard.view');
        Role::findOrCreate('super_admin')->givePermissionTo('dashboard.view');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super_admin');

        Product::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        Product::factory()->create(['company_id' => $otherCompany->id, 'is_active' => true]);
        Category::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        Category::factory()->create(['company_id' => $otherCompany->id, 'is_active' => true]);

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Todas las empresas')
            ->assertSee('Contexto global')
            ->assertSee('<div class="h2 mb-0">2</div>', false);
    }

    public function test_super_admin_with_company_dashboard_is_scoped_to_assigned_company(): void
    {
        Permission::findOrCreate('dashboard.view');
        Role::findOrCreate('super_admin')->givePermissionTo('dashboard.view');

        $company = Company::factory()->create(['name' => 'Empresa Asignada']);
        $otherCompany = Company::factory()->create(['name' => 'Empresa Global No Visible']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('super_admin');

        Product::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        Product::factory()->create(['company_id' => $otherCompany->id, 'is_active' => true]);
        Category::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        Category::factory()->create(['company_id' => $otherCompany->id, 'is_active' => true]);

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Empresa Asignada')
            ->assertSee('Contexto de empresa')
            ->assertSee('<div class="h2 mb-0">1</div>', false)
            ->assertDontSee('<div class="h2 mb-0">2</div>', false)
            ->assertDontSee('Empresa Global No Visible');
    }
}
