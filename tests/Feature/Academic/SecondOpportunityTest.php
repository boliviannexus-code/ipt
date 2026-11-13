<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentAssignment;
use App\Models\AcademicModuleTeacherAssignment;
use App\Models\AccountCharge;
use App\Models\Area;
use App\Models\Company;
use App\Models\Customer;
use App\Models\EnrollmentContract;
use App\Models\Personnel;
use App\Models\Plan;
use App\Models\Position;
use App\Models\Program;
use App\Models\ProgramGradingScheme;
use App\Models\ProgramLevel;
use App\Models\RectorateApplication;
use App\Models\Student;
use App\Models\StudentSingleGrade;
use App\Models\User;
use App\Services\Academic\ModuleCompletionService;
use App\Services\Enrollment\EnrollmentCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SecondOpportunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_passing_at_the_saved_threshold_advances_once_and_preserves_academic_and_financial_data(): void
    {
        [$user, $module, $student, $contract, $result] = $this->context();
        $originalClose = $module->closed_at;
        $originalFinalizedAt = $result->finalized_at;
        $module->update(['promoted_at' => now()]);
        $result->gradingScheme->update(['passing_score' => 80]);
        $this->get(route('academic.second-opportunities.index'))->assertOk()->assertSee('Registrar 2T');
        $this->get(route('academic.second-opportunities.create', $result))->assertOk()->assertSee('Nota original');
        $this->post(route('academic.second-opportunities.preview', $result), ['score' => '51.00', 'reason' => 'Evaluación de recuperación'])
            ->assertOk()->assertSee('Aprobado')->assertSee('Básico 2');
        $this->assertDatabaseCount('academic_second_opportunities', 0);
        $this->post(route('academic.second-opportunities.store', $result), ['score' => '51.00', 'reason' => 'Evaluación de recuperación'])
            ->assertRedirect(route('academic.second-opportunities.index'));
        $this->assertSame('approved', $result->refresh()->status);
        $this->assertSame('51.00', $result->final_score);
        $this->assertSame(2, $contract->refresh()->currentProgramLevel->position);
        $this->assertNull($student->fresh()->currentModuleAssignment);
        $this->assertTrue($originalClose->equalTo($module->fresh()->closed_at));
        $this->assertTrue($originalFinalizedAt->equalTo($result->finalized_at));
        $this->assertDatabaseHas('student_single_grades', ['student_id' => $student->id, 'score' => 30]);
        $this->assertSame(1, $contract->charges()->count());
        $this->assertDatabaseHas('academic_second_opportunities', ['original_score' => 30, 'score' => 51, 'recorded_by' => $user->id, 'original_status' => 'failed', 'status' => 'approved']);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 90, 'reason' => 'Segundo envío'])->assertUnprocessable();
        $this->assertDatabaseCount('academic_second_opportunities', 1);
        $this->post(route('academic.second-opportunities.store', $result), ['score' => 90, 'reason' => 'Segundo envío'])
            ->assertRedirect(route('academic.second-opportunities.index'))->assertSessionHasErrors('result');
        $this->get(route('teacher.modules.results.edit', $module))->assertOk()->assertSee('2T · Original:')->assertSee('51,00');
        $trackingResponse = $this->get(route('teacher.tracking.show', $module));
        $trackingResponse->assertOk()->assertViewHas('passingScore', 51.0)->assertSee('2T · Original:')->assertSee('51,00');
        $this->get(route('students.kardex.show', $student))->assertOk()->assertSee('2T · Original:')->assertSee('51,00');
        $this->get(route('teacher.tracking.pdf', $module))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('students.kardex.pdf', $student))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('academic.second-opportunities.index', ['search' => $student->identity_document, 'program_id' => $module->program_id, 'module_id' => $module->id]))
            ->assertOk()->assertSee('Evaluación de recuperación')->assertDontSee('Registrar 2T');
        Permission::findOrCreate('students.manage');
        $user->givePermissionTo('students.manage');
        $nextModule = $module->replicate();
        $nextModule->fill(['name' => 'Siguiente módulo disponible', 'program_level_id' => $contract->current_program_level_id,
            'start_date' => today(), 'end_date' => today()->addMonth(), 'closed_at' => null, 'promoted_at' => null])->save();
        $this->postJson(route('students.modules.store', $student), ['academic_module_id' => $nextModule->id])->assertOk();
        $this->assertSame($nextModule->id, $student->fresh()->currentModuleAssignment->academic_module_id);
        $this->assertSame(1, $contract->charges()->count());
    }

    public function test_a_failed_attempt_keeps_the_level_and_cannot_be_repeated(): void
    {
        [, $module, , $contract, $result] = $this->context();
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => '0', 'reason' => 'No alcanzó el mínimo'])->assertRedirect();
        $this->assertSame('failed', $result->refresh()->status);
        $this->assertSame($module->program_level_id, $contract->refresh()->current_program_level_id);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 100, 'reason' => 'Reintento'])->assertUnprocessable();
    }

    public function test_score_and_reason_validation(): void
    {
        [, , , , $result] = $this->context();
        foreach ([-1, 101, '50.123', 'no es nota', null] as $score) {
            $this->postJson(route('academic.second-opportunities.store', $result), ['score' => $score, 'reason' => 'Recuperación'])
                ->assertUnprocessable()->assertJsonValidationErrors('score');
        }
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 60, 'reason' => '   '])
            ->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->assertDatabaseCount('academic_second_opportunities', 0);
    }

    public function test_assignment_after_opening_the_form_blocks_submission_even_if_released(): void
    {
        [$user, $module, $student, , $result] = $this->context();
        $this->get(route('academic.second-opportunities.create', $result))->assertOk();
        $other = $module->replicate();
        $other->fill(['name' => 'Otro módulo', 'closed_at' => null, 'promoted_at' => null])->save();
        $assignment = AcademicModuleStudentAssignment::create(['company_id' => $module->company_id, 'academic_module_id' => $other->id, 'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()->addSecond()]);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Recuperación'])->assertUnprocessable();
        $assignment->update(['released_at' => now()->addSeconds(2)]);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Recuperación'])->assertUnprocessable();
        $this->get(route('academic.second-opportunities.index'))->assertOk()->assertDontSee('Registrar 2T');
    }

    public function test_ineligible_statuses_levels_and_later_results_are_rejected(): void
    {
        [, $module, $student, $contract, $result] = $this->context();
        foreach (['suspended', 'withdrawn', 'inactive', 'finished'] as $status) {
            $contract->update(['status' => $status]);
            $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Recuperación'])->assertUnprocessable();
        }
        $contract->update(['status' => 'enrolled']);
        $student->update(['is_active' => false]);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Recuperación'])->assertUnprocessable();
        $student->update(['is_active' => true]);
        $result->update(['status' => 'approved']);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Recuperación'])->assertUnprocessable();
        $result->update(['status' => 'failed']);
        $module->update(['closed_at' => null]);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Recuperación'])->assertUnprocessable();
        $module->update(['closed_at' => now()]);
        $contract->update(['current_program_level_id' => ProgramLevel::where('position', 2)->value('id')]);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Recuperación'])->assertUnprocessable();
        $contract->update(['current_program_level_id' => $module->program_level_id]);
        $other = $module->replicate();
        $other->fill(['name' => 'Curso posterior'])->save();
        $later = $result->replicate();
        $later->fill(['academic_module_id' => $other->id, 'finalized_at' => now()->addDay()])->save();
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Recuperación'])->assertUnprocessable();
        $this->assertDatabaseCount('academic_second_opportunities', 0);
    }

    public function test_permission_and_company_isolation_apply_to_all_endpoints(): void
    {
        [$user, , , , $result] = $this->context();
        $user->revokePermissionTo('second-opportunities.manage');
        $this->get(route('academic.second-opportunities.index'))->assertForbidden();
        $this->get(route('academic.second-opportunities.create', $result))->assertForbidden();
        $this->postJson(route('academic.second-opportunities.preview', $result), ['score' => 70, 'reason' => 'Prueba'])->assertForbidden();
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Prueba'])->assertForbidden();
        $other = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $other->givePermissionTo('second-opportunities.manage');
        $this->actingAs($other)->get(route('academic.second-opportunities.index'))->assertOk()->assertDontSee('Lucía');
        $this->get(route('academic.second-opportunities.create', $result))->assertNotFound();
        $this->postJson(route('academic.second-opportunities.preview', $result), ['score' => 70, 'reason' => 'Prueba'])->assertNotFound();
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Prueba'])->assertNotFound();
    }

    public function test_last_level_finishes_only_when_academic_and_payment_requirements_are_met(): void
    {
        [, $module, , $contract, $result] = $this->context();
        ProgramLevel::where('program_id', $module->program_id)->where('position', 2)->update(['is_active' => false]);
        $contract->charges()->update(['paid_amount' => 0, 'status' => 'pending']);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 100, 'reason' => 'Último nivel'])->assertRedirect();
        $this->assertSame('enrolled', $contract->refresh()->status);
        $contract->charges()->update(['paid_amount' => 320, 'status' => 'paid']);
        app(EnrollmentCompletionService::class)->evaluate($contract);
        $this->assertSame('finished', $contract->refresh()->status);
    }

    public function test_last_level_with_all_requirements_paid_finishes_immediately(): void
    {
        [, $module, , $contract, $result] = $this->context();
        ProgramLevel::where('program_id', $module->program_id)->where('position', 2)->update(['is_active' => false]);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 80, 'reason' => 'Recuperación final'])->assertRedirect();
        $this->assertSame('finished', $contract->refresh()->status);
        $this->assertNotNull($contract->completed_at);
    }

    public function test_active_assignment_blocks_even_when_it_started_before_the_course_closed(): void
    {
        [$user, $module, $student, , $result] = $this->context();
        $student->moduleAssignments()->where('academic_module_id', $module->id)->update(['released_at' => null]);
        $this->postJson(route('academic.second-opportunities.store', $result), ['score' => 70, 'reason' => 'Recuperación'])->assertUnprocessable();
        $this->assertDatabaseCount('academic_second_opportunities', 0);
    }

    private function context(): array
    {
        $company = Company::factory()->create();
        Permission::findOrCreate('teaching.view');
        Permission::findOrCreate('teaching.manage');
        Permission::findOrCreate('academic-modules.view');
        Permission::findOrCreate('academic-modules.manage');
        $area = Area::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Académico', 'is_active' => true]);
        $position = Position::withoutGlobalScope('company')->create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Docente', 'is_academic' => true, 'is_active' => true]);
        $personnel = Personnel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'position_id' => $position->id, 'first_name' => 'Ana', 'paternal_surname' => 'Flores', 'identity_document' => '9101', 'phone' => '70000001', 'email' => 'ana@example.com', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id, 'personnel_id' => $personnel->id]);
        $user->givePermissionTo(['teaching.view', 'teaching.manage', 'academic-modules.view', 'academic-modules.manage']);
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        $level = ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'name' => 'Básico 1', 'position' => 1]);
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Ponderación de prueba', 'version' => 1, 'status' => 'draft', 'is_active' => false]);
        $level->update(['program_grading_scheme_id' => $scheme->id]);
        ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'name' => 'Básico 2', 'position' => 2]);
        $module = AcademicModule::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'program_level_id' => $level->id, 'name' => 'Módulo Básico 1', 'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00', 'start_date' => today()->subMonth(), 'end_date' => today()->subDay()]);
        AcademicModuleTeacherAssignment::withoutGlobalScope('company')->create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'personnel_id' => $personnel->id, 'assigned_at' => now()]);
        $student = Student::withoutGlobalScope('company')->create(['company_id' => $company->id, 'identity_document' => '3101', 'first_name' => 'Lucía', 'paternal_surname' => 'Pérez', 'birth_date' => '2010-01-01', 'gender' => 'Femenino', 'is_active' => true]);
        AcademicModuleStudentAssignment::withoutGlobalScope('company')->create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);
        $plan = Plan::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Regular', 'monthly_cost' => 320]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $application = RectorateApplication::withoutGlobalScope('company')->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'program_id' => $program->id, 'plan_id' => $plan->id, 'student_id' => $student->id, 'first_name' => 'Titular', 'paternal_surname' => 'Pérez', 'birth_date' => '1980-01-01', 'email' => 'titular@example.com', 'phone' => '70000002', 'status' => 'completed']);
        $contract = EnrollmentContract::withoutGlobalScope('company')->create(['company_id' => $company->id, 'rectorate_application_id' => $application->id, 'student_id' => $student->id, 'program_id' => $program->id, 'current_program_level_id' => $level->id, 'plan_id' => $plan->id, 'contract_number' => 1, 'monthly_amount' => 320, 'status' => 'enrolled', 'confirmed_at' => now(), 'enrolled_at' => now()]);
        AccountCharge::withoutGlobalScope('company')->create(['company_id' => $company->id, 'enrollment_contract_id' => $contract->id, 'plan_id' => $plan->id, 'concept' => 'Mensualidad', 'period' => today()->startOfMonth(), 'due_date' => today(), 'amount' => 320, 'paid_amount' => 320, 'status' => 'paid']);

        $contract->update(['confirmed_at' => now()->subMonth()]);
        $scheme->update(['status' => 'finalized', 'is_active' => true, 'passing_score' => 51, 'finalized_at' => now()]);
        $component = $scheme->components()->create(['company_id' => $company->id, 'name' => 'Examen', 'weight' => 100, 'frequency' => 'single', 'skill_mode' => 'single_skill', 'scoring_method' => 'percentage', 'position' => 1]);
        $skill = $component->skills()->create(['company_id' => $company->id, 'name' => 'Examen', 'weight' => 100, 'position' => 1]);
        StudentSingleGrade::create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'program_grading_skill_id' => $skill->id, 'student_id' => $student->id, 'score' => 30, 'graded_by' => $user->id, 'graded_at' => now()]);
        $this->actingAs($user);
        app(ModuleCompletionService::class)->complete($user, $module);
        Permission::findOrCreate('second-opportunities.manage');
        Permission::findOrCreate('students.view');
        $user->givePermissionTo(['second-opportunities.manage', 'students.view']);
        $module->refresh();
        $result = $module->studentResults()->sole();

        return [$user, $module, $student, $contract, $result];
    }
}
