<?php

namespace Tests\Feature\Parameters;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_can_be_created_and_updated_with_multiple_plans(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('programs.view');
        Permission::findOrCreate('programs.create');
        Permission::findOrCreate('programs.edit');
        $user->givePermissionTo(['programs.view', 'programs.create', 'programs.edit']);
        $plans = collect([
            Plan::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Contado', 'monthly_cost' => 300]),
            Plan::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Mensual', 'monthly_cost' => 100]),
        ]);

        $this->actingAs($user)->post(route('parameters.programs.store'), [
            'title' => 'Programa técnico',
            'enrollment_code' => 'cap',
            'duration_months' => 18,
            'plan_ids' => $plans->pluck('id')->all(),
        ])->assertRedirect(route('parameters.programs.index'));

        $program = Program::withoutGlobalScope('company')->sole();
        $this->assertSame('CAP', $program->enrollment_code);
        $this->assertCount(2, $program->plans);

        $this->actingAs($user)->put(route('parameters.programs.update', $program), [
            'title' => 'Programa actualizado',
            'enrollment_code' => 'tec',
            'duration_months' => 24,
            'plan_ids' => [$plans->first()->id],
        ])->assertRedirect(route('parameters.programs.index'));

        $this->assertDatabaseHas('programs', ['id' => $program->id, 'title' => 'Programa actualizado', 'enrollment_code' => 'TEC', 'duration_months' => 24]);
        $this->assertSame([$plans->first()->id], $program->fresh()->plans->pluck('id')->all());
    }

    public function test_levels_are_configured_independently_for_each_program(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('programs.edit');
        $user->givePermissionTo('programs.edit');
        $firstProgram = Program::withoutGlobalScope('company')->create([
            'company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12,
        ]);
        $secondProgram = Program::withoutGlobalScope('company')->create([
            'company_id' => $company->id, 'title' => 'Francés', 'duration_months' => 12,
        ]);

        $this->actingAs($user)
            ->get(route('parameters.programs.levels.index', $firstProgram))
            ->assertOk()
            ->assertSee('Niveles del programa')
            ->assertSee('Agregar nivel');

        $this->actingAs($user)->post(route('parameters.programs.levels.store', $firstProgram), [
            'name' => 'básico 1',
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('parameters.programs.levels.store', $firstProgram), [
            'name' => 'Básico 2',
        ])->assertSessionHasNoErrors();

        $levels = ProgramLevel::withoutGlobalScope('company')->orderBy('position')->get();
        $this->assertSame(['Básico 1', 'Básico 2'], $levels->pluck('name')->all());
        $this->assertSame([1, 2], $levels->pluck('position')->all());

        $this->actingAs($user)->post(route('parameters.programs.levels.store', $firstProgram), [
            'name' => 'BÁSICO 1',
        ])->assertSessionHasErrors('name');

        $this->actingAs($user)->post(route('parameters.programs.levels.store', $secondProgram), [
            'name' => 'Básico 1',
        ])->assertSessionHasNoErrors();

        $firstLevel = $levels->first();
        $this->actingAs($user)->put(route('parameters.programs.levels.update', [$firstProgram, $firstLevel]), [
            'name' => 'Inicial', 'position' => 3, 'is_active' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('program_levels', [
            'id' => $firstLevel->id, 'name' => 'Inicial', 'position' => 3, 'is_active' => false,
        ]);

        $secondLevel = $levels->last();
        $this->actingAs($user)
            ->delete(route('parameters.programs.levels.destroy', [$firstProgram, $secondLevel]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('program_levels', ['id' => $secondLevel->id]);
    }
}
