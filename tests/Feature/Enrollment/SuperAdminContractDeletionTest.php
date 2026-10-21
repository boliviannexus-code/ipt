<?php

namespace Tests\Feature\Enrollment;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentResult;
use App\Models\AccountCharge;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Customer;
use App\Models\EnrollmentContract;
use App\Models\Plan;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\RectorateApplication;
use App\Models\Student;
use App\Models\User;
use App\Services\Enrollment\AccountPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminContractDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_access_the_menu_and_delete(): void
    {
        [$user, $contract] = $this->contract();
        Role::findOrCreate('admin');
        $user->assignRole('admin');
        $this->actingAs($user)->get(route('super-admin.contracts.index'))->assertForbidden();
        $this->actingAs($user)->delete(route('super-admin.contracts.destroy', $contract))->assertForbidden();
        $this->assertDatabaseHas('enrollment_contracts', ['id' => $contract->id]);
    }

    public function test_deletes_contract_application_charges_and_history_but_keeps_student(): void
    {
        [$user, $contract] = $this->contract();
        Role::findOrCreate('super_admin');
        $user->assignRole('super_admin');
        $contract->planHistory()->create(['plan_id' => $contract->plan_id, 'monthly_amount' => 250, 'effective_from' => today()]);
        $this->actingAs($user)->get(route('super-admin.contracts.index'))->assertOk()->assertSee('Eliminar contrato');
        $this->actingAs($user)->delete(route('super-admin.contracts.destroy', $contract))
            ->assertRedirect(route('super-admin.contracts.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('enrollment_contracts', ['id' => $contract->id]);
        $this->assertDatabaseMissing('rectorate_applications', ['id' => $contract->rectorate_application_id]);
        $this->assertDatabaseMissing('account_charges', ['enrollment_contract_id' => $contract->id]);
        $this->assertDatabaseMissing('contract_plan_histories', ['enrollment_contract_id' => $contract->id]);
        $this->assertDatabaseHas('students', ['id' => $contract->student_id, 'deleted_at' => null]);
    }

    public function test_payment_blocks_deletion_even_by_direct_request(): void
    {
        [$user, $contract] = $this->contract();
        CashRegister::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
        app(AccountPaymentService::class)->record($user, $contract, '25.00', ['payment_method_code' => 1]);
        Role::findOrCreate('super_admin');
        $user->assignRole('super_admin');
        $this->actingAs($user)->get(route('super-admin.contracts.index'))->assertOk()->assertSee('Eliminación bloqueada');
        $this->actingAs($user)->deleteJson(route('super-admin.contracts.destroy', $contract))->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->assertDatabaseHas('enrollment_contracts', ['id' => $contract->id]);
        $this->assertDatabaseHas('rectorate_applications', ['id' => $contract->rectorate_application_id]);
        $this->assertDatabaseCount('account_payments', 1);
    }

    public function test_paid_amount_without_payment_still_blocks_deletion(): void
    {
        [$user, $contract] = $this->contract();
        Role::findOrCreate('super_admin');
        $user->assignRole('super_admin');
        $contract->charges()->update(['paid_amount' => 1, 'status' => 'partial']);
        $this->actingAs($user)->deleteJson(route('super-admin.contracts.destroy', $contract))->assertUnprocessable();
        $this->assertDatabaseHas('enrollment_contracts', ['id' => $contract->id]);
    }

    public function test_contract_from_another_active_company_cannot_be_deleted(): void
    {
        [$user, $contract] = $this->contract();
        Role::findOrCreate('super_admin');
        $user->assignRole('super_admin');
        $other = Company::factory()->create();
        $this->actingAs($user)->withSession(['active_company_id' => $other->id])
            ->delete(route('super-admin.contracts.destroy', $contract))->assertNotFound();
        $this->assertDatabaseHas('enrollment_contracts', ['id' => $contract->id]);
    }

    public function test_free_contract_without_collections_can_be_deleted(): void
    {
        [$user, $contract] = $this->contract();
        Role::findOrCreate('super_admin');
        $user->assignRole('super_admin');
        $contract->update(['monthly_amount' => 0]);
        $contract->charges()->update(['amount' => 0, 'paid_amount' => 0, 'status' => 'paid']);
        $this->actingAs($user)->delete(route('super-admin.contracts.destroy', $contract))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('enrollment_contracts', ['id' => $contract->id]);
    }

    public function test_charge_linked_academic_results_are_deleted_and_shared_records_preserved(): void
    {
        [$user, $contract] = $this->contract();
        Role::findOrCreate('super_admin');
        $user->assignRole('super_admin');
        $level = ProgramLevel::create([
            'company_id' => $contract->company_id, 'program_id' => $contract->program_id,
            'name' => 'Inicial', 'position' => 1,
        ]);
        $module = AcademicModule::create([
            'company_id' => $contract->company_id, 'program_id' => $contract->program_id,
            'program_level_id' => $level->id, 'name' => 'Módulo de prueba',
            'modality' => 'presential', 'starts_at' => '08:00', 'ends_at' => '10:00',
            'start_date' => today(), 'end_date' => today()->addMonth(),
        ]);
        $result = AcademicModuleStudentResult::create([
            'company_id' => $contract->company_id, 'academic_module_id' => $module->id,
            'student_id' => $contract->student_id, 'status' => 'failed',
            'account_charge_id' => $contract->charges()->sole()->id,
            'finalized_by' => $user->id, 'finalized_at' => now(),
        ]);
        $this->actingAs($user)->delete(route('super-admin.contracts.destroy', $contract))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('academic_module_student_results', ['id' => $result->id]);
        $this->assertDatabaseHas('academic_modules', ['id' => $module->id]);
        $this->assertDatabaseHas('students', ['id' => $contract->student_id]);
        $this->assertDatabaseHas('customers', ['id' => Customer::first()->id]);
    }

    private function contract(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $plan = Plan::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Mensual', 'monthly_cost' => 250]);
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        $program->plans()->attach($plan);
        $student = Student::withoutGlobalScope('company')->create([
            'company_id' => $company->id, 'identity_document' => '1234567', 'first_name' => 'Ana',
            'paternal_surname' => 'Pérez', 'birth_date' => '2010-01-01', 'gender' => 'Femenino',
        ]);
        $application = RectorateApplication::withoutGlobalScope('company')->create([
            'company_id' => $company->id, 'customer_id' => $customer->id, 'program_id' => $program->id,
            'plan_id' => $plan->id, 'student_id' => $student->id, 'identity_document' => '7654321',
            'first_name' => 'María', 'paternal_surname' => 'Pérez', 'birth_date' => '1980-01-01',
            'email' => 'maria@example.com', 'phone' => '70000000', 'status' => 'completed', 'current_step' => 4,
        ]);
        $contract = EnrollmentContract::withoutGlobalScope('company')->create([
            'company_id' => $company->id, 'rectorate_application_id' => $application->id, 'student_id' => $student->id,
            'program_id' => $program->id, 'plan_id' => $plan->id, 'contract_number' => 1,
            'monthly_amount' => 250, 'status' => 'pre_enrolled', 'confirmed_at' => now(),
        ]);
        AccountCharge::withoutGlobalScope('company')->create([
            'company_id' => $company->id, 'enrollment_contract_id' => $contract->id, 'plan_id' => $plan->id,
            'period' => today()->startOfMonth(), 'due_date' => today(), 'amount' => 250, 'paid_amount' => 0, 'status' => 'pending',
        ]);

        return [$user, $contract];
    }
}
