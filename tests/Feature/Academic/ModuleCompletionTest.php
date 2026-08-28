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
use App\Models\ProgramLevel;
use App\Models\RectorateApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_results_generate_one_charge_per_student_without_duplicates(): void
    {
        [$user, $module, $student, $contract] = $this->context();

        $this->actingAs($user)->get(route('teacher.modules.results.edit', $module))
            ->assertOk()->assertSee($student->first_name)->assertSee('Aprobado')->assertSee('Reprobado');
        $this->actingAs($user)->put(route('teacher.modules.results.update', $module), [
            'results' => [$student->id => 'approved'],
            'concepts' => [$student->id => 'Mensualidad'],
        ])->assertRedirect(route('teacher.modules.index'));

        $this->assertDatabaseHas('academic_module_student_results', ['academic_module_id' => $module->id, 'student_id' => $student->id, 'status' => 'approved']);
        $this->assertDatabaseHas('account_charges', ['enrollment_contract_id' => $contract->id, 'concept' => 'Mensualidad', 'amount' => '320.00', 'status' => 'pending']);
        $this->assertSame(2, $contract->charges()->count());

        $this->actingAs($user)->put(route('teacher.modules.results.update', $module), [
            'results' => [$student->id => 'failed'],
            'concepts' => [$student->id => 'Mensualidad módulo repetido'],
        ])->assertRedirect(route('teacher.modules.index'));
        $this->assertDatabaseHas('academic_module_student_results', ['academic_module_id' => $module->id, 'student_id' => $student->id, 'status' => 'failed']);
        $this->assertDatabaseHas('account_charges', ['enrollment_contract_id' => $contract->id, 'concept' => 'Mensualidad módulo repetido']);
        $this->assertSame(2, $contract->charges()->count());
    }

    private function context(): array
    {
        $company = Company::factory()->create();
        Permission::findOrCreate('teaching.view');
        Permission::findOrCreate('teaching.manage');
        $area = Area::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Académico', 'is_active' => true]);
        $position = Position::withoutGlobalScope('company')->create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Docente', 'is_academic' => true, 'is_active' => true]);
        $personnel = Personnel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'position_id' => $position->id, 'first_name' => 'Ana', 'paternal_surname' => 'Flores', 'identity_document' => '9101', 'phone' => '70000001', 'email' => 'ana@example.com', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id, 'personnel_id' => $personnel->id]);
        $user->givePermissionTo(['teaching.view', 'teaching.manage']);
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        $level = ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'name' => 'Básico 1', 'position' => 1]);
        $module = AcademicModule::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'program_level_id' => $level->id, 'name' => 'Módulo Básico 1', 'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00', 'start_date' => today()->subMonth(), 'end_date' => today()->subDay()]);
        AcademicModuleTeacherAssignment::withoutGlobalScope('company')->create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'personnel_id' => $personnel->id, 'assigned_at' => now()]);
        $student = Student::withoutGlobalScope('company')->create(['company_id' => $company->id, 'identity_document' => '3101', 'first_name' => 'Lucía', 'paternal_surname' => 'Pérez', 'birth_date' => '2010-01-01', 'gender' => 'Femenino', 'is_active' => true]);
        AcademicModuleStudentAssignment::withoutGlobalScope('company')->create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);
        $plan = Plan::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Regular', 'monthly_cost' => 320]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $application = RectorateApplication::withoutGlobalScope('company')->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'program_id' => $program->id, 'plan_id' => $plan->id, 'student_id' => $student->id, 'first_name' => 'Titular', 'paternal_surname' => 'Pérez', 'birth_date' => '1980-01-01', 'email' => 'titular@example.com', 'phone' => '70000002', 'status' => 'completed']);
        $contract = EnrollmentContract::withoutGlobalScope('company')->create(['company_id' => $company->id, 'rectorate_application_id' => $application->id, 'student_id' => $student->id, 'program_id' => $program->id, 'plan_id' => $plan->id, 'contract_number' => 1, 'monthly_amount' => 320, 'status' => 'enrolled', 'confirmed_at' => now(), 'enrolled_at' => now()]);
        AccountCharge::withoutGlobalScope('company')->create(['company_id' => $company->id, 'enrollment_contract_id' => $contract->id, 'plan_id' => $plan->id, 'concept' => 'Mensualidad', 'period' => today()->startOfMonth(), 'due_date' => today(), 'amount' => 320, 'paid_amount' => 320, 'status' => 'paid']);

        return [$user, $module, $student, $contract];
    }
}
