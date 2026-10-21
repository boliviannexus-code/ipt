<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentAssignment;
use App\Models\AcademicModuleTeacherAssignment;
use App\Models\Area;
use App\Models\Company;
use App\Models\Customer;
use App\Models\EnrollmentContract;
use App\Models\Personnel;
use App\Models\Plan;
use App\Models\Position;
use App\Models\Program;
use App\Models\ProgramGradingScheme;
use App\Models\ProgramGradingSkill;
use App\Models\ProgramLevel;
use App\Models\RectorateApplication;
use App\Models\Student;
use App\Models\User;
use App\Services\Academic\StudentAttendanceRegularityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StudentAttendanceRegularityTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_finalized_absences_inactivate_only_the_program_contract_and_highlight_required_lists(): void
    {
        [$user, $module, $student] = $this->context();
        $module->update(['start_date' => today()->subDays(5)]);
        $attendance = $this->dailySkill($module, 'simple');
        $attendance->component->update(['name' => 'Attendance']);
        $attendance->update(['name' => 'Attendance']);
        $contract = $this->contractFor($module, $student);
        Permission::findOrCreate('students.view');
        Permission::findOrCreate('rectorate.create');
        $user->givePermissionTo(['students.view', 'rectorate.create']);
        $otherProgram = Program::create(['company_id' => $module->company_id, 'title' => 'Francés', 'duration_months' => 12]);
        $otherContract = $this->contractFor($module, $student, $otherProgram->id);
        $this->actingAs($user);
        foreach ([3, 2, 1] as $index => $daysAgo) {
            $this->post(route('teacher.modules.sessions.start', $module), ['class_date' => today()->subDays($daysAgo)->toDateString()])->assertSessionHasNoErrors();
            $session = $module->classSessions()->latest('id')->first();
            $this->put(route('teacher.modules.attendance.update', [$module, $session]), [
                'grades' => [$attendance->id => [$student->id => 0]],
            ])->assertSessionHasNoErrors();
            $this->assertSame($index === 2 ? 'inactive' : 'enrolled', $contract->refresh()->status);
        }
        $this->assertSame('enrolled', $otherContract->refresh()->status);
        $this->assertTrue($student->refresh()->is_active);
        $this->assertNotNull($contract->attendance_inactive_at);
        $this->assertSame($module->id, $contract->attendance_inactive_module_id);
        $this->get(route('students.index'))->assertOk()->assertSee('Lucía')->assertSee('student-attendance-inactive')->assertSee('INACTIVO');
        $this->getJson(route('datatables.enrollments', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()->assertJsonFragment(['DT_RowClass' => 'student-attendance-inactive'])->assertSee('INACTIVO');
        $this->post(route('teacher.modules.sessions.start', $module));
        $session = $module->classSessions()->latest('id')->first();
        $this->get(route('teacher.modules.attendance.edit', [$module, $session]))->assertOk()
            ->assertSee('student-attendance-inactive')->assertSee('INACTIVO')->assertSee('Faltas en este módulo: 3');
        $this->put(route('teacher.modules.attendance.update', [$module, $session]), ['grades' => [$attendance->id => [$student->id => 1]]])->assertSessionHasNoErrors();
        $this->assertSame('inactive', $contract->refresh()->status);
    }

    public function test_unsaved_or_open_class_attendance_and_other_skills_do_not_count_as_final_absences(): void
    {
        [$user, $module, $student] = $this->context();
        $module->update(['start_date' => today()->subDays(5)]);
        $attendance = $this->dailySkill($module, 'simple');
        $attendance->component->update(['name' => 'Attendance']);
        $attendance->update(['name' => 'Attendance']);
        $other = $module->level->gradingScheme->components()->create([
            'company_id' => $module->company_id, 'name' => 'Participación', 'weight' => 10,
            'frequency' => 'daily', 'skill_mode' => 'single_skill', 'scoring_method' => 'simple', 'position' => 2,
        ])->skills()->create(['company_id' => $module->company_id, 'name' => 'Participación', 'weight' => 100, 'position' => 1]);
        $contract = $this->contractFor($module, $student);
        $this->actingAs($user);
        foreach ([3, 2, 1] as $daysAgo) {
            $this->post(route('teacher.modules.sessions.start', $module), ['class_date' => today()->subDays($daysAgo)->toDateString()]);
            $session = $module->classSessions()->latest('id')->first();
            $this->patchJson(route('teacher.modules.daily-record.autosave', [$module, $session]), [
                'type' => 'grade', 'student_id' => $student->id, 'skill_id' => $attendance->id, 'score' => 0,
            ])->assertOk();
        }
        app(StudentAttendanceRegularityService::class)->evaluate($module);
        $this->assertSame('enrolled', $contract->refresh()->status);
        $this->assertEmpty(app(StudentAttendanceRegularityService::class)->absenceCounts($module));
        foreach ($module->classSessions as $session) {
            $this->put(route('teacher.modules.attendance.update', [$module, $session]), [
                'selected_skills' => [$other->id], 'grades' => [$attendance->id => [$student->id => 1], $other->id => [$student->id => 0]],
            ])->assertSessionHasNoErrors();
        }
        $this->assertSame('enrolled', $contract->refresh()->status);
    }

    public function test_absences_are_per_module_not_combined_and_repeated_evaluation_is_idempotent(): void
    {
        [$user, $module, $student] = $this->context();
        $module->update(['start_date' => today()->subDays(5)]);
        $attendance = $this->dailySkill($module, 'simple');
        $attendance->component->update(['name' => 'Attendance']);
        $attendance->update(['name' => 'Attendance']);
        $contract = $this->contractFor($module, $student);
        $otherModule = $module->replicate();
        $otherModule->name = 'Otro módulo';
        $otherModule->save();
        $otherModule->teacherAssignments()->create(['company_id' => $module->company_id, 'personnel_id' => $user->personnel_id, 'assigned_at' => now()]);
        $otherModule->studentAssignments()->create(['company_id' => $module->company_id, 'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);
        $this->actingAs($user);
        foreach ([$module, $module, $otherModule] as $index => $course) {
            $this->post(route('teacher.modules.sessions.start', $course), ['class_date' => today()->subDays(3 - $index)->toDateString()])->assertSessionHasNoErrors();
            $session = $course->classSessions()->latest('id')->first();
            $this->put(route('teacher.modules.attendance.update', [$course, $session]), ['grades' => [$attendance->id => [$student->id => 0]]])->assertSessionHasNoErrors();
        }
        app(StudentAttendanceRegularityService::class)->evaluate($module);
        app(StudentAttendanceRegularityService::class)->evaluate($module);
        $this->assertSame('enrolled', $contract->refresh()->status);
        $this->assertSame(2, (int) app(StudentAttendanceRegularityService::class)->absenceCounts($module)->get($student->id));
        $this->assertSame(1, (int) app(StudentAttendanceRegularityService::class)->absenceCounts($otherModule)->get($student->id));
    }

    private function contractFor(AcademicModule $module, Student $student, ?int $programId = null): EnrollmentContract
    {
        $programId ??= $module->program_id;
        $customer = Customer::factory()->create(['company_id' => $module->company_id]);
        $plan = Plan::create(['company_id' => $module->company_id, 'name' => 'Plan '.$programId, 'monthly_cost' => 100]);
        $application = RectorateApplication::create([
            'company_id' => $module->company_id, 'customer_id' => $customer->id,
            'program_id' => $programId, 'plan_id' => $plan->id, 'student_id' => $student->id,
            'first_name' => 'Titular', 'paternal_surname' => 'Prueba', 'birth_date' => '1980-01-01',
            'email' => 'titular@example.com', 'phone' => '70000000', 'status' => 'completed',
        ]);

        return EnrollmentContract::create([
            'company_id' => $module->company_id, 'rectorate_application_id' => $application->id,
            'student_id' => $student->id, 'program_id' => $programId, 'plan_id' => $plan->id,
            'contract_number' => 1, 'monthly_amount' => 100, 'status' => 'enrolled', 'confirmed_at' => now(), 'enrolled_at' => now(),
        ]);
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
