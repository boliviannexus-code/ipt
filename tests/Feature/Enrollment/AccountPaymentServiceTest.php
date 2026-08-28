<?php

namespace Tests\Feature\Enrollment;

use App\Models\AccountCharge;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Customer;
use App\Models\EnrollmentContract;
use App\Models\Plan;
use App\Models\Program;
use App\Models\RectorateApplication;
use App\Models\SinCatalogItem;
use App\Models\Student;
use App\Models\User;
use App\Services\Enrollment\AccountPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_payment_keeps_pre_enrollment_and_full_initial_payment_enrolls_student(): void
    {
        [$user, $contract] = $this->contract();
        CashRegister::factory()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
        Permission::findOrCreate('accounts.collect');
        $user->givePermissionTo('accounts.collect');

        $this->actingAs($user)
            ->get(route('rectorate.collectible-accounts.index'))
            ->assertOk()
            ->assertSee('Cuentas disponibles para cobro')
            ->assertSee('Ana Pérez')
            ->assertSee('Cobrar');
        $this->actingAs($user)
            ->get(route('rectorate.contracts.account.show', $contract))
            ->assertOk()
            ->assertSee('Cobro de mensualidad')
            ->assertSee('Estudiante')
            ->assertSee('Titular')
            ->assertSee('María Pérez')
            ->assertSee('Facturación')
            ->assertSee('NIT ')
            ->assertSee('Plan')
            ->assertSee('Monto a pagar')
            ->assertSee('Método de pago')
            ->assertSee('1 · EFECTIVO')
            ->assertSee('Historial de pagos');

        $service = app(AccountPaymentService::class);

        $firstPayment = $service->record($user, $contract, '100.00', ['payment_method_code' => 1]);
        $this->assertSame('100.00', $firstPayment->amount);
        $this->assertSame('pre_enrolled', $contract->refresh()->status);
        $this->assertDatabaseHas('account_charges', ['enrollment_contract_id' => $contract->id, 'paid_amount' => 100, 'status' => 'partial']);

        $service->record($user, $contract, '150.00', ['payment_method_code' => 1]);
        $this->assertSame('enrolled', $contract->refresh()->status);
        $this->assertNotNull($contract->enrolled_at);
        $this->assertDatabaseHas('account_charges', ['enrollment_contract_id' => $contract->id, 'paid_amount' => 250, 'status' => 'paid']);
        $this->assertDatabaseCount('account_payments', 2);
        $this->assertDatabaseCount('payment_allocations', 2);

        Permission::findOrCreate('cash-registers.view');
        $user->givePermissionTo('cash-registers.view');
        $this->actingAs($user)
            ->get(route('cash-registers.index'))
            ->assertOk()
            ->assertSee('Total cobrado')
            ->assertSee('Movimientos de la caja activa')
            ->assertSee('PAGO-000001')
            ->assertSee('Ana Pérez')
            ->assertSee('Bs 250.00');

        $this->actingAs($user)
            ->get(route('rectorate.contracts.account.show', $contract))
            ->assertOk()
            ->assertSee('Cobro de mensualidad')
            ->assertSee('Inscrito')
            ->assertSee('Historial de pagos');
    }

    public function test_payment_requires_an_open_cash_register(): void
    {
        [$user, $contract] = $this->contract();

        try {
            app(AccountPaymentService::class)->record($user, $contract, '50.00', ['payment_method_code' => 1]);
            $this->fail('Se esperaba una validación por caja cerrada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cash_register', $exception->errors());
        }

        $this->assertDatabaseCount('account_payments', 0);
    }

    private function contract(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        SinCatalogItem::withoutGlobalScope('company')->create([
            'company_id' => $company->id, 'catalog_key' => 'tipos_metodo_pago', 'item_key' => '1',
            'classifier_code' => '1', 'description' => 'EFECTIVO', 'is_active' => true,
            'raw_data' => [], 'synced_at' => now(),
        ]);
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
