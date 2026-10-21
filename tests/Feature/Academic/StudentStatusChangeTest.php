<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentAssignment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\EnrollmentContract;
use App\Models\EnrollmentStatusChange;
use App\Models\Plan;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\RectorateApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StudentStatusChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_requires_observation_and_suspension_date_and_records_audited_status(): void
    {
        [$user, $student] = $this->context();
        $contract = $student->contracts()->sole();
        $this->actingAs($user)->get(route('students.status.edit', $student), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('data-ajax-form', false)->assertSee('SUSPENDIDO')->assertSee('ABANDONO')->assertSee('BAJA')->assertSee('Fecha de registro');
        $payload = $this->payload($contract, 'suspended');
        $this->postJson(route('students.status.update', $student), [...$payload, 'reactivation_date' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('reactivation_date');
        $this->postJson(route('students.status.update', $student), [...$payload, 'observation' => '   '])
            ->assertUnprocessable()->assertJsonValidationErrors('observation');
        $this->postJson(route('students.status.update', $student), $payload)->assertOk()->assertJsonPath('success', true);
        $this->assertSame('suspended', $contract->refresh()->status);
        $this->assertSame($payload['reactivation_date'], $contract->reactivation_date->toDateString());
        $record = EnrollmentStatusChange::sole();
        $this->assertSame('enrolled', $record->previous_status);
        $this->assertSame($user->id, $record->recorded_by);
        $this->assertSame('Motivo del cambio', $record->observation);
        $this->get(route('students.index'))->assertOk()->assertSee('Lucía')->assertSee('SUSPENDIDO');
        $this->get(route('students.status.edit', $student), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('Motivo del cambio')->assertSee('Reactivación prevista');
    }

    public function test_withdrawal_is_terminal_and_double_submission_is_idempotent(): void
    {
        [$user, $student] = $this->context();
        $contract = $student->contracts()->sole();
        $payload = $this->payload($contract, 'withdrawn');
        $this->actingAs($user)->postJson(route('students.status.update', $student), $payload)->assertOk();
        $this->postJson(route('students.status.update', $student), $payload)->assertOk();
        $this->assertDatabaseCount('enrollment_status_changes', 1);
        $this->assertSame('withdrawn', $contract->refresh()->status);
        $this->postJson(route('students.status.update', $student), $this->payload($contract, 'suspended'))
            ->assertUnprocessable()->assertJsonValidationErrors('enrollment_contract_id');
        $this->get(route('students.index'))->assertOk()->assertSee('Lucía')->assertSee('BAJA')->assertDontSee('>Asignar módulo<', false);
    }

    public function test_abandonment_clears_expected_reactivation_date_without_reactivating_or_charging(): void
    {
        [$user, $student] = $this->context();
        $contract = $student->contracts()->sole();
        $this->actingAs($user)->postJson(route('students.status.update', $student), $this->payload($contract, 'suspended'))->assertOk();
        $this->postJson(route('students.status.update', $student), $this->payload($contract, 'abandoned'))->assertOk();
        $this->assertSame('abandoned', $contract->refresh()->status);
        $this->assertNull($contract->reactivation_date);
        $this->assertDatabaseCount('enrollment_status_changes', 2);
        $this->assertDatabaseCount('account_charges', 0);
        $this->get(route('students.index'))->assertOk()->assertSee('ABANDONO');
    }

    public function test_registered_and_reactivation_dates_are_validated_and_prior_history_cannot_be_backdated(): void
    {
        [$user, $student] = $this->context();
        $contract = $student->contracts()->sole();
        $payload = $this->payload($contract, 'suspended');
        $this->actingAs($user)->postJson(route('students.status.update', $student), [...$payload, 'registered_on' => today()->addDay()->toDateString()])
            ->assertUnprocessable()->assertJsonValidationErrors('registered_on');
        $this->postJson(route('students.status.update', $student), [...$payload, 'reactivation_date' => today()->toDateString()])
            ->assertUnprocessable()->assertJsonValidationErrors('reactivation_date');
        $this->postJson(route('students.status.update', $student), $payload)->assertOk();
        $this->postJson(route('students.status.update', $student), [
            ...$this->payload($contract, 'abandoned'), 'registered_on' => today()->subDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('registered_on');
        $this->postJson(route('students.status.update', $student), $this->payload($contract, 'suspended'))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_changes_require_management_permission_and_are_company_scoped(): void
    {
        [$user, $student] = $this->context();
        $contract = $student->contracts()->sole();
        $user->revokePermissionTo('students.manage');
        $this->actingAs($user)->get(route('students.status.edit', $student))->assertForbidden();
        $this->postJson(route('students.status.update', $student), $this->payload($contract, 'withdrawn'))->assertForbidden();
        $other = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $other->givePermissionTo('students.manage');
        $this->actingAs($other)->get(route('students.status.edit', $student))->assertNotFound();
        $this->postJson(route('students.status.update', $student), $this->payload($contract, 'withdrawn'))->assertNotFound();
        $this->assertSame('enrolled', $contract->refresh()->status);
    }

    public function test_status_change_releases_only_selected_program_assignments_and_preserves_history(): void
    {
        [$user, $student, $program, $plan] = $this->context();
        $contract = $student->contracts()->sole();
        $level = ProgramLevel::create(['company_id' => $program->company_id, 'program_id' => $program->id, 'name' => 'Inicial', 'position' => 1]);
        $module = $this->module($program, $level, 'Módulo actual');
        $assignment = AcademicModuleStudentAssignment::create(['company_id' => $program->company_id, 'academic_module_id' => $module->id, 'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);
        $otherProgram = Program::create(['company_id' => $program->company_id, 'title' => 'Francés', 'duration_months' => 12]);
        $otherLevel = ProgramLevel::create(['company_id' => $program->company_id, 'program_id' => $otherProgram->id, 'name' => 'Inicial francés', 'position' => 1]);
        $otherModule = $this->module($otherProgram, $otherLevel, 'Otro programa');
        $otherAssignment = AcademicModuleStudentAssignment::create(['company_id' => $program->company_id, 'academic_module_id' => $otherModule->id, 'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);
        $this->actingAs($user)->postJson(route('students.status.update', $student), $this->payload($contract, 'suspended'))->assertOk();
        $this->assertNotNull($assignment->refresh()->released_at);
        $this->assertNull($otherAssignment->refresh()->released_at);
        $this->assertDatabaseHas('academic_module_student_assignments', ['id' => $assignment->id]);
        $this->assertTrue($student->refresh()->is_active);
        $this->assertDatabaseCount('academic_modules', 2);
    }

    private function payload(EnrollmentContract $contract, string $status): array
    {
        return ['enrollment_contract_id' => $contract->id, 'status' => $status, 'observation' => 'Motivo del cambio',
            'registered_on' => today()->toDateString(), 'reactivation_date' => today()->addMonth()->toDateString(),
            'submission_token' => (string) Str::uuid()];
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
