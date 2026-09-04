<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentAssignment;
use App\Models\AcademicModuleTeacherAssignment;
use App\Models\Area;
use App\Models\Company;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Program;
use App\Models\ProgramGradingScheme;
use App\Models\ProgramGradingSkill;
use App\Models\ProgramLevel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TeacherClassAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_start_an_assigned_module_class_without_manual_attendance(): void
    {
        [$user, $module, $student] = $this->context();

        $this->actingAs($user)->get(route('teacher.modules.index'))
            ->assertOk()->assertSee($module->name)->assertSee('Iniciar clase');

        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module))
            ->assertRedirect();
        $session = $module->classSessions()->sole();
        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module))->assertRedirect();
        $this->assertDatabaseCount('class_sessions', 1);

        $this->actingAs($user)->get(route('teacher.modules.attendance.edit', [$module, $session]))
            ->assertOk()->assertSee($student->first_name)->assertDontSee('Presente');
        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [])
            ->assertRedirect(route('teacher.modules.index'));
        $this->assertDatabaseCount('student_attendances', 0);
    }

    public function test_teacher_cannot_manage_a_module_assigned_to_another_teacher(): void
    {
        [$user, $module] = $this->context();
        $otherPersonnel = Personnel::withoutGlobalScope('company')->create(['company_id' => $user->company_id, 'position_id' => $user->personnel->position_id, 'first_name' => 'Otro', 'paternal_surname' => 'Docente', 'identity_document' => '9002', 'phone' => '70000002', 'email' => 'otro@example.com', 'is_active' => true]);
        $otherUser = User::factory()->create(['company_id' => $user->company_id, 'personnel_id' => $otherPersonnel->id]);
        $otherUser->givePermissionTo(['teaching.view', 'teaching.manage']);

        $this->actingAs($otherUser)->post(route('teacher.modules.sessions.start', $module))->assertForbidden();
        $this->actingAs($otherUser)->get(route('teacher.modules.single-grades.edit', $module))->assertForbidden();
        $this->actingAs($otherUser)->get(route('teacher.tracking.show', $module))->assertForbidden();
        $this->assertDatabaseCount('class_sessions', 0);
    }

    public function test_teacher_can_select_a_daily_skill_and_grade_every_module_student(): void
    {
        [$user, $module, $student] = $this->context();
        $skill = $this->dailySkill($module);
        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module));
        $session = $module->classSessions()->sole();

        $this->actingAs($user)->get(route('teacher.modules.attendance.edit', [$module, $session]))
            ->assertOk()->assertSee('Habilidades trabajadas hoy')->assertSee('Lista de estudiantes')->assertSee('Resumen de la clase')->assertSee('Asistencia')->assertSee('Participación')->assertSee('Habilidades usadas')->assertSee('Listening')->assertSee('academicObservationModal')->assertSee('daily-skill-column', false)->assertSee('step="1"', false)->assertSee('value="0"', false)->assertDontSee('Registra las calificaciones y observaciones académicas.')->assertDontSee('Porcentaje · 0 a 100')->assertDontSee('Opcional')->assertDontSee("state==='saved'?'Guardado'", false)->assertDontSee($student->identity_document);
        $this->actingAs($user)->patchJson(route('teacher.modules.daily-record.autosave', [$module, $session]), [
            'type' => 'grade', 'student_id' => $student->id, 'skill_id' => $skill->id, 'score' => 82.555,
        ])->assertUnprocessable();
        $this->actingAs($user)->patchJson(route('teacher.modules.daily-record.autosave', [$module, $session]), [
            'type' => 'grade', 'student_id' => $student->id, 'skill_id' => $skill->id, 'score' => 82.55,
        ])->assertOk()->assertJsonPath('score', 82.55)->assertJsonMissing(['message' => 'Nota guardada.']);
        $this->assertDatabaseHas('student_daily_grades', ['student_id' => $student->id, 'score' => 82.55]);
        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'selected_skills' => [$skill->id],
            'grades' => [$skill->id => [$student->id => 82.555]],
        ])->assertSessionHasErrors("grades.{$skill->id}.{$student->id}");
        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'selected_skills' => [$skill->id],
            'grades' => [$skill->id => [$student->id => 82.55]],
        ])->assertRedirect(route('teacher.modules.index'));

        $this->assertDatabaseHas('student_daily_grades', ['student_id' => $student->id, 'score' => 82.55]);
        $this->assertSame($skill->component->program_grading_scheme_id, $session->program_grading_scheme_id);
        $this->assertNotNull($session->refresh()->ended_at);
    }

    public function test_selected_daily_skill_requires_a_grade_for_every_student(): void
    {
        [$user, $module, $student] = $this->context();
        $skill = $this->dailySkill($module);
        $other = Student::withoutGlobalScope('company')->create(['company_id' => $user->company_id, 'identity_document' => '3002', 'first_name' => 'Mario', 'paternal_surname' => 'Pérez', 'birth_date' => '2010-01-01', 'gender' => 'Masculino', 'is_active' => true]);
        AcademicModuleStudentAssignment::withoutGlobalScope('company')->create(['company_id' => $user->company_id, 'academic_module_id' => $module->id, 'student_id' => $other->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);
        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module));
        $session = $module->classSessions()->sole();

        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'selected_skills' => [$skill->id],
            'grades' => [$skill->id => [$student->id => 80]],
        ])->assertSessionHasErrors("grades.{$skill->id}");
        $this->assertDatabaseCount('student_daily_grades', 0);
    }

    public function test_simple_daily_grade_only_accepts_zero_or_one_and_normalizes_one_to_one_hundred(): void
    {
        [$user, $module, $student] = $this->context();
        $skill = $this->dailySkill($module, 'simple');
        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module));
        $session = $module->classSessions()->sole();

        $this->actingAs($user)->get(route('teacher.modules.attendance.edit', [$module, $session]))
            ->assertOk()->assertDontSee('Simple · 0 o 1')->assertSee('form-check form-switch', false);

        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'selected_skills' => [$skill->id],
            'grades' => [$skill->id => [$student->id => 2]],
        ])->assertSessionHasErrors("grades.{$skill->id}.{$student->id}");

        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'selected_skills' => [$skill->id],
            'grades' => [$skill->id => [$student->id => 1]],
        ])->assertRedirect(route('teacher.modules.index'));

        $this->assertDatabaseHas('student_daily_grades', ['student_id' => $student->id, 'score' => 100]);
    }

    public function test_teacher_can_record_a_configured_single_percentage_grade_with_two_decimals(): void
    {
        [$user, $module, $student] = $this->context();
        $skill = $this->singleSkill($module);

        $this->actingAs($user)->get(route('teacher.modules.single-grades.edit', $module))
            ->assertOk()
            ->assertSee('Ponderaciones únicas')
            ->assertSee('Examen final')
            ->assertSee('Comprensión')
            ->assertSee('step="0.01"', false);

        $this->actingAs($user)->put(route('teacher.modules.single-grades.update', $module), [
            'grades' => [$skill->id => [$student->id => 87.555]],
        ])->assertSessionHasErrors("grades.{$skill->id}.{$student->id}");

        $this->actingAs($user)->put(route('teacher.modules.single-grades.update', $module), [
            'grades' => [$skill->id => [$student->id => 87.55]],
        ])->assertRedirect(route('teacher.modules.single-grades.edit', $module));

        $this->assertDatabaseHas('student_single_grades', [
            'academic_module_id' => $module->id,
            'program_grading_skill_id' => $skill->id,
            'student_id' => $student->id,
            'score' => 87.55,
        ]);
        $this->actingAs($user)->get(route('teacher.tracking.show', $module))
            ->assertOk()->assertSee('Nota acumulada')->assertSee('87,55');

        Permission::findOrCreate('students.view');
        $user->givePermissionTo('students.view');
        $this->actingAs($user)->get(route('students.kardex.show', $student))
            ->assertOk()
            ->assertDontSee('Ponderaciones')
            ->assertSee('87,55')
            ->assertSee('Ver detalles');
    }

    public function test_student_kardex_shows_module_details_by_day(): void
    {
        [$user, $module, $student] = $this->context();
        $skill = $this->dailySkill($module);
        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module));
        $session = $module->classSessions()->sole();

        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'selected_skills' => [$skill->id],
            'grades' => [$skill->id => [$student->id => 91.25]],
            'observations' => [$student->id => 'Excelente avance diario.'],
        ])->assertRedirect(route('teacher.modules.index'));

        Permission::findOrCreate('students.view');
        $user->givePermissionTo('students.view');

        $this->actingAs($user)->get(route('students.kardex.details', [$student, $module]))
            ->assertOk()
            ->assertSee('Registro por día')
            ->assertDontSee('<th>Asistencia</th>', false)
            ->assertSee($session->class_date->format('d/m/Y'))
            ->assertSee('Listening')
            ->assertSee('91,25')
            ->assertSee('Excelente avance diario.');

        $otherStudent = Student::withoutGlobalScope('company')->create([
            'company_id' => $user->company_id,
            'identity_document' => '3999',
            'first_name' => 'Sin',
            'paternal_surname' => 'Asignación',
            'birth_date' => '2010-01-01',
            'gender' => 'Femenino',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('students.kardex.details', [$otherStudent, $module]))
            ->assertNotFound();
    }

    public function test_single_simple_grade_is_normalized_to_one_hundred(): void
    {
        [$user, $module, $student] = $this->context();
        $skill = $this->singleSkill($module, 'simple');

        $this->actingAs($user)->put(route('teacher.modules.single-grades.update', $module), [
            'grades' => [$skill->id => [$student->id => 1]],
        ])->assertRedirect(route('teacher.modules.single-grades.edit', $module));

        $this->assertDatabaseHas('student_single_grades', [
            'academic_module_id' => $module->id,
            'student_id' => $student->id,
            'score' => 100,
        ]);
    }

    public function test_teacher_can_record_an_optional_academic_observation_and_view_the_tracking_summary(): void
    {
        [$user, $module, $student] = $this->context();
        $skill = $this->dailySkill($module);
        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module));
        $session = $module->classSessions()->sole();

        $this->actingAs($user)->patchJson(route('teacher.modules.daily-record.autosave', [$module, $session]), [
            'type' => 'observation', 'student_id' => $student->id, 'observation' => 'Mejoró su comprensión auditiva.',
        ])->assertOk();
        $this->assertDatabaseHas('student_academic_observations', ['class_session_id' => $session->id, 'student_id' => $student->id]);

        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'selected_skills' => [$skill->id],
            'grades' => [$skill->id => [$student->id => 80]],
            'observations' => [$student->id => 'Mejoró su comprensión auditiva.'],
        ])->assertRedirect(route('teacher.modules.index'));

        $this->assertDatabaseHas('student_academic_observations', [
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'observation' => 'Mejoró su comprensión auditiva.',
        ]);
        $this->actingAs($user)->get(route('teacher.tracking.index'))
            ->assertOk()->assertSee($module->name)->assertSee('Ver centralizador de notas');
        $this->actingAs($user)->get(route('teacher.tracking.show', $module))
            ->assertOk()->assertSee('Centralizador de notas')->assertSee('Listening')->assertSee('80,00')->assertSee('Mejoró su comprensión auditiva.');

        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'observations' => [$student->id => ''],
        ])->assertSessionHasErrors('class');
        $this->assertDatabaseHas('student_academic_observations', ['class_session_id' => $session->id, 'student_id' => $student->id]);
    }

    public function test_user_without_a_teacher_profile_sees_a_clear_message(): void
    {
        $company = Company::factory()->create();
        Permission::findOrCreate('teaching.view');
        $user = User::factory()->create(['company_id' => $company->id, 'personnel_id' => null]);
        $user->givePermissionTo('teaching.view');

        $this->actingAs($user)->get(route('teacher.modules.index'))
            ->assertOk()
            ->assertSee('No tienes un perfil docente vinculado')
            ->assertSee('Solicita a un administrador');
    }

    public function test_teacher_action_without_a_teacher_profile_returns_forbidden_instead_of_type_error(): void
    {
        [$teacher, $module] = $this->context();
        $user = User::factory()->create(['company_id' => $teacher->company_id, 'personnel_id' => null]);
        $user->givePermissionTo(['teaching.view', 'teaching.manage']);

        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module))
            ->assertForbidden()
            ->assertSee('Tu cuenta no está vinculada a un docente');
    }

    private function context(): array
    {
        $company = Company::factory()->create();
        Permission::findOrCreate('teaching.view');
        Permission::findOrCreate('teaching.manage');
        $area = Area::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Académico', 'is_active' => true]);
        $position = Position::withoutGlobalScope('company')->create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Docente', 'is_academic' => true, 'is_active' => true]);
        $personnel = Personnel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'position_id' => $position->id, 'first_name' => 'Ana', 'paternal_surname' => 'Flores', 'identity_document' => '9001', 'phone' => '70000001', 'email' => 'ana@example.com', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id, 'personnel_id' => $personnel->id]);
        $user->givePermissionTo(['teaching.view', 'teaching.manage']);
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        $level = ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'name' => 'Básico 1', 'position' => 1]);
        $module = AcademicModule::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'program_level_id' => $level->id, 'name' => 'Módulo Básico 1', 'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00', 'start_date' => today()->subDay(), 'end_date' => today()->addMonth()]);
        AcademicModuleTeacherAssignment::withoutGlobalScope('company')->create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'personnel_id' => $personnel->id, 'assigned_at' => now()]);
        $student = Student::withoutGlobalScope('company')->create(['company_id' => $company->id, 'identity_document' => '3001', 'first_name' => 'Lucía', 'paternal_surname' => 'Pérez', 'birth_date' => '2010-01-01', 'gender' => 'Femenino', 'is_active' => true]);
        AcademicModuleStudentAssignment::withoutGlobalScope('company')->create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);

        return [$user, $module, $student];
    }

    private function dailySkill(AcademicModule $module, string $scoringMethod = 'percentage'): ProgramGradingSkill
    {
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->where('program_id', $module->program_id)->firstOrFail();
        $scheme->update(['status' => 'finalized', 'is_active' => true, 'finalized_at' => now()]);
        $component = $scheme->components()->create(['company_id' => $module->company_id, 'name' => 'Overall Performance', 'weight' => 100, 'frequency' => 'daily', 'skill_mode' => 'single_skill', 'scoring_method' => $scoringMethod, 'position' => 1]);

        return $component->skills()->create(['company_id' => $module->company_id, 'name' => 'Listening', 'weight' => 100, 'position' => 1]);
    }

    private function singleSkill(AcademicModule $module, string $scoringMethod = 'percentage'): ProgramGradingSkill
    {
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->where('program_id', $module->program_id)->firstOrFail();
        $scheme->update(['status' => 'finalized', 'is_active' => true, 'finalized_at' => now()]);
        $component = $scheme->components()->create(['company_id' => $module->company_id, 'name' => 'Examen final', 'weight' => 100, 'frequency' => 'single', 'skill_mode' => 'single_skill', 'scoring_method' => $scoringMethod, 'position' => 1]);

        return $component->skills()->create(['company_id' => $module->company_id, 'name' => 'Comprensión', 'weight' => 100, 'position' => 1]);
    }
}
