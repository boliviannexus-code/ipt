<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleTeacherAssignment;
use App\Models\Area;
use App\Models\Company;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AcademicModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_modules_in_ajax_modals(): void
    {
        [$user, $program, $level] = $this->context();

        $this->actingAs($user)->get(route('academic.modules.create'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('Programa')->assertSee('Nivel')->assertSee('Hora inicio');

        $response = $this->actingAs($user)->postJson(route('academic.modules.store'), [
            'program_id' => $program->id,
            'program_level_id' => $level->id,
            'modality' => 'virtual',
            'starts_at' => '18:00',
            'ends_at' => '20:00',
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
        ]);
        $response->assertOk()->assertJsonPath('success', true);

        $module = AcademicModule::withoutGlobalScope('company')->sole();
        $this->assertSame('Módulo Básico 1', $module->name);
        $this->actingAs($user)->get(route('academic.modules.index'))
            ->assertOk()->assertSee('data-datatable', false)->assertSee(route('datatables.academic-modules'), false);
        $this->actingAs($user)->getJson(route('datatables.academic-modules', [
            'draw' => 1, 'start' => 0, 'length' => 10,
        ]))->assertOk()->assertJsonFragment(['name' => 'Módulo Básico 1']);

        $this->actingAs($user)->putJson(route('academic.modules.update', $module), [
            'program_id' => $program->id,
            'program_level_id' => $level->id,
            'name' => 'Gramática avanzada',
            'modality' => 'presential',
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'start_date' => '2026-11-01',
            'end_date' => '2026-12-15',
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('academic_modules', ['id' => $module->id, 'name' => 'Gramática avanzada', 'modality' => 'presential']);

        $this->actingAs($user)->deleteJson(route('academic.modules.destroy', $module))
            ->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('academic_modules', ['id' => $module->id]);
    }

    public function test_level_must_belong_to_selected_program(): void
    {
        [$user, $program] = $this->context();
        $otherProgram = Program::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'title' => 'Francés', 'duration_months' => 12]);
        $otherLevel = ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'program_id' => $otherProgram->id, 'name' => 'Inicial', 'position' => 1]);

        $this->actingAs($user)->postJson(route('academic.modules.store'), [
            'program_id' => $program->id, 'program_level_id' => $otherLevel->id, 'name' => 'Inválido',
            'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00',
            'start_date' => '2026-09-01', 'end_date' => '2026-10-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('program_level_id');
        $this->assertDatabaseCount('academic_modules', 0);
    }

    public function test_end_date_cannot_be_before_start_date(): void
    {
        [$user, $program, $level] = $this->context();

        $this->actingAs($user)->postJson(route('academic.modules.store'), [
            'program_id' => $program->id, 'program_level_id' => $level->id,
            'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00',
            'start_date' => '2026-10-01', 'end_date' => '2026-09-30',
        ])->assertUnprocessable()->assertJsonValidationErrors('end_date');
    }

    public function test_academic_personnel_can_be_assigned_and_replaced_as_module_teacher(): void
    {
        [$user, $program, $level] = $this->context();
        $module = AcademicModule::withoutGlobalScope('company')->create([
            'company_id' => $program->company_id, 'program_id' => $program->id, 'program_level_id' => $level->id,
            'name' => 'Módulo Básico 1', 'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00',
            'start_date' => '2026-09-01', 'end_date' => '2026-10-01',
        ]);
        $area = Area::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'name' => 'Académico', 'is_active' => true]);
        $position = Position::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'area_id' => $area->id, 'name' => 'Docente', 'is_academic' => true, 'is_active' => true]);
        $firstTeacher = Personnel::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'position_id' => $position->id, 'first_name' => 'Ana', 'paternal_surname' => 'Flores', 'identity_document' => '1001', 'phone' => '70000001', 'email' => 'ana@example.com', 'is_active' => true]);
        $secondTeacher = Personnel::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'position_id' => $position->id, 'first_name' => 'Luis', 'paternal_surname' => 'Rojas', 'identity_document' => '1002', 'phone' => '70000002', 'email' => 'luis@example.com', 'is_active' => true]);

        $this->actingAs($user)->get(route('academic.modules.teacher.edit', $module), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('Ana Flores')->assertSee('Luis Rojas');
        $this->actingAs($user)->putJson(route('academic.modules.teacher.update', $module), ['personnel_id' => $firstTeacher->id])
            ->assertOk()->assertJsonPath('success', true);
        $this->actingAs($user)->putJson(route('academic.modules.teacher.update', $module), ['personnel_id' => $secondTeacher->id])
            ->assertOk()->assertJsonPath('success', true);

        $this->assertSame(2, AcademicModuleTeacherAssignment::withoutGlobalScope('company')->count());
        $this->assertDatabaseHas('academic_module_teacher_assignments', ['academic_module_id' => $module->id, 'personnel_id' => $firstTeacher->id]);
        $this->assertDatabaseHas('academic_module_teacher_assignments', ['academic_module_id' => $module->id, 'personnel_id' => $secondTeacher->id, 'unassigned_at' => null]);
        $this->assertNotNull(AcademicModuleTeacherAssignment::withoutGlobalScope('company')->where('personnel_id', $firstTeacher->id)->value('unassigned_at'));
    }

    public function test_personnel_without_academic_position_cannot_be_assigned_as_teacher(): void
    {
        [$user, $program, $level] = $this->context();
        $module = AcademicModule::withoutGlobalScope('company')->create([
            'company_id' => $program->company_id, 'program_id' => $program->id, 'program_level_id' => $level->id,
            'name' => 'Módulo Básico 1', 'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00',
            'start_date' => '2026-09-01', 'end_date' => '2026-10-01',
        ]);
        $area = Area::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'name' => 'Administración', 'is_active' => true]);
        $position = Position::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'area_id' => $area->id, 'name' => 'Auxiliar', 'is_academic' => false, 'is_active' => true]);
        $person = Personnel::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'position_id' => $position->id, 'first_name' => 'Mario', 'paternal_surname' => 'López', 'identity_document' => '2001', 'phone' => '70000003', 'email' => 'mario@example.com', 'is_active' => true]);

        $this->actingAs($user)->putJson(route('academic.modules.teacher.update', $module), ['personnel_id' => $person->id])
            ->assertUnprocessable()->assertJsonValidationErrors('personnel_id');
        $this->assertDatabaseCount('academic_module_teacher_assignments', 0);
    }

    private function context(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('academic-modules.view');
        Permission::findOrCreate('academic-modules.manage');
        $user->givePermissionTo(['academic-modules.view', 'academic-modules.manage']);
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        $level = ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'name' => 'Básico 1', 'position' => 1]);

        return [$user, $program, $level];
    }
}
