<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicModule;
use App\Models\Company;
use App\Models\Customer;
use App\Models\EnrollmentContract;
use App\Models\Plan;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\RectorateApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StudentModuleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_officially_enrolled_students_are_listed(): void
    {
        [$user, $student, $program, $plan] = $this->context();
        $preEnrolled = $this->studentWithContract($program, $plan, 'pre_enrolled', '2002', 'Pedro');

        $this->actingAs($user)->get(route('students.index'))
            ->assertOk()
            ->assertSee($student->first_name)
            ->assertDontSee($preEnrolled->first_name);
    }

    public function test_student_can_only_be_assigned_to_a_current_module_in_enrolled_program(): void
    {
        [$user, $student, $program] = $this->context();
        $level = ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'program_id' => $program->id, 'name' => 'Básico 1', 'position' => 1]);
        $module = $this->module($program, $level, 'Módulo Básico 1');
        $otherProgram = Program::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'title' => 'Francés', 'duration_months' => 12]);
        $otherLevel = ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $program->company_id, 'program_id' => $otherProgram->id, 'name' => 'Inicial', 'position' => 1]);
        $otherModule = $this->module($otherProgram, $otherLevel, 'Módulo Inicial');

        $this->actingAs($user)->get(route('students.modules.create', $student), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('Módulo Básico 1')->assertDontSee('Módulo Inicial');
        $this->actingAs($user)->postJson(route('students.modules.store', $student), ['academic_module_id' => $module->id])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('academic_module_student_assignments', ['student_id' => $student->id, 'academic_module_id' => $module->id]);

        $this->actingAs($user)->postJson(route('students.modules.store', $student), ['academic_module_id' => $otherModule->id])
            ->assertUnprocessable()->assertJsonValidationErrors('academic_module_id');
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
