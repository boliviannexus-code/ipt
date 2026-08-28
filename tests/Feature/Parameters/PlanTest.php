<?php

namespace Tests\Feature\Parameters;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_can_create_and_list_only_their_plans(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('plans.view');
        Permission::findOrCreate('plans.create');
        $user->givePermissionTo(['plans.view', 'plans.create']);
        Plan::withoutGlobalScope('company')->create(['company_id' => $otherCompany->id, 'name' => 'Plan ajeno', 'monthly_cost' => 999]);

        $this->actingAs($user)
            ->post(route('parameters.plans.store'), ['name' => 'Plan Regular', 'monthly_cost' => '250.50'])
            ->assertRedirect(route('parameters.plans.index'));

        $this->assertDatabaseHas('plans', ['company_id' => $company->id, 'name' => 'Plan Regular', 'monthly_cost' => '250.50']);

        $this->actingAs($user)
            ->get(route('parameters.plans.index'))
            ->assertOk()
            ->assertSee('Plan Regular')
            ->assertSee('250,50')
            ->assertDontSee('Plan ajeno');
    }

    public function test_plan_name_is_unique_inside_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('plans.create');
        $user->givePermissionTo('plans.create');
        Plan::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Mensual', 'monthly_cost' => 100]);

        $this->actingAs($user)
            ->post(route('parameters.plans.store'), ['name' => 'Mensual', 'monthly_cost' => 120])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Plan::withoutGlobalScope('company')->where('company_id', $company->id)->count());
    }

    public function test_create_and_edit_forms_load_in_modal_and_plan_can_be_updated(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('plans.create');
        Permission::findOrCreate('plans.edit');
        $user->givePermissionTo(['plans.create', 'plans.edit']);
        $plan = Plan::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Inicial', 'monthly_cost' => 100]);

        $this->actingAs($user)->get(route('parameters.plans.create'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('data-ajax-form', false);
        $this->actingAs($user)->get(route('parameters.plans.edit', $plan), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('Inicial');

        $this->actingAs($user)
            ->putJson(route('parameters.plans.update', $plan), ['name' => 'Actualizado', 'monthly_cost' => '180.75'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Actualizado', 'monthly_cost' => '180.75']);
    }
}
