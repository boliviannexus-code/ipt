<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Program;
use App\Models\ProgramGradingScheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class AcademicControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_only_sees_programs_from_the_active_company(): void
    {
        [$user, $program] = $this->context(['academic-control.view']);
        $otherCompany = Company::factory()->create();
        Program::withoutGlobalScope('company')->create(['company_id' => $otherCompany->id, 'title' => 'Francés', 'duration_months' => 12]);

        $this->actingAs($user)->get(route('academic.control.index'))
            ->assertOk()->assertSee($program->title)->assertDontSee('Francés');
    }

    public function test_new_program_starts_with_an_empty_version_one_draft(): void
    {
        [, $program] = $this->context();
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->with('components')->where('program_id', $program->id)->firstOrFail();

        $this->assertSame(1, $scheme->version);
        $this->assertSame('draft', $scheme->status);
        $this->assertFalse($scheme->is_active);
        $this->assertSame('51.00', $scheme->passing_score);
        $this->assertCount(0, $scheme->components);
    }

    public function test_configuration_builder_displays_dynamic_controls(): void
    {
        [$user, $program] = $this->context(['academic-control.view', 'academic-control.manage']);

        $this->actingAs($user)->get(route('academic.control.show', $program))
            ->assertOk()
            ->assertSee('Agregar ponderación')
            ->assertSee('Tipos de ponderación')
            ->assertSee('Nota mínima')
            ->assertSee('Método de ponderación')
            ->assertSee('Simple (0 o 1)')
            ->assertSee('Nueva ponderación')
            ->assertSee('No hay ponderaciones registradas')
            ->assertSee('data-confirm-title="¿Finalizar la configuración?"', false);
    }

    public function test_dynamic_daily_and_single_components_can_be_saved_and_reordered(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.manage']);

        $this->actingAs($user)->put(route('academic.control.update', [$program, $scheme]), $this->validPayload())->assertRedirect();

        $scheme->refresh()->load('components.skills');
        $this->assertSame(['Participación diaria', 'Evaluación final'], $scheme->components->pluck('name')->all());
        $this->assertSame('daily', $scheme->components[0]->frequency->value);
        $this->assertSame('single_skill', $scheme->components[0]->skill_mode->value);
        $this->assertSame('simple', $scheme->components[0]->scoring_method->value);
        $this->assertSame('percentage', $scheme->components[1]->scoring_method->value);
        $this->assertSame('multiple_skills', $scheme->components[1]->skill_mode->value);
        $this->assertSame(['Expresión oral', 'Comprensión'], $scheme->components[1]->skills->pluck('name')->all());

        $payload = $this->validPayload();
        $payload['components'] = array_reverse($payload['components']);
        $this->actingAs($user)->put(route('academic.control.update', [$program, $scheme]), $payload)->assertRedirect();
        $this->assertSame(['Evaluación final', 'Participación diaria'], $scheme->refresh()->components->pluck('name')->all());
    }

    public function test_duplicate_titles_and_skill_names_are_rejected(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.manage']);
        $payload = $this->validPayload();
        $payload['components'][1]['name'] = 'participación DIARIA';
        $payload['components'][1]['skills'][1]['name'] = 'EXPRESIÓN ORAL';

        $this->actingAs($user)->put(route('academic.control.update', [$program, $scheme]), $payload)
            ->assertSessionHasErrors(['components', 'components.1.skills']);
    }

    public function test_incomplete_totals_or_invalid_multiple_skills_prevent_finalization(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.manage']);
        $payload = $this->validPayload();
        $payload['components'][0]['weight'] = 30;
        $payload['components'][1]['skills'] = [['name' => 'Expresión oral', 'weight' => 100]];
        $this->actingAs($user)->put(route('academic.control.update', [$program, $scheme]), $payload)->assertRedirect();

        $this->actingAs($user)->post(route('academic.control.finalize', [$program, $scheme]))->assertSessionHasErrors('components');
        $this->assertSame('draft', $scheme->refresh()->status);
    }

    public function test_finalized_version_is_immutable_and_a_new_version_is_cloned(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.manage']);
        $this->actingAs($user)->put(route('academic.control.update', [$program, $scheme]), $this->validPayload())->assertRedirect();
        $this->actingAs($user)->post(route('academic.control.finalize', [$program, $scheme]))->assertRedirect();

        $this->assertSame('finalized', $scheme->refresh()->status);
        $this->assertTrue($scheme->is_active);
        $this->actingAs($user)->put(route('academic.control.update', [$program, $scheme]), $this->validPayload())
            ->assertSessionHasErrors('configuration');

        $this->actingAs($user)->post(route('academic.control.versions.store', $program))->assertRedirect();
        $versions = ProgramGradingScheme::withoutGlobalScope('company')->with('components.skills')->where('program_id', $program->id)->orderBy('version')->get();
        $this->assertCount(2, $versions);
        $this->assertSame('draft', $versions[1]->status);
        $this->assertFalse($versions[1]->is_active);
        $this->assertSame($versions[0]->components->pluck('name')->all(), $versions[1]->components->pluck('name')->all());
        $this->assertSame($versions[0]->components->pluck('scoring_method')->all(), $versions[1]->components->pluck('scoring_method')->all());
    }

    public function test_new_version_can_be_created_from_a_selected_previous_version(): void
    {
        [$user, $program, $firstVersion] = $this->context(['academic-control.view', 'academic-control.manage']);
        $this->actingAs($user)->put(route('academic.control.update', [$program, $firstVersion]), $this->validPayload());
        $this->actingAs($user)->post(route('academic.control.finalize', [$program, $firstVersion]));

        $this->actingAs($user)->get(route('academic.control.show', $program))
            ->assertOk()
            ->assertSee('Crear nueva versión')
            ->assertSee('Configuración de origen')
            ->assertSee('Confirmar y crear borrador');

        $this->actingAs($user)->post(route('academic.control.versions.store', $program));
        $secondVersion = $program->gradingSchemes()->where('version', 2)->firstOrFail();
        $secondPayload = $this->validPayload();
        $secondPayload['components'][0]['name'] = 'Configuración modificada';
        $this->actingAs($user)->put(route('academic.control.update', [$program, $secondVersion]), $secondPayload);
        $this->actingAs($user)->post(route('academic.control.finalize', [$program, $secondVersion]));

        $this->actingAs($user)->post(route('academic.control.versions.store', $program), [
            'source_scheme_id' => $firstVersion->id,
        ])->assertRedirect();

        $thirdVersion = $program->gradingSchemes()->with('components.skills')->where('version', 3)->firstOrFail();
        $this->assertSame('draft', $thirdVersion->status);
        $this->assertSame($firstVersion->refresh()->components->pluck('name')->all(), $thirdVersion->components->pluck('name')->all());
        $this->assertNotContains('Configuración modificada', $thirdVersion->components->pluck('name')->all());
    }

    public function test_empty_draft_can_be_deleted_and_recreated_for_a_program_without_weightings(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.view', 'academic-control.manage']);

        $this->actingAs($user)->get(route('academic.control.show', $program))
            ->assertOk()
            ->assertSee('Eliminar borrador')
            ->assertSee('data-confirm-title="¿Eliminar este borrador?"', false);

        $this->actingAs($user)->delete(route('academic.control.versions.destroy', [$program, $scheme]))
            ->assertRedirect(route('academic.control.index'));
        $this->assertDatabaseMissing('program_grading_schemes', ['id' => $scheme->id]);

        $this->actingAs($user)->get(route('academic.control.index'))
            ->assertOk()
            ->assertSee('Sin ponderaciones')
            ->assertSee('Crear ponderaciones')
            ->assertSee('Crear desde cero')
            ->assertSee('Confirmar y crear borrador');

        $this->actingAs($user)->post(route('academic.control.versions.store', $program))->assertRedirect();
        $newDraft = $program->gradingSchemes()->with('components')->sole();
        $this->assertSame('draft', $newDraft->status);
        $this->assertCount(0, $newDraft->components);
    }

    public function test_weightings_can_be_copied_between_programs_from_the_popup(): void
    {
        [$user, $sourceProgram, $sourceScheme] = $this->context(['academic-control.view', 'academic-control.manage']);
        $this->actingAs($user)->put(route('academic.control.update', [$sourceProgram, $sourceScheme]), $this->validPayload());
        $this->actingAs($user)->post(route('academic.control.finalize', [$sourceProgram, $sourceScheme]));

        $destinationProgram = Program::withoutGlobalScope('company')->create([
            'company_id' => $sourceProgram->company_id,
            'title' => 'Francés',
            'duration_months' => 12,
        ]);
        $destinationDraft = $destinationProgram->gradingSchemes()->sole();
        $this->actingAs($user)->delete(route('academic.control.versions.destroy', [$destinationProgram, $destinationDraft]));

        $this->actingAs($user)->get(route('academic.control.index'))
            ->assertOk()
            ->assertSee('Programa destino: Francés')
            ->assertSee('Inglés · Versión 1');

        $this->actingAs($user)->post(route('academic.control.versions.store', $destinationProgram), [
            'source_scheme_id' => $sourceScheme->id,
        ])->assertRedirect();

        $copiedScheme = $destinationProgram->gradingSchemes()->with('components.skills')->sole();
        $this->assertSame('draft', $copiedScheme->status);
        $this->assertSame($sourceScheme->refresh()->components->pluck('name')->all(), $copiedScheme->components->pluck('name')->all());
        $this->assertSame($sourceScheme->passing_score, $copiedScheme->passing_score);
    }

    public function test_finalized_configuration_cannot_be_deleted(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.manage']);
        $this->actingAs($user)->put(route('academic.control.update', [$program, $scheme]), $this->validPayload());
        $this->actingAs($user)->post(route('academic.control.finalize', [$program, $scheme]));

        $this->actingAs($user)->delete(route('academic.control.versions.destroy', [$program, $scheme]))
            ->assertSessionHasErrors('configuration');
        $this->assertDatabaseHas('program_grading_schemes', ['id' => $scheme->id, 'status' => 'finalized']);
    }

    public function test_cross_company_scheme_cannot_be_modified(): void
    {
        [$user, $program] = $this->context(['academic-control.manage']);
        $otherCompany = Company::factory()->create();
        $otherProgram = Program::withoutGlobalScope('company')->create(['company_id' => $otherCompany->id, 'title' => 'Otro', 'duration_months' => 4]);
        $otherScheme = ProgramGradingScheme::withoutGlobalScope('company')->where('program_id', $otherProgram->id)->firstOrFail();

        $this->actingAs($user)->put(route('academic.control.update', [$program, $otherScheme]), $this->validPayload())->assertNotFound();
    }

    private function context(array $permissions = []): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }
        $user->givePermissionTo($permissions);
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->where('program_id', $program->id)->firstOrFail();

        return [$user, $program, $scheme];
    }

    private function validPayload(): array
    {
        return ['passing_score' => 51, 'components' => [
            ['name' => 'Participación diaria', 'weight' => 40, 'frequency' => 'daily', 'skill_mode' => 'single_skill', 'scoring_method' => 'simple', 'skills' => [['name' => 'Participación', 'weight' => 100]]],
            ['name' => 'Evaluación final', 'weight' => 60, 'frequency' => 'single', 'skill_mode' => 'multiple_skills', 'scoring_method' => 'percentage', 'skills' => [
                ['name' => 'Expresión oral', 'weight' => 45],
                ['name' => 'Comprensión', 'weight' => 55],
            ]],
        ]];
    }
}
