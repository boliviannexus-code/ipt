<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicModule;
use App\Models\Area;
use App\Models\ClassSession;
use App\Models\Company;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Program;
use App\Models\ProgramGradingScheme;
use App\Models\ProgramLevel;
use App\Models\User;
use App\Services\Academic\ModuleCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function test_configuration_builder_displays_dynamic_controls(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.view', 'academic-control.manage']);

        $this->actingAs($user)->get(route('academic.weightings.show', $scheme))
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

        $this->actingAs($user)->put(route('academic.weightings.update', $scheme), $this->validPayload())->assertRedirect();

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
        $this->actingAs($user)->put(route('academic.weightings.update', $scheme), $payload)->assertRedirect();
        $this->assertSame(['Evaluación final', 'Participación diaria'], $scheme->refresh()->components->pluck('name')->all());
    }

    public function test_duplicate_titles_and_skill_names_are_rejected(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.manage']);
        $payload = $this->validPayload();
        $payload['components'][1]['name'] = 'participación DIARIA';
        $payload['components'][1]['skills'][1]['name'] = 'EXPRESIÓN ORAL';

        $this->actingAs($user)->put(route('academic.weightings.update', $scheme), $payload)
            ->assertSessionHasErrors(['components', 'components.1.skills']);
    }

    public function test_incomplete_totals_or_invalid_multiple_skills_prevent_finalization(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.manage']);
        $payload = $this->validPayload();
        $payload['components'][0]['weight'] = 30;
        $payload['components'][1]['skills'] = [['name' => 'Expresión oral', 'weight' => 100]];
        $this->actingAs($user)->put(route('academic.weightings.update', $scheme), $payload)->assertRedirect();

        $this->actingAs($user)->post(route('academic.weightings.finalize', $scheme))->assertSessionHasErrors('components');
        $this->assertSame('draft', $scheme->refresh()->status);
    }

    public function test_finalized_configuration_without_links_can_be_deleted(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.manage']);
        $this->actingAs($user)->put(route('academic.weightings.update', $scheme), $this->validPayload());
        $this->actingAs($user)->post(route('academic.weightings.finalize', $scheme));

        $this->actingAs($user)->delete(route('academic.weightings.destroy', $scheme))
            ->assertRedirect(route('academic.weightings.index'))->assertSessionHas('success');
        $this->assertDatabaseMissing('program_grading_schemes', ['id' => $scheme->id]);
        $this->assertDatabaseMissing('program_grading_components', ['program_grading_scheme_id' => $scheme->id]);
    }

    public function test_linked_weighting_cannot_be_deleted_and_has_no_delete_button(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.view', 'academic-control.manage']);
        $scheme->update(['status' => 'finalized', 'is_active' => true]);
        $level = $program->levels()->create(['company_id' => $program->company_id,
            'name' => 'Nivel 1', 'position' => 1, 'program_grading_scheme_id' => $scheme->id]);
        $this->actingAs($user)->get(route('academic.weightings.index'))
            ->assertOk()->assertDontSee('action="'.route('academic.weightings.destroy', $scheme).'"', false);
        $this->actingAs($user)->get(route('academic.weightings.show', $scheme))
            ->assertOk()->assertDontSee('Eliminar ponderación');
        $this->actingAs($user)->delete(route('academic.weightings.destroy', $scheme))
            ->assertSessionHasErrors('configuration');
        $this->assertDatabaseHas('program_grading_schemes', ['id' => $scheme->id]);
        $level->update(['program_grading_scheme_id' => null]);
        $this->actingAs($user)->get(route('academic.weightings.index'))
            ->assertOk()->assertSee('action="'.route('academic.weightings.destroy', $scheme).'"', false);
        $this->actingAs($user)->delete(route('academic.weightings.destroy', $scheme))
            ->assertSessionHas('success');
    }

    public function test_draft_can_be_deleted_but_view_only_user_cannot_delete(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.view']);
        $this->actingAs($user)->get(route('academic.weightings.index'))
            ->assertOk()->assertDontSee('Eliminar ponderación');
        $this->actingAs($user)->delete(route('academic.weightings.destroy', $scheme))->assertForbidden();
        Permission::findOrCreate('academic-control.manage');
        $user->givePermissionTo('academic-control.manage');
        $this->actingAs($user)->delete(route('academic.weightings.destroy', $scheme))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('program_grading_schemes', ['id' => $scheme->id]);
    }

    public function test_cross_company_scheme_cannot_be_modified(): void
    {
        [$user, $program] = $this->context(['academic-control.manage']);
        $otherCompany = Company::factory()->create();
        $otherProgram = Program::withoutGlobalScope('company')->create(['company_id' => $otherCompany->id, 'title' => 'Otro', 'duration_months' => 4]);
        $otherScheme = ProgramGradingScheme::withoutGlobalScope('company')->create(['company_id' => $otherCompany->id, 'name' => 'Otra empresa']);

        $this->actingAs($user)->put(route('academic.weightings.update', $otherScheme), $this->validPayload())->assertNotFound();
    }

    public function test_schemes_can_be_created_without_a_program_and_multiple_can_be_active(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.view', 'academic-control.manage']);
        $this->assertNull($scheme->program_id);
        $this->actingAs($user)->put(route('academic.weightings.update', $scheme), $this->validPayload())->assertRedirect();
        $this->actingAs($user)->post(route('academic.weightings.finalize', $scheme))->assertRedirect();
        $this->actingAs($user)->post(route('academic.weightings.store'), [
            'name' => 'Ponderación Y', 'source_scheme_id' => $scheme->id,
        ])->assertRedirect();
        $copy = ProgramGradingScheme::query()->with('components.skills')->where('name', 'Ponderación Y')->firstOrFail();
        $this->assertSame($scheme->fresh()->components->pluck('name')->all(), $copy->components->pluck('name')->all());
        $this->actingAs($user)->post(route('academic.weightings.finalize', $copy))->assertRedirect();
        $this->assertTrue($scheme->fresh()->is_active);
        $this->assertTrue($copy->fresh()->is_active);
        $this->assertNull($copy->program_id);
        $this->actingAs($user)->put(route('academic.weightings.update', $scheme), $this->validPayload())
            ->assertSessionHasErrors('configuration');
        $this->actingAs($user)->post(route('academic.weightings.store'), ['name' => 'Ponderación Z'])
            ->assertRedirect();
        $this->assertSame('draft', ProgramGradingScheme::where('name', 'Ponderación Z')->firstOrFail()->status);
    }

    public function test_each_program_level_can_use_a_different_or_shared_scheme(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.view', 'academic-control.manage']);
        $this->actingAs($user)->put(route('academic.weightings.update', $scheme), $this->validPayload());
        $this->actingAs($user)->post(route('academic.weightings.finalize', $scheme));
        $this->actingAs($user)->post(route('academic.weightings.store'), ['name' => 'Y', 'source_scheme_id' => $scheme->id]);
        $other = ProgramGradingScheme::where('name', 'Y')->firstOrFail();
        $this->actingAs($user)->post(route('academic.weightings.finalize', $other));
        $secondProgram = Program::create(['company_id' => $program->company_id, 'title' => 'Francés', 'duration_months' => 12]);
        foreach ([$program, $secondProgram] as $target) {
            foreach ([1, 2] as $position) {
                $target->levels()->create(['company_id' => $target->company_id, 'name' => "Nivel {$position}", 'position' => $position, 'is_active' => true]);
            }
        }
        $levels = $program->levels()->get();
        $this->actingAs($user)->put(route('academic.control.levels.update', $program), ['levels' => [
            ['id' => $levels[0]->id, 'scheme_id' => $scheme->id],
            ['id' => $levels[1]->id, 'scheme_id' => $other->id],
        ]])->assertSessionHas('success');
        $secondLevels = $secondProgram->levels()->get();
        $this->actingAs($user)->put(route('academic.control.levels.update', $secondProgram), ['levels' => [
            ['id' => $secondLevels[0]->id, 'scheme_id' => $scheme->id],
            ['id' => $secondLevels[1]->id, 'scheme_id' => $scheme->id],
        ]])->assertSessionHas('success');
        $this->assertSame([$scheme->id, $other->id], $program->levels()->pluck('program_grading_scheme_id')->all());
        $this->assertSame([$scheme->id, $scheme->id], $secondProgram->levels()->pluck('program_grading_scheme_id')->all());
        $module = AcademicModule::create(['company_id' => $program->company_id, 'program_id' => $program->id,
            'program_level_id' => $levels[1]->id, 'name' => 'Curso', 'modality' => 'virtual',
            'starts_at' => '18:00', 'ends_at' => '20:00', 'start_date' => today(), 'end_date' => today()->addMonth()]);
        $this->assertSame($other->id, $module->configuredGradingScheme()->id);
        $this->assertSame($other->id, app(ModuleCompletionService::class)->preview($module)['scheme']->id);
        $area = Area::create(['company_id' => $program->company_id, 'name' => 'Académico', 'is_active' => true]);
        $position = Position::create(['company_id' => $program->company_id, 'area_id' => $area->id, 'name' => 'Docente', 'is_academic' => true, 'is_active' => true]);
        $personnel = Personnel::create(['company_id' => $program->company_id, 'position_id' => $position->id, 'first_name' => 'Ana', 'paternal_surname' => 'Flores', 'identity_document' => '9001', 'phone' => '70000001', 'email' => 'ana@example.com', 'is_active' => true]);
        ClassSession::create(['company_id' => $program->company_id, 'academic_module_id' => $module->id,
            'program_grading_scheme_id' => $other->id, 'personnel_id' => $personnel->id, 'class_date' => today(), 'started_by' => $user->id, 'started_at' => now()]);
        $this->actingAs($user)->put(route('academic.control.levels.update', $program), ['levels' => [
            ['id' => $levels[1]->id, 'scheme_id' => $scheme->id],
        ]])->assertSessionHas('success');
        $this->assertSame($scheme->id, $module->fresh()->configuredGradingScheme()->id);
        $this->assertSame($other->id, app(ModuleCompletionService::class)->preview($module->fresh())['scheme']->id);
        $this->actingAs($user)->get(route('academic.control.levels.edit', $program))->assertOk()->assertSee('Nivel 2');
        $this->actingAs($user)->delete(route('academic.weightings.destroy', $other))
            ->assertSessionHasErrors('configuration');
        $this->assertDatabaseHas('program_grading_schemes', ['id' => $other->id]);
    }

    public function test_assignments_reject_drafts_foreign_levels_and_foreign_company_schemes(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.manage']);
        $level = $program->levels()->create(['company_id' => $program->company_id, 'name' => 'Nivel 1', 'position' => 1]);
        $route = route('academic.control.levels.update', $program);
        $this->actingAs($user)->put($route, ['levels' => [['id' => $level->id, 'scheme_id' => $scheme->id]]])
            ->assertSessionHasErrors('levels');
        $company = Company::factory()->create();
        $foreignScheme = ProgramGradingScheme::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Ajena', 'status' => 'finalized', 'is_active' => true]);
        $this->actingAs($user)->put($route, ['levels' => [['id' => $level->id, 'scheme_id' => $foreignScheme->id]]])->assertNotFound();
        $foreignProgram = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Ajeno', 'duration_months' => 12]);
        $foreignLevel = ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $foreignProgram->id, 'name' => 'Nivel ajeno', 'position' => 1]);
        $this->actingAs($user)->put($route, ['levels' => [['id' => $foreignLevel->id, 'scheme_id' => null]]])->assertNotFound();
        $this->assertNull($level->fresh()->program_grading_scheme_id);
        $user->revokePermissionTo('academic-control.manage');
        $this->actingAs($user)->put($route, ['levels' => [['id' => $level->id, 'scheme_id' => null]]])->assertForbidden();
    }

    public function test_migration_preserves_existing_schemes_and_assigns_them_to_program_levels(): void
    {
        $company = Company::factory()->create();
        $program = Program::create(['company_id' => $company->id, 'title' => 'Programa anterior', 'duration_months' => 12]);
        $level = ProgramLevel::create(['company_id' => $company->id, 'program_id' => $program->id, 'name' => 'Nivel 1', 'position' => 1]);
        $scheme = ProgramGradingScheme::create(['company_id' => $company->id, 'program_id' => $program->id, 'name' => 'Original', 'version' => 1, 'status' => 'finalized', 'is_active' => true]);
        $component = $scheme->components()->create(['company_id' => $company->id, 'name' => 'Examen', 'weight' => 100, 'frequency' => 'single', 'skill_mode' => 'single_skill', 'scoring_method' => 'percentage', 'position' => 1]);
        $skill = $component->skills()->create(['company_id' => $company->id, 'name' => 'Comprensión', 'weight' => 100, 'position' => 1]);
        Schema::table('program_levels', function ($table): void {
            $table->dropConstrainedForeignId('program_grading_scheme_id');
        });
        Schema::table('program_grading_schemes', function ($table): void {
            $table->dropColumn('name');
            $table->unique(['program_id', 'version']);
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
        });
        DB::statement("CREATE UNIQUE INDEX program_grading_schemes_one_draft_per_program ON program_grading_schemes (program_id) WHERE status = 'draft'");
        DB::statement('CREATE UNIQUE INDEX program_grading_schemes_one_active_per_program ON program_grading_schemes (program_id) WHERE is_active = true');
        $migration = require database_path('migrations/2026_10_09_120000_make_grading_schemes_independent.php');
        $migration->up();
        $this->assertSame($scheme->id, $level->fresh()->program_grading_scheme_id);
        $this->assertNull($scheme->fresh()->program_id);
        $this->assertSame('Programa anterior · versión 1', $scheme->fresh()->name);
        $this->assertDatabaseHas('program_grading_components', ['id' => $component->id, 'program_grading_scheme_id' => $scheme->id]);
        $this->assertDatabaseHas('program_grading_skills', ['id' => $skill->id]);
    }

    public function test_catalog_program_list_and_assignment_page_are_separate(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.view', 'academic-control.manage']);
        $program->levels()->create(['company_id' => $program->company_id, 'name' => 'Nivel de prueba', 'position' => 1]);
        $this->actingAs($user)->get(route('academic.control.index'))
            ->assertOk()->assertSee($program->title)
            ->assertSee(route('academic.control.levels.edit', $program))
            ->assertDontSee('Catálogo de ponderaciones')->assertDontSee('Nivel de prueba');
        $this->actingAs($user)->get(route('academic.weightings.index'))
            ->assertOk()->assertSee('Catálogo de ponderaciones')->assertSee($scheme->name)
            ->assertSee('Crear ponderación')->assertDontSee($program->title)
            ->assertSee(route('academic.weightings.index'))->assertSee(route('academic.control.index'));
        $this->actingAs($user)->get(route('academic.weightings.show', $scheme))
            ->assertOk()->assertSee(route('academic.weightings.index'));
        $this->actingAs($user)->get(route('academic.control.levels.edit', $program))
            ->assertOk()->assertSee($program->title)->assertSee('Nivel de prueba')
            ->assertSee('Guardar asignaciones')->assertDontSee('Crear ponderación independiente');
        $this->actingAs($user)->put(route('academic.control.levels.update', $program), [
            'levels' => [['id' => $program->levels()->firstOrFail()->id, 'scheme_id' => null]],
        ])->assertRedirect(route('academic.control.levels.edit', $program));
    }

    public function test_assignment_page_is_company_scoped_and_requires_view_permission(): void
    {
        [$user, $program] = $this->context(['academic-control.view']);
        $foreignCompany = Company::factory()->create();
        $foreignProgram = Program::withoutGlobalScope('company')->create(['company_id' => $foreignCompany->id, 'title' => 'Ajeno', 'duration_months' => 12]);
        $this->actingAs($user)->get(route('academic.control.levels.edit', $foreignProgram))->assertNotFound();
        $this->actingAs($user)->get(route('academic.control.levels.edit', $program))->assertOk()->assertDontSee('Guardar asignaciones');
        $this->actingAs($user)->get(route('academic.weightings.index'))->assertOk()->assertDontSee('Crear ponderación independiente');
        $user->revokePermissionTo('academic-control.view');
        $this->actingAs($user)->get(route('academic.control.levels.edit', $program))->assertForbidden();
        $this->actingAs($user)->get(route('academic.weightings.index'))->assertForbidden();
    }

    public function test_names_can_be_changed_for_draft_and_linked_finalized_weightings(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.view', 'academic-control.manage']);
        $this->actingAs($user)->get(route('academic.weightings.index'))->assertOk()->assertSee('Cambiar nombre');
        $this->actingAs($user)->patch(route('academic.weightings.rename', $scheme), ['name' => 'Nuevo nombre'])
            ->assertSessionHas('success');
        $this->assertSame('Nuevo nombre', $scheme->fresh()->name);
        $this->actingAs($user)->put(route('academic.weightings.update', $scheme), $this->validPayload());
        $this->actingAs($user)->post(route('academic.weightings.finalize', $scheme));
        $level = $program->levels()->create(['company_id' => $program->company_id, 'name' => 'Nivel 1',
            'position' => 1, 'program_grading_scheme_id' => $scheme->id]);
        $before = $scheme->fresh()->load('components.skills')->toArray();
        $this->actingAs($user)->get(route('academic.weightings.show', $scheme))->assertOk()->assertSee('Cambiar nombre');
        $this->actingAs($user)->patch(route('academic.weightings.rename', $scheme), [
            'name' => 'Nombre actualizado', 'status' => 'draft', 'passing_score' => 1,
        ])->assertSessionHas('success');
        $after = $scheme->fresh()->load('components.skills')->toArray();
        unset($before['name'], $before['updated_at'], $after['name'], $after['updated_at']);
        $this->assertSame($before, $after);
        $this->assertSame('Nombre actualizado', $scheme->fresh()->name);
        $this->assertSame($scheme->id, $level->fresh()->program_grading_scheme_id);
    }

    public function test_renaming_validates_names_and_requires_management_permission(): void
    {
        [$user, $program, $scheme] = $this->context(['academic-control.view', 'academic-control.manage']);
        foreach (['', '   ', str_repeat('a', 151)] as $name) {
            $this->actingAs($user)->patch(route('academic.weightings.rename', $scheme), ['name' => $name])
                ->assertSessionHasErrors('name');
        }
        $company = Company::factory()->create();
        $foreign = ProgramGradingScheme::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Ajena']);
        $this->actingAs($user)->patch(route('academic.weightings.rename', $foreign), ['name' => 'Cambio'])->assertNotFound();
        $user->revokePermissionTo('academic-control.manage');
        $this->actingAs($user)->get(route('academic.weightings.index'))->assertOk()->assertDontSee('Cambiar nombre');
        $this->actingAs($user)->get(route('academic.weightings.show', $scheme))->assertOk()->assertDontSee('Cambiar nombre');
        $this->actingAs($user)->patch(route('academic.weightings.rename', $scheme), ['name' => 'Cambio'])->assertForbidden();
        $this->assertSame('Ponderación X', $scheme->fresh()->name);
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
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Ponderación X']);

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
