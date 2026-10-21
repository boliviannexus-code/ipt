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
        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module))->assertSessionHasErrors('class_date');
        $this->assertDatabaseCount('class_sessions', 1);

        $this->actingAs($user)->get(route('teacher.modules.attendance.edit', [$module, $session]))
            ->assertOk()->assertSee($student->first_name)->assertDontSee('Presente');
        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [])
            ->assertRedirect(route('teacher.modules.index'));
        $this->assertDatabaseCount('student_attendances', 0);
    }

    public function test_teacher_can_register_different_dates_and_continue_previous_pending_classes(): void
    {
        [$user, $module] = $this->context();
        $module->update(['start_date' => today()->subDays(10), 'end_date' => today()->addDays(10)]);
        $dates = [today()->subDays(2)->toDateString(), today()->toDateString(), today()->addDay()->toDateString()];
        foreach ($dates as $date) {
            $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module), ['class_date' => $date])
                ->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->assertSame($dates, $module->classSessions()->orderBy('class_date')->pluck('class_date')->map(fn ($date) => $date->toDateString())->all());
        $response = $this->get(route('teacher.modules.index'))->assertOk()->assertSee('Fecha de la clase');
        foreach ($module->classSessions()->get() as $session) {
            $response->assertSee(route('teacher.modules.attendance.edit', [$module, $session]));
        }
        $session = $module->classSessions()->firstOrFail();
        $this->get(route('teacher.modules.attendance.edit', [$module, $session]))
            ->assertOk()->assertSee($session->class_date->format('d/m/Y'));
        $session->update(['ended_at' => now(), 'finalized_by' => $user->id]);
        $this->post(route('teacher.modules.sessions.start', $module), ['class_date' => $session->class_date->toDateString()])
            ->assertSessionHasErrors('class_date');
        $this->assertDatabaseCount('class_sessions', 3);
    }

    public function test_teacher_can_register_a_past_class_when_the_course_dates_have_ended(): void
    {
        [$user, $module] = $this->context();
        $module->update(['start_date' => today()->subDays(10), 'end_date' => today()->subDays(2)]);
        $this->actingAs($user)->get(route('teacher.modules.index'))->assertOk()->assertSee('Iniciar clase');
        $this->post(route('teacher.modules.sessions.start', $module), ['class_date' => $module->end_date->toDateString()])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('class_sessions', ['academic_module_id' => $module->id, 'class_date' => $module->end_date->toDateString()]);
    }

    public function test_invalid_dates_and_closed_courses_do_not_create_classes(): void
    {
        [$user, $module] = $this->context();
        foreach (['', 'invalid', '2026-02-30', $module->start_date->subDay()->toDateString(), $module->end_date->addDay()->toDateString()] as $date) {
            $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module), ['class_date' => $date])
                ->assertSessionHasErrors('class_date');
        }
        $module->update(['closed_at' => now(), 'closed_by' => $user->id]);
        $this->get(route('teacher.modules.index'))->assertOk()->assertDontSee('Iniciar clase');
        $this->post(route('teacher.modules.sessions.start', $module), ['class_date' => today()->toDateString()])
            ->assertSessionHasErrors('class_date');
        $this->assertDatabaseCount('class_sessions', 0);
    }

    public function test_same_date_is_allowed_in_different_courses(): void
    {
        [$user, $module] = $this->context();
        $otherModule = $module->replicate();
        $otherModule->name = 'Otro curso';
        $otherModule->save();
        $otherModule->teacherAssignments()->create(['company_id' => $module->company_id,
            'personnel_id' => $user->personnel_id, 'assigned_at' => now(), 'assigned_by' => $user->id]);
        foreach ([$module, $otherModule] as $course) {
            $this->actingAs($user)->post(route('teacher.modules.sessions.start', $course), ['class_date' => today()->toDateString()])
                ->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->assertDatabaseCount('class_sessions', 2);
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
            ->assertOk()->assertSee('Nota<br>acumulada', false)->assertSee('87,55');

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
            ->assertOk()->assertSee('Centralizador de notas')->assertSee('Listening')->assertSee('80,00');

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

    public function test_attendance_is_automatically_included_and_not_offered_as_an_optional_skill(): void
    {
        [$user, $module, $student] = $this->context();
        $otherSkill = $this->dailySkill($module);
        $scheme = $module->level->gradingScheme;
        $component = $scheme->components()->create([
            'company_id' => $module->company_id, 'name' => 'Attendance', 'weight' => 10,
            'frequency' => 'daily', 'skill_mode' => 'single_skill', 'scoring_method' => 'simple', 'position' => 2,
        ]);
        $attendance = $component->skills()->create(['company_id' => $module->company_id, 'name' => 'Attendance', 'weight' => 100, 'position' => 1]);
        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module))->assertSessionHasNoErrors();
        $session = $module->classSessions()->sole();
        $this->assertDatabaseHas('class_session_grading_skills', ['class_session_id' => $session->id, 'program_grading_skill_id' => $attendance->id]);
        $this->get(route('teacher.modules.attendance.edit', [$module, $session]))->assertOk()
            ->assertSee('data-required-daily-skill', false)
            ->assertDontSee('data-daily-skill="'.$attendance->id.'"', false)
            ->assertSee('data-daily-skill="'.$otherSkill->id.'"', false);
        $this->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'selected_skills' => [], 'grades' => [$attendance->id => [$student->id => 1]],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_daily_grades', ['student_id' => $student->id, 'score' => 100]);
        $this->assertDatabaseHas('class_session_grading_skills', ['class_session_id' => $session->id, 'program_grading_skill_id' => $attendance->id]);
        $this->assertNotNull($session->refresh()->ended_at);
    }

    public function test_attendance_cannot_be_omitted_when_finishing_a_class(): void
    {
        [$user, $module, $student] = $this->context();
        $skill = $this->dailySkill($module, 'simple');
        $skill->component->update(['name' => 'Attendance']);
        $skill->update(['name' => 'Attendance']);
        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module));
        $session = $module->classSessions()->sole();
        $this->putJson(route('teacher.modules.attendance.update', [$module, $session]), ['selected_skills' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('grades.'.$skill->id);
        $this->assertNull($session->refresh()->ended_at);
        $this->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'selected_skills' => [], 'grades' => [$skill->id => [$student->id => 0]],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_daily_grades', ['student_id' => $student->id, 'score' => 0]);
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
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Ponderación de prueba', 'version' => 1, 'status' => 'draft', 'is_active' => false]);
        $level->update(['program_grading_scheme_id' => $scheme->id]);
        $module = AcademicModule::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'program_level_id' => $level->id, 'name' => 'Módulo Básico 1', 'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00', 'start_date' => today()->subDay(), 'end_date' => today()->addMonth()]);
        AcademicModuleTeacherAssignment::withoutGlobalScope('company')->create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'personnel_id' => $personnel->id, 'assigned_at' => now()]);
        $student = Student::withoutGlobalScope('company')->create(['company_id' => $company->id, 'identity_document' => '3001', 'first_name' => 'Lucía', 'paternal_surname' => 'Pérez', 'birth_date' => '2010-01-01', 'gender' => 'Femenino', 'is_active' => true]);
        AcademicModuleStudentAssignment::withoutGlobalScope('company')->create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);

        return [$user, $module, $student];
    }

    private function dailySkill(AcademicModule $module, string $scoringMethod = 'percentage'): ProgramGradingSkill
    {
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->findOrFail($module->level->program_grading_scheme_id);
        $scheme->update(['status' => 'finalized', 'is_active' => true, 'finalized_at' => now()]);
        $component = $scheme->components()->create(['company_id' => $module->company_id, 'name' => 'Overall Performance', 'weight' => 100, 'frequency' => 'daily', 'skill_mode' => 'single_skill', 'scoring_method' => $scoringMethod, 'position' => 1]);

        return $component->skills()->create(['company_id' => $module->company_id, 'name' => 'Listening', 'weight' => 100, 'position' => 1]);
    }

    private function singleSkill(AcademicModule $module, string $scoringMethod = 'percentage'): ProgramGradingSkill
    {
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->findOrFail($module->level->program_grading_scheme_id);
        $scheme->update(['status' => 'finalized', 'is_active' => true, 'finalized_at' => now()]);
        $component = $scheme->components()->create(['company_id' => $module->company_id, 'name' => 'Examen final', 'weight' => 100, 'frequency' => 'single', 'skill_mode' => 'single_skill', 'scoring_method' => $scoringMethod, 'position' => 1]);

        return $component->skills()->create(['company_id' => $module->company_id, 'name' => 'Comprensión', 'weight' => 100, 'position' => 1]);
    }
}
