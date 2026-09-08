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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_results_are_calculated_from_weightings_without_generating_charges(): void
    {
        [$user, $module, $student, $contract] = $this->context();
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->where('program_id', $module->program_id)->firstOrFail();
        $scheme->update(['passing_score' => 70, 'status' => 'finalized', 'is_active' => true, 'finalized_at' => now()]);
        $component = $scheme->components()->create(['company_id' => $module->company_id, 'name' => 'Examen final', 'weight' => 100, 'frequency' => 'single', 'skill_mode' => 'single_skill', 'scoring_method' => 'percentage', 'position' => 1]);
        $skill = $component->skills()->create(['company_id' => $module->company_id, 'name' => 'Comprensión', 'weight' => 100, 'position' => 1]);
        StudentSingleGrade::withoutGlobalScope('company')->create(['company_id' => $module->company_id, 'academic_module_id' => $module->id, 'program_grading_skill_id' => $skill->id, 'student_id' => $student->id, 'score' => 82.50, 'graded_by' => $user->id, 'graded_at' => now()]);

        $this->actingAs($user)->get(route('teacher.modules.index'))
            ->assertOk()->assertSee('Finalizar curso');
        $this->actingAs($user)->get(route('teacher.modules.results.edit', $module))
            ->assertOk()->assertSee($student->first_name)->assertSee('82,50')->assertSee('Aprobado')->assertSee('Examen final');
        $this->actingAs($user)->put(route('teacher.modules.results.update', $module))->assertRedirect(route('teacher.modules.index'));

        $this->assertDatabaseHas('academic_module_student_results', ['academic_module_id' => $module->id, 'student_id' => $student->id, 'program_grading_scheme_id' => $scheme->id, 'status' => 'approved', 'final_score' => 82.50, 'passing_score' => 70]);
        $this->assertNotNull($module->refresh()->closed_at);
        $this->assertSame($user->id, $module->closed_by);
        $this->assertNotNull($student->moduleAssignments()->where('academic_module_id', $module->id)->firstOrFail()->released_at);
        $this->assertSame(2, $contract->refresh()->currentProgramLevel->position);
        $this->assertNull($student->fresh()->currentModuleAssignment);
        $this->assertSame(1, $contract->charges()->count());
        $result = $module->studentResults()->firstOrFail();
        $this->assertSame('Examen final', $result->grading_details[0]['name']);
        $this->assertSame(82.5, $result->grading_details[0]['average']);

        $this->actingAs($user)->get(route('academic.promotions.index'))
            ->assertOk()
            ->assertSee('Promover módulo')
            ->assertSee($module->name)
            ->assertSee(route('academic.promotions.create', $module), false);

        $startDate = today()->toDateString();
        $endDate = today()->addMonth()->toDateString();
        $this->actingAs($user)->get(route('academic.promotions.create', $module))
            ->assertOk()
            ->assertSee('Paso 1 de 2')
            ->assertSee('Básico 2');
        $this->actingAs($user)->post(route('academic.promotions.students', $module), [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ])->assertOk()->assertSee('Paso 2 de 2')->assertSee($student->first_name)->assertSee('Disponible');
        $this->actingAs($user)->post(route('academic.promotions.store', $module), [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'student_ids' => [$student->id],
        ])->assertRedirect(route('academic.modules.index'));

        $promotedModule = AcademicModule::withoutGlobalScope('company')->where('id', '!=', $module->id)->firstOrFail();
        $this->assertSame(2, $promotedModule->level->position);
        $this->assertSame($module->modality, $promotedModule->modality);
        $this->assertDatabaseHas('academic_module_student_assignments', ['academic_module_id' => $promotedModule->id, 'student_id' => $student->id, 'released_at' => null]);
        $this->assertSame(2, $contract->charges()->count());
        $this->assertDatabaseHas('account_charges', ['enrollment_contract_id' => $contract->id, 'concept' => 'Mensualidad - Módulo Básico 2', 'status' => 'pending']);

        $this->actingAs($user)->put(route('teacher.modules.results.update', $module))
            ->assertSessionHasErrors('results');
        $this->assertDatabaseHas('academic_module_student_results', ['academic_module_id' => $module->id, 'student_id' => $student->id, 'status' => 'approved']);
        $this->assertSame(2, $contract->charges()->count());
    }

    public function test_failed_student_is_released_and_keeps_the_same_level(): void
    {
        [$user, $module, $student, $contract] = $this->context();
        $scheme = ProgramGradingScheme::withoutGlobalScope('company')->where('program_id', $module->program_id)->firstOrFail();
        $scheme->update(['passing_score' => 70, 'status' => 'finalized', 'is_active' => true, 'finalized_at' => now()]);
        $component = $scheme->components()->create(['company_id' => $module->company_id, 'name' => 'Examen final', 'weight' => 100, 'frequency' => 'single', 'skill_mode' => 'single_skill', 'scoring_method' => 'percentage', 'position' => 1]);
        $skill = $component->skills()->create(['company_id' => $module->company_id, 'name' => 'Comprensión', 'weight' => 100, 'position' => 1]);
        StudentSingleGrade::withoutGlobalScope('company')->create(['company_id' => $module->company_id, 'academic_module_id' => $module->id, 'program_grading_skill_id' => $skill->id, 'student_id' => $student->id, 'score' => 60, 'graded_by' => $user->id, 'graded_at' => now()]);

        $this->actingAs($user)->put(route('teacher.modules.results.update', $module))
            ->assertRedirect(route('teacher.modules.index'));

        $this->assertDatabaseHas('academic_module_student_results', [
            'academic_module_id' => $module->id,
            'student_id' => $student->id,
            'status' => 'failed',
            'final_score' => 60,
        ]);
        $this->assertNotNull($student->moduleAssignments()->where('academic_module_id', $module->id)->firstOrFail()->released_at);
        $this->assertSame(1, $contract->refresh()->currentProgramLevel->position);
        $this->assertNull($student->fresh()->currentModuleAssignment);

        $this->actingAs($user)->post(route('academic.promotions.students', $module), [
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonth()->toDateString(),
        ])->assertOk()
            ->assertSee($student->first_name)
            ->assertSee('No pertenece al nivel destino');

        Permission::findOrCreate('students.manage');
        $user->givePermissionTo('students.manage');
        $repeatModule = AcademicModule::withoutGlobalScope('company')->create([
            'company_id' => $module->company_id,
            'program_id' => $module->program_id,
            'program_level_id' => $module->program_level_id,
            'name' => 'Módulo Básico 1 - Repetición',
            'modality' => 'virtual',
            'starts_at' => '18:00',
            'ends_at' => '20:00',
            'start_date' => today(),
            'end_date' => today()->addMonth(),
        ]);
        $this->actingAs($user)->postJson(route('students.modules.store', $student), ['academic_module_id' => $repeatModule->id])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('academic_module_student_assignments', [
            'academic_module_id' => $repeatModule->id,
            'student_id' => $student->id,
            'released_at' => null,
        ]);
    }

    public function test_finish_course_button_is_disabled_until_the_module_end_date(): void
    {
        [$user, $module] = $this->context();
        $module->update(['end_date' => today()->addDay()]);

        $this->actingAs($user)->get(route('teacher.modules.index'))
            ->assertOk()
            ->assertSee('Finalizar curso')
            ->assertSee('Disponible desde el '.$module->end_date->format('d/m/Y'))
            ->assertSee('disabled', false);

        $this->actingAs($user)->get(route('teacher.modules.results.edit', $module))->assertStatus(409);
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

        return [$user, $module, $student, $contract];
    }
}
