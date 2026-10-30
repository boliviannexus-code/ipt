<?php

namespace Tests\Feature\Enrollment;

use App\Models\AccountCharge;
use App\Models\AdditionalService;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Customer;
use App\Models\EnrollmentContract;
use App\Models\Plan;
use App\Models\Program;
use App\Models\RectorateApplication;
use App\Models\Student;
use App\Models\User;
use App\Services\Enrollment\AccountPaymentService;
use App\Services\Enrollment\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdditionalChargeTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_can_be_created_edited_and_deactivated_without_changing_posted_charges(): void
    {
        [$user, $contract] = $this->context();
        $this->actingAs($user)->get(route('additional-services.create'))->assertOk();
        $this->post(route('additional-services.store'), ['title' => 'Certificado', 'detail' => 'Emisión de certificado', 'cost' => '75.50', 'is_active' => 1])->assertSessionHasNoErrors();
        $service = AdditionalService::sole();
        $payload = $this->payload($contract, $service);
        $this->post(route('additional-charges.store', $contract->student_id), $payload)->assertSessionHasNoErrors();
        $this->put(route('additional-services.update', $service), ['title' => 'Certificado nuevo', 'detail' => 'Nuevo detalle', 'cost' => '90', 'is_active' => 0])->assertSessionHasNoErrors();
        $charge = AccountCharge::where('charge_type', 'additional')->sole();
        $this->assertSame('75.50', $charge->amount);
        $this->assertSame('Certificado', $charge->concept);
        $this->assertSame('Emisión de certificado', $charge->detail);
        $this->postJson(route('additional-charges.store', $contract->student_id), $this->payload($contract, $service))->assertUnprocessable()->assertJsonValidationErrors('additional_service_id');
        $this->get(route('additional-charges.show', $contract->student_id))->assertOk()->assertSee('Certificado')->assertSee('75,50');
    }

    public function test_multiple_additional_charges_coexist_with_monthly_charge_and_retries_do_not_duplicate(): void
    {
        [$user, $contract] = $this->context();
        $service = $this->service($contract, '40.00');
        $payload = $this->payload($contract, $service);
        $this->actingAs($user)->post(route('additional-charges.store', $contract->student_id), $payload)->assertSessionHasNoErrors();
        $this->post(route('additional-charges.store', $contract->student_id), $payload)->assertSessionHasNoErrors();
        $this->post(route('additional-charges.store', $contract->student_id), $this->payload($contract, $service))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('account_charges', 3);
        $this->assertSame(2, AccountCharge::where('charge_type', 'additional')->whereNull('period')->count());
        $monthly = app(ContractService::class)->generateMonthlyCharge($contract, today());
        $this->assertSame('monthly', $monthly->charge_type);
        $this->assertSame('250.00', $monthly->amount);
        $this->assertDatabaseCount('account_charges', 3);
        $this->get(route('additional-charges.show', $contract->student_id))->assertOk()->assertSee('330,00');
    }

    public function test_additional_charges_are_collected_through_existing_account_payment_and_credit_flow(): void
    {
        [$user, $contract] = $this->context();
        $service = $this->service($contract, '40.00');
        $this->actingAs($user)->post(route('additional-charges.store', $contract->student_id), $this->payload($contract, $service))->assertSessionHasNoErrors();
        CashRegister::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
        $payment = app(AccountPaymentService::class)->record($user, $contract, '290.00', ['payment_method_code' => 1]);
        $this->assertSame(2, $payment->allocations()->count());
        $this->assertDatabaseHas('account_charges', ['charge_type' => 'additional', 'paid_amount' => 40, 'status' => 'paid']);
        $this->get(route('rectorate.contracts.account.show', $contract))->assertOk()->assertSee('Adicional')->assertSee('Servicio de prueba');
        Role::findOrCreate('super_admin');
        $user->assignRole('super_admin');
        $this->deleteJson(route('super-admin.contracts.destroy', $contract))->assertUnprocessable()->assertJsonValidationErrors('contract');
    }

    public function test_free_extra_does_not_activate_enrollment_but_first_positive_payment_does(): void
    {
        [$user, $contract] = $this->context();
        $free = $this->service($contract, '0');
        $payload = [...$this->payload($contract, $free), 'due_date' => today()->subMonth()->toDateString()];
        $this->actingAs($user)->post(route('additional-charges.store', $contract->student_id), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('account_charges', ['charge_type' => 'additional', 'amount' => 0, 'status' => 'paid']);
        $this->assertSame('pre_enrolled', $contract->refresh()->status);
        CashRegister::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
        app(AccountPaymentService::class)->record($user, $contract, '1.00', ['payment_method_code' => 1]);
        $this->assertSame('enrolled', $contract->refresh()->status);
    }

    public function test_only_valid_services_and_contracts_in_the_same_company_are_accepted(): void
    {
        [$user, $contract] = $this->context();
        $service = $this->service($contract);
        $this->actingAs($user);
        $service->update(['company_id' => Company::factory()->create()->id]);
        $this->postJson(route('additional-charges.store', $contract->student_id), $this->payload($contract, $service))->assertUnprocessable()->assertJsonValidationErrors('additional_service_id');
        $service->update(['company_id' => $contract->company_id]);
        $contract->update(['status' => 'cancelled']);
        $this->postJson(route('additional-charges.store', $contract->student_id), $this->payload($contract, $service))->assertUnprocessable()->assertJsonValidationErrors('enrollment_contract_id');
        $this->assertSame(1, AccountCharge::count());
        $other = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $other->givePermissionTo(['additional-services.view', 'additional-services.manage', 'additional-charges.view', 'additional-charges.manage']);
        $this->actingAs($other)->get(route('additional-services.edit', $service))->assertNotFound();
        $this->get(route('additional-charges.show', $contract->student_id))->assertNotFound();
        $this->get(route('additional-charges.index'))->assertOk()->assertDontSee('Ana');
    }

    public function test_all_students_are_listed_even_without_enrollment_and_empty_accounts_are_rendered(): void
    {
        [$user, $contract] = $this->context();
        $student = Student::create(['company_id' => $user->company_id, 'identity_document' => 'EMPTY', 'first_name' => 'SinContrato', 'paternal_surname' => 'Prueba', 'birth_date' => '2010-01-01', 'gender' => 'Femenino', 'is_active' => false]);
        $this->actingAs($user)->get(route('additional-charges.index'))->assertOk()->assertSee('SinContrato');
        $this->get(route('additional-charges.show', $student))->assertOk()->assertSee('no tiene una cuenta vigente');
        $this->get(route('additional-charges.show', $contract->student_id))->assertOk()->assertSee('250,00');
    }

    public function test_validation_and_permissions_protect_configuration_and_charge_creation(): void
    {
        [$user, $contract] = $this->context();
        $this->actingAs($user)->postJson(route('additional-services.store'), ['title' => 'Servicio', 'detail' => 'Detalle', 'cost' => -1, 'is_active' => 1])->assertUnprocessable()->assertJsonValidationErrors('cost');
        $this->postJson(route('additional-charges.store', $contract->student_id), [])->assertUnprocessable()->assertJsonValidationErrors(['enrollment_contract_id', 'additional_service_id', 'due_date', 'submission_token']);
        $user->revokePermissionTo(['additional-services.manage', 'additional-charges.manage']);
        $this->get(route('additional-services.index'))->assertOk();
        $this->get(route('additional-services.create'))->assertForbidden();
        $this->postJson(route('additional-charges.store', $contract->student_id), [])->assertForbidden();
        $user->revokePermissionTo(['additional-services.view', 'additional-charges.view']);
        $this->get(route('additional-services.index'))->assertForbidden();
        $this->get(route('additional-charges.index'))->assertForbidden();
    }

    public function test_additional_charge_can_be_collected_partially(): void
    {
        [$user, $contract] = $this->context();
        $service = $this->service($contract);
        $this->actingAs($user)->post(route('additional-charges.store', $contract->student_id), $this->payload($contract, $service))->assertSessionHasNoErrors();
        CashRegister::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
        app(AccountPaymentService::class)->record($user, $contract, '260.00', ['payment_method_code' => 1]);
        $this->assertDatabaseHas('account_charges', ['charge_type' => 'additional', 'paid_amount' => 10, 'status' => 'partial']);
        $this->get(route('additional-charges.show', $contract->student_id))->assertOk()->assertSee('30,00');
    }

    private function context(): array
    {
        [$user, $contract] = $this->contract();
        foreach (['additional-services.view', 'additional-services.manage', 'additional-charges.view', 'additional-charges.manage', 'accounts.collect'] as $permission) {
            Permission::findOrCreate($permission);
            $user->givePermissionTo($permission);
        }

        return [$user, $contract];
    }

    private function service(EnrollmentContract $contract, string $cost = '40.00'): AdditionalService
    {
        return AdditionalService::create(['company_id' => $contract->company_id, 'title' => 'Servicio de prueba', 'detail' => 'Detalle del servicio', 'cost' => $cost, 'is_active' => true]);
    }

    private function payload(EnrollmentContract $contract, AdditionalService $service): array
    {
        return ['enrollment_contract_id' => $contract->id, 'additional_service_id' => $service->id, 'due_date' => today()->toDateString(), 'submission_token' => (string) Str::uuid()];
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
