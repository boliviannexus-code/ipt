<?php

namespace Tests\Feature\Parameters;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_selects_a_program_and_creates_its_plan(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('plans.view');
        Permission::findOrCreate('plans.create');
        $user->givePermissionTo(['plans.view', 'plans.create']);
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        Program::withoutGlobalScope('company')->create(['company_id' => $otherCompany->id, 'title' => 'Programa ajeno', 'duration_months' => 12]);

        $this->actingAs($user)
            ->post(route('parameters.plans.store', $program), ['name' => 'Plan Regular', 'monthly_cost' => '250.50'])
            ->assertRedirect(route('parameters.plans.show', $program));

        $this->assertDatabaseHas('plans', ['company_id' => $company->id, 'name' => 'Plan Regular', 'monthly_cost' => '250.50']);

        $this->actingAs($user)
            ->get(route('parameters.plans.index'))
            ->assertOk()
            ->assertSee('Inglés')
            ->assertDontSee('Programa ajeno');

        $this->actingAs($user)
            ->get(route('parameters.plans.show', $program))
            ->assertOk()
            ->assertSee('Plan Regular')
            ->assertSee('250,50');

        $this->assertTrue($program->plans()->whereKey(Plan::query()->sole()->id)->exists());
    }

    public function test_plan_name_is_unique_inside_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('plans.create');
        $user->givePermissionTo('plans.create');
        Plan::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Mensual', 'monthly_cost' => 100]);
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);

        $this->actingAs($user)
            ->post(route('parameters.plans.store', $program), ['name' => 'Mensual', 'monthly_cost' => 120])
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
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        $program->plans()->attach($plan);

        $this->actingAs($user)->get(route('parameters.plans.create', $program), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('data-ajax-form', false);
        $this->actingAs($user)->get(route('parameters.plans.edit', [$program, $plan]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('Inicial');

        $this->actingAs($user)
            ->putJson(route('parameters.plans.update', [$program, $plan]), ['name' => 'Actualizado', 'monthly_cost' => '180.75'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Actualizado', 'monthly_cost' => '180.75']);
    }
}
