<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentAssignment;
use App\Models\AcademicModuleStudentResult;
use App\Models\Area;
use App\Models\Company;
use App\Models\Customer;
use App\Models\EnrollmentContract;
use App\Models\Personnel;
use App\Models\PlacementExam;
use App\Models\Plan;
use App\Models\Position;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\RectorateApplication;
use App\Models\Student;
use App\Models\User;
use App\Services\Academic\StudentKardexPdfService;
use App\Services\Academic\StudentKardexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlacementExamTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_seven_recognizes_six_levels_and_updates_only_selected_contract_without_charges(): void
    {
        [$user, $student, $program, $levels, $teacher, $contract] = $this->examContext();
        $payload = $this->payload($contract, $levels->last(), $teacher);
        $this->actingAs($user)->get(route('placement-exams.index'))->assertOk()->assertSee('Lucía');
        $this->get(route('placement-exams.create', ['student' => $student, 'enrollment_contract_id' => $contract->id, 'target_program_level_id' => $levels->last()->id]))
            ->assertOk()->assertSee('Se reconocerá con la nota del examen')->assertSee('Nivel 7');
        $this->post(route('placement-exams.store', $student), $payload)->assertSessionHasNoErrors()->assertRedirect(route('placement-exams.history'));
        $this->assertSame($levels->last()->id, $contract->refresh()->current_program_level_id);
        $this->assertSame('enrolled', $contract->status);
        $this->assertDatabaseCount('placement_exam_levels', 6);
        $this->assertSame('80.00', PlacementExam::sole()->score);
        $this->assertDatabaseCount('account_charges', 0);
        $this->assertDatabaseCount('account_payments', 0);
        $this->assertDatabaseCount('academic_module_student_assignments', 0);
        $this->get(route('placement-exams.history'))->assertOk()->assertSee('Docente')->assertSee('Buen dominio oral');
        $this->get(route('students.kardex.show', $student))->assertOk()
            ->assertSee('Reconocido por examen de nivel')->assertSee('80,00')->assertSee('Nivel 7')->assertSee('Pendiente de asignación');
        $kardex = app(StudentKardexService::class)->build($student->fresh());
        $html = view('students.kardex-pdf', $kardex)->render();
        $this->assertStringContainsString('Reconocido por examen de nivel', $html);
        $this->assertStringContainsString('Buen dominio oral', $html);
        $this->assertStringStartsWith('%PDF-', app(StudentKardexPdfService::class)->render($kardex));
    }

    public function test_retries_are_idempotent_and_stale_submissions_cannot_advance_again(): void
    {
        [$user, $student, , $levels, $teacher, $contract] = $this->examContext();
        $payload = $this->payload($contract, $levels[3], $teacher);
        $this->actingAs($user)->post(route('placement-exams.store', $student), $payload)->assertSessionHasNoErrors();
        $this->post(route('placement-exams.store', $student), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('placement_exams', 1);
        $this->assertDatabaseCount('placement_exam_levels', 3);
        $payload['submission_token'] = (string) Str::uuid();
        $payload['target_program_level_id'] = $levels->last()->id;
        $this->postJson(route('placement-exams.store', $student), $payload)->assertUnprocessable()->assertJsonValidationErrors('target_program_level_id');
        $this->assertSame($levels[3]->id, $contract->refresh()->current_program_level_id);
    }

    public function test_prior_approvals_are_preserved_and_failed_attempts_gain_a_separate_recognition(): void
    {
        [$user, $student, $program, $levels, $teacher, $contract] = $this->examContext();
        foreach (['approved', 'failed'] as $index => $status) {
            $module = $this->module($program, $levels[$index], 'Intento '.$status);
            AcademicModuleStudentAssignment::create(['company_id' => $student->company_id, 'academic_module_id' => $module->id,
                'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now(), 'released_at' => now()]);
            AcademicModuleStudentResult::create(['company_id' => $student->company_id, 'academic_module_id' => $module->id,
                'student_id' => $student->id, 'status' => $status, 'final_score' => $index === 0 ? 90 : 30,
                'finalized_by' => $user->id, 'finalized_at' => now()]);
        }
        $this->actingAs($user)->post(route('placement-exams.store', $student), $this->payload($contract, $levels->last(), $teacher))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('academic_module_student_results', 2);
        $this->assertDatabaseCount('placement_exam_levels', 5);
        $this->assertDatabaseMissing('placement_exam_levels', ['program_level_id' => $levels[0]->id]);
        $this->assertDatabaseHas('placement_exam_levels', ['program_level_id' => $levels[1]->id]);
        $this->assertDatabaseHas('academic_module_student_results', ['student_id' => $student->id, 'status' => 'failed', 'final_score' => 30]);
    }

    public function test_a_later_exam_does_not_duplicate_recognized_levels(): void
    {
        [$user, $student, , $levels, $teacher, $contract] = $this->examContext();
        $this->actingAs($user)->post(route('placement-exams.store', $student), $this->payload($contract, $levels[3], $teacher))->assertSessionHasNoErrors();
        $this->post(route('placement-exams.store', $student), $this->payload($contract->refresh(), $levels->last(), $teacher, 0))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('placement_exam_levels', 6);
        $this->assertSame('0.00', PlacementExam::latest('id')->first()->score);
        $this->assertSame('80.00', PlacementExam::oldest('id')->first()->score);
    }

    public function test_new_module_assignment_blocks_submission_and_removes_student_from_list(): void
    {
        [$user, $student, $program, $levels, $teacher, $contract] = $this->examContext();
        $module = $this->module($program, $levels[0], 'Abierto');
        AcademicModuleStudentAssignment::create(['company_id' => $student->company_id, 'academic_module_id' => $module->id,
            'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);
        $this->actingAs($user)->postJson(route('placement-exams.store', $student), $this->payload($contract, $levels->last(), $teacher))
            ->assertUnprocessable()->assertJsonValidationErrors('student');
        $this->get(route('placement-exams.index'))->assertOk()->assertDontSee('Lucía');
        $this->assertDatabaseCount('placement_exams', 0);
    }

    public function test_invalid_scores_and_missing_fields_are_rejected(): void
    {
        [$user, $student, , $levels, $teacher, $contract] = $this->examContext();
        $payload = $this->payload($contract, $levels->last(), $teacher);
        foreach ([-1, 101, '1.234', 'invalid'] as $score) {
            $this->actingAs($user)->postJson(route('placement-exams.store', $student), [...$payload, 'score' => $score])
                ->assertUnprocessable()->assertJsonValidationErrors('score');
        }
        $this->postJson(route('placement-exams.store', $student), [])->assertUnprocessable()
            ->assertJsonValidationErrors(['score', 'teacher_id', 'observation', 'enrollment_contract_id', 'target_program_level_id', 'submission_token']);
        $this->postJson(route('placement-exams.store', $student), [...$payload, 'observation' => str_repeat('x', 2001)])
            ->assertUnprocessable()->assertJsonValidationErrors('observation');
        $this->assertDatabaseCount('placement_exams', 0);
    }

    public function test_wrong_program_inactive_level_and_non_academic_teacher_are_rejected(): void
    {
        [$user, $student, $program, $levels, $teacher, $contract] = $this->examContext();
        $payload = $this->payload($contract, $levels->last(), $teacher);
        $otherProgram = Program::create(['company_id' => $student->company_id, 'title' => 'Francés', 'duration_months' => 12]);
        $otherLevel = ProgramLevel::create(['company_id' => $student->company_id, 'program_id' => $otherProgram->id, 'name' => 'Avanzado', 'position' => 8]);
        $this->actingAs($user)->postJson(route('placement-exams.store', $student), [...$payload, 'target_program_level_id' => $otherLevel->id])
            ->assertUnprocessable()->assertJsonValidationErrors('target_program_level_id');
        $levels->last()->update(['is_active' => false]);
        $this->postJson(route('placement-exams.store', $student), $payload)->assertUnprocessable()->assertJsonValidationErrors('target_program_level_id');
        $levels->last()->update(['is_active' => true]);
        $teacher->position->update(['is_academic' => false]);
        $teacher->position->area->update(['name' => 'Administración']);
        $this->postJson(route('placement-exams.store', $student), $payload)->assertUnprocessable()->assertJsonValidationErrors('teacher_id');
        $this->assertDatabaseCount('placement_exams', 0);
    }

    public function test_read_only_users_can_consult_but_cannot_record_and_other_companies_are_isolated(): void
    {
        [$user, $student, , $levels, $teacher, $contract] = $this->examContext();
        $user->revokePermissionTo('students.manage');
        $this->actingAs($user)->get(route('placement-exams.index'))->assertOk()->assertDontSee('Registrar examen');
        $this->get(route('placement-exams.history'))->assertOk();
        $this->get(route('placement-exams.create', $student))->assertForbidden();
        $this->postJson(route('placement-exams.store', $student), $this->payload($contract, $levels->last(), $teacher))->assertForbidden();
        $outsider = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $outsider->givePermissionTo(['students.view', 'students.manage']);
        $this->actingAs($outsider)->get(route('placement-exams.index'))->assertOk()->assertDontSee('Lucía');
        $this->get(route('placement-exams.create', $student))->assertNotFound();
        $this->postJson(route('placement-exams.store', $student), $this->payload($contract, $levels->last(), $teacher))->assertNotFound();
    }

    public function test_super_admin_contract_deletion_cascades_exams_and_recognitions(): void
    {
        [$user, $student, , $levels, $teacher, $contract] = $this->examContext();
        $this->actingAs($user)->post(route('placement-exams.store', $student), $this->payload($contract, $levels->last(), $teacher))->assertSessionHasNoErrors();
        Role::findOrCreate('super_admin');
        $user->assignRole('super_admin');
        $this->delete(route('super-admin.contracts.destroy', $contract))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('placement_exams', 0);
        $this->assertDatabaseCount('placement_exam_levels', 0);
        $this->assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
    }

    public function test_multiple_programs_require_an_explicit_contract_and_leave_other_contracts_unchanged(): void
    {
        [$user, $student, , $levels, $teacher, $contract] = $this->examContext();
        $otherProgram = Program::create(['company_id' => $student->company_id, 'title' => 'Francés', 'duration_months' => 12]);
        $otherLevel = ProgramLevel::create(['company_id' => $student->company_id, 'program_id' => $otherProgram->id, 'name' => 'Inicial francés', 'position' => 1]);
        $otherApplication = $contract->application->replicate();
        $otherApplication->program_id = $otherProgram->id;
        $otherApplication->save();
        $otherContract = $contract->replicate();
        $otherContract->program_id = $otherProgram->id;
        $otherContract->rectorate_application_id = $otherApplication->id;
        $otherContract->current_program_level_id = $otherLevel->id;
        $otherContract->save();
        $this->actingAs($user)->get(route('placement-exams.create', $student))->assertOk()
            ->assertSee('Francés')->assertDontSee('Registrar examen y actualizar nivel');
        $this->postJson(route('placement-exams.store', $student), [
            ...$this->payload($contract, $levels->last(), $teacher), 'enrollment_contract_id' => null,
        ])->assertUnprocessable()->assertJsonValidationErrors('enrollment_contract_id');
        $this->post(route('placement-exams.store', $student), $this->payload($contract, $levels->last(), $teacher))->assertSessionHasNoErrors();
        $this->assertSame($otherLevel->id, $otherContract->refresh()->current_program_level_id);
        $this->assertSame($levels->last()->id, $contract->refresh()->current_program_level_id);
    }

    public function test_inactive_or_pre_enrolled_students_cannot_record_and_unprivileged_users_cannot_consult(): void
    {
        [$user, $student, , $levels, $teacher, $contract] = $this->examContext();
        $payload = $this->payload($contract, $levels->last(), $teacher);
        $student->update(['is_active' => false]);
        $this->actingAs($user)->postJson(route('placement-exams.store', $student), $payload)->assertUnprocessable()->assertJsonValidationErrors('student');
        $student->update(['is_active' => true]);
        $contract->update(['status' => 'pre_enrolled']);
        $this->postJson(route('placement-exams.store', $student), $payload)->assertUnprocessable()->assertJsonValidationErrors('student');
        $this->get(route('placement-exams.index'))->assertOk()->assertDontSee('Lucía');
        $user->revokePermissionTo(['students.view', 'students.manage']);
        $this->get(route('placement-exams.index'))->assertForbidden();
        $this->get(route('placement-exams.history'))->assertForbidden();
        $this->assertDatabaseCount('placement_exams', 0);
    }

    public function test_missing_current_level_uses_first_program_level_and_external_teacher_is_rejected(): void
    {
        [$user, $student, , $levels, $teacher, $contract] = $this->examContext();
        $contract->update(['current_program_level_id' => null]);
        $payload = $this->payload($contract, $levels->last(), $teacher);
        $teacher->update(['company_id' => Company::factory()->create()->id]);
        $this->actingAs($user)->postJson(route('placement-exams.store', $student), $payload)->assertUnprocessable()->assertJsonValidationErrors('teacher_id');
        $teacher->update(['company_id' => $student->company_id]);
        $this->post(route('placement-exams.store', $student), $payload)->assertSessionHasNoErrors();
        $this->assertSame($levels[0]->id, PlacementExam::sole()->previous_program_level_id);
        $this->assertDatabaseCount('placement_exam_levels', 6);
    }

    private function payload(EnrollmentContract $contract, ProgramLevel $target, Personnel $teacher, int $score = 80): array
    {
        return ['enrollment_contract_id' => $contract->id, 'target_program_level_id' => $target->id,
            'expected_current_level_id' => $contract->current_program_level_id, 'teacher_id' => $teacher->id,
            'score' => $score, 'observation' => 'Buen dominio oral', 'submission_token' => (string) Str::uuid()];
    }

    private function examContext(): array
    {
        [$user, $student, $program] = $this->context();
        $levels = collect(range(1, 7))->map(fn ($position) => ProgramLevel::create([
            'company_id' => $student->company_id, 'program_id' => $program->id, 'name' => 'Nivel '.$position,
            'position' => $position, 'is_active' => true,
        ]));
        $contract = $student->contracts()->sole();
        $contract->update(['current_program_level_id' => $levels[0]->id]);
        $area = Area::create(['company_id' => $student->company_id, 'name' => 'Académica']);
        $position = Position::create(['company_id' => $student->company_id, 'area_id' => $area->id, 'name' => 'Docente', 'is_active' => true, 'is_academic' => true]);
        $teacher = Personnel::create(['company_id' => $student->company_id, 'position_id' => $position->id,
            'first_name' => 'Docente', 'paternal_surname' => 'Evaluador', 'identity_document' => 'T-1',
            'email' => 'docente@example.com', 'phone' => '70000001', 'is_active' => true]);

        return [$user, $student, $program, $levels, $teacher, $contract];
    }

    private function context(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('students.view');
        Permission::findOrCreate('students.manage');
        $user->givePermissionTo(['students.view', 'students.manage']);
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        $plan = Plan::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Regular', 'monthly_cost' => 300]);
        $student = $this->studentWithContract($program, $plan, 'enrolled', '2001', 'Lucía');

        return [$user, $student, $program, $plan];
    }

    private function studentWithContract(Program $program, Plan $plan, string $status, string $document, string $name): Student
    {
        $student = Student::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'identity_document' => $document, 'first_name' => $name, 'paternal_surname' => 'Pérez', 'birth_date' => '2010-01-01', 'gender' => 'Femenino', 'is_active' => true]);
        $customer = Customer::factory()->create(['company_id' => $program->company_id]);
        $application = RectorateApplication::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'customer_id' => $customer->id, 'program_id' => $program->id, 'plan_id' => $plan->id, 'student_id' => $student->id, 'first_name' => 'Titular', 'paternal_surname' => 'Pérez', 'birth_date' => '1980-01-01', 'email' => strtolower($name).'@example.com', 'phone' => '70000000', 'status' => 'completed']);
        EnrollmentContract::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'rectorate_application_id' => $application->id, 'student_id' => $student->id, 'program_id' => $program->id, 'plan_id' => $plan->id, 'contract_number' => random_int(1000, 9999), 'monthly_amount' => 300, 'status' => $status, 'confirmed_at' => now(), 'enrolled_at' => $status === 'enrolled' ? now() : null]);

        return $student;
    }

    private function module(Program $program, ProgramLevel $level, string $name): AcademicModule
    {
        return AcademicModule::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'program_id' => $program->id, 'program_level_id' => $level->id, 'name' => $name, 'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00', 'start_date' => today(), 'end_date' => today()->addMonth()]);
    }
}
