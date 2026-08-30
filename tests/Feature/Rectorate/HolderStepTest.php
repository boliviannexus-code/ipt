<?php

namespace Tests\Feature\Rectorate;

use App\Models\Area;
use App\Models\Campus;
use App\Models\CommercialOrigin;
use App\Models\Company;
use App\Models\Customer;
use App\Models\EnrollmentContract;
use App\Models\Personnel;
use App\Models\Plan;
use App\Models\Position;
use App\Models\Program;
use App\Models\RectorateApplication;
use App\Models\SinCatalogItem;
use App\Models\Student;
use App\Models\User;
use App\Support\SiatIdentityDocumentTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HolderStepTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_can_open_and_save_holder_step(): void
    {
        [$company, $user] = $this->context();

        $this->actingAs($user)
            ->get(route('rectorate.new'))
            ->assertOk()
            ->assertSee('Datos del titular')
            ->assertSee('Datos para facturación')
            ->assertSee('Razón social');

        $this->actingAs($user)
            ->post(route('rectorate.new.store'), $this->validData())
            ->assertRedirect()
            ->assertSessionHas('success');

        $customer = Customer::withoutGlobalScope('company')->sole();
        $application = RectorateApplication::withoutGlobalScope('company')->sole();

        $this->assertSame($company->id, $customer->company_id);
        $this->assertSame('8324984', $customer->document_number);
        $this->assertSame('Pacheco Servicios', $customer->name);
        $this->assertSame($customer->id, $application->customer_id);
        $this->assertSame('8324984', $application->identity_document);
        $this->assertSame('Álvaro', $application->first_name);
        $this->assertSame('Pacheco', $application->paternal_surname);
        $this->assertNull($application->account_number);
        $this->assertSame(2, $application->current_step);

        $this->actingAs($user)
            ->get(route('rectorate.applications.holder.edit', $application))
            ->assertOk()
            ->assertSee('value="Álvaro"', false)
            ->assertSee('value="8324984"', false)
            ->assertSee('Pacheco Servicios');

        $this->actingAs($user)
            ->put(route('rectorate.applications.holder.update', $application), [
                ...$this->validData(),
                'email' => 'titular.actualizado@example.com',
            ])
            ->assertRedirect(route('rectorate.applications.plan.edit', $application));

        $this->assertSame(1, RectorateApplication::withoutGlobalScope('company')->count());
        $this->assertDatabaseHas('rectorate_applications', [
            'id' => $application->id,
            'email' => 'titular.actualizado@example.com',
            'current_step' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('rectorate.index'))
            ->assertOk()
            ->assertSee('Álvaro Pacheco Rojas')
            ->assertSee('Paso 2 de 4')
            ->assertSee('Continuar programa');

        $plan = Plan::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'name' => 'Plan Inicial',
            'monthly_cost' => 250,
        ]);
        $program = Program::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'title' => 'Inglés intensivo',
            'enrollment_code' => 'CAP',
            'duration_months' => 12,
        ]);
        $program->plans()->attach($plan);
        $origin = CommercialOrigin::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'name' => 'Redes sociales',
        ]);
        $area = Area::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Marketing', 'is_active' => true]);
        $position = Position::withoutGlobalScope('company')->create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Asesor comercial', 'is_sales_executive' => true, 'is_active' => true]);
        $executive = Personnel::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'position_id' => $position->id,
            'first_name' => 'María',
            'paternal_surname' => 'Vargas',
            'identity_document' => '7788991',
            'email' => 'maria@example.com',
            'phone' => '70000001',
            'is_sales_enabled' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('rectorate.applications.plan.edit', $application))
            ->assertOk()
            ->assertSee('Origen comercial')
            ->assertSee('Redes sociales')
            ->assertSee('Ejecutivo de ventas')
            ->assertSee('María Vargas');

        $this->actingAs($user)
            ->put(route('rectorate.applications.plan.update', $application), [
                'program_id' => $program->id,
                'plan_id' => $plan->id,
                'commercial_origin_id' => $origin->id,
                'sales_executive_id' => $executive->id,
            ])
            ->assertRedirect(route('rectorate.applications.student.edit', $application));

        $this->assertDatabaseHas('rectorate_applications', [
            'id' => $application->id,
            'commercial_origin_id' => $origin->id,
            'sales_executive_id' => $executive->id,
        ]);

        $this->actingAs($user)
            ->get(route('rectorate.applications.student.edit', $application))
            ->assertOk()
            ->assertSee('Datos del estudiante')
            ->assertSee('Parentesco con el titular');

        $this->actingAs($user)
            ->put(route('rectorate.applications.student.update', $application), [
                'student_identity_document' => '9900112',
                'student_first_name' => 'lucía',
                'student_paternal_surname' => 'pacheco',
                'student_maternal_surname' => 'flores',
                'student_birth_date' => '2015-06-10',
                'student_email' => 'estudiante@example.com',
                'student_phone' => '71234567',
                'student_relationship' => 'Titular',
                'student_gender' => 'Femenino',
                'primary_contact_type' => 'Otro',
                'reference_first_name' => 'Carla',
                'reference_last_name' => 'Mendoza',
                'reference_relationship' => 'Tía',
                'reference_phone' => '70112233',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('rectorate.applications.confirmation.show', $application));

        $this->assertDatabaseHas('rectorate_applications', [
            'id' => $application->id,
            'program_id' => $program->id,
            'plan_id' => $plan->id,
            'student_identity_document' => '8324984',
            'student_first_name' => 'Álvaro',
            'student_relationship' => 'Titular',
            'student_gender' => 'Femenino',
            'primary_contact_type' => 'Otro',
            'reference_first_name' => 'Carla',
            'reference_last_name' => 'Mendoza',
            'reference_relationship' => 'Tía',
            'reference_phone' => '70112233',
            'current_step' => 4,
        ]);

        $this->actingAs($user)
            ->get(route('rectorate.index'))
            ->assertOk()
            ->assertSee('Paso 4 de 4')
            ->assertSee('Confirmación');

        $this->actingAs($user)
            ->get(route('rectorate.applications.confirmation.show', $application))
            ->assertOk()
            ->assertSee('Resumen')
            ->assertSee('Inglés intensivo')
            ->assertSee('Plan Inicial')
            ->assertSee('Redes sociales')
            ->assertSee('María Vargas')
            ->assertSee('Contacto de referencia principal')
            ->assertSee('Carla Mendoza')
            ->assertSee('70112233')
            ->assertSee('Confirmar inscripción');

        $this->actingAs($user)
            ->post(route('rectorate.applications.confirmation.store', $application))
            ->assertRedirect(route('rectorate.index'))
            ->assertSessionHas('success');

        $student = Student::withoutGlobalScope('company')->sole();
        $this->assertSame($company->id, $student->company_id);
        $this->assertSame('8324984', $student->identity_document);
        $this->assertSame('Álvaro', $student->first_name);
        $this->assertSame('CAP10001', $student->account_number);
        $this->assertSame($application->campus_id, $student->campus_id);
        $this->assertDatabaseHas('rectorate_applications', [
            'id' => $application->id,
            'student_id' => $student->id,
            'status' => 'completed',
        ]);
        $contract = EnrollmentContract::withoutGlobalScope('company')->sole();
        $this->assertSame(10001, $contract->contract_number);
        $this->assertSame('CAP10001', $contract->account_number);
        $this->assertSame($application->campus_id, $contract->campus_id);
        $this->assertSame('pre_enrolled', $contract->status);
        $this->assertSame('250.00', $contract->monthly_amount);
        $this->assertDatabaseHas('account_charges', [
            'enrollment_contract_id' => $contract->id,
            'amount' => 250,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('rectorate.applications.confirmation.store', $application))
            ->assertRedirect(route('rectorate.index'));
        $this->assertSame(1, Student::withoutGlobalScope('company')->count());
        $this->assertSame(1, EnrollmentContract::withoutGlobalScope('company')->count());

        $this->actingAs($user)
            ->get(route('rectorate.index'))
            ->assertOk()
            ->assertSee('Completada')
            ->assertSee('Ver resumen')
            ->assertSee('Contrato');

        $this->actingAs($user)
            ->get(route('rectorate.contracts.print', $contract))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="contrato-CAP10001.pdf"');

        $this->actingAs($user)
            ->delete(route('rectorate.applications.destroy', $application))
            ->assertStatus(409);
        $this->assertNotSoftDeleted('rectorate_applications', ['id' => $application->id]);
        $this->assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
    }

    public function test_billing_document_types_are_fixed_and_do_not_require_the_siat_catalog(): void
    {
        [, $user] = $this->context();
        SinCatalogItem::withoutGlobalScope('company')->delete();

        $this->actingAs($user)
            ->get(route('rectorate.new'))
            ->assertOk()
            ->assertSee('<option value="1"', false)
            ->assertSee('>CI</option>', false)
            ->assertSee('<option value="5"', false)
            ->assertSee('>NIT</option>', false)
            ->assertDontSee('Primero sincroniza los tipos de documento');

        $this->actingAs($user)
            ->post(route('rectorate.new.store'), $this->validData())
            ->assertSessionHasNoErrors();
    }

    public function test_holder_step_rejects_billing_document_types_other_than_ci_and_nit(): void
    {
        [, $user] = $this->context();

        $this->actingAs($user)
            ->post(route('rectorate.new.store'), [
                ...$this->validData(),
                'identity_document_type_code' => '2',
            ])
            ->assertSessionHasErrors('identity_document_type_code');
    }

    public function test_existing_billing_customer_is_reused_inside_the_company(): void
    {
        [$company, $user] = $this->context();
        $customer = Customer::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'identity_document_type_code' => 1,
            'document_number' => '8324984',
            'document_complement' => null,
            'customer_code' => 'CLI-001',
            'name' => 'Nombre anterior',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('rectorate.new.store'), $this->validData())->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('rectorate.new.store'), [
            ...$this->validData(),
            'phone' => '70000001',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Customer::withoutGlobalScope('company')->count());
        $this->assertSame(2, RectorateApplication::withoutGlobalScope('company')->count());
        $this->assertTrue(RectorateApplication::withoutGlobalScope('company')->pluck('account_number')->every(fn ($number) => $number === null));
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Pacheco Servicios']);
        $this->assertDatabaseHas('rectorate_applications', ['customer_id' => $customer->id, 'company_id' => $company->id]);

        $this->actingAs($user)
            ->getJson(route('rectorate.new.lookup', ['identity_document' => '8324984']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('holder.phone', '70000001')
            ->assertJsonPath('billing.document_number', '8324984');

        $draft = RectorateApplication::withoutGlobalScope('company')->firstOrFail();
        $this->actingAs($user)
            ->delete(route('rectorate.applications.destroy', $draft))
            ->assertRedirect(route('rectorate.index'));
        $this->assertSoftDeleted('rectorate_applications', ['id' => $draft->id]);
    }

    private function context(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $campus = Campus::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'name' => 'Sede Central',
            'code' => '1',
            'address' => 'Av. Principal 123',
        ]);
        $area = Area::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Rectoría', 'is_active' => true]);
        $position = Position::withoutGlobalScope('company')->create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Secretaria', 'is_active' => true]);
        $personnel = Personnel::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'position_id' => $position->id,
            'campus_id' => $campus->id,
            'first_name' => 'Usuario',
            'paternal_surname' => 'Rectoría',
            'identity_document' => '1122334',
            'phone' => '70000000',
            'email' => 'rectoria@example.com',
            'is_active' => true,
        ]);
        $user->update(['personnel_id' => $personnel->id]);
        Permission::findOrCreate('rectorate.create');
        Permission::findOrCreate('rectorate.delete');
        $user->givePermissionTo(['rectorate.create', 'rectorate.delete']);
        SinCatalogItem::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'catalog_key' => SiatIdentityDocumentTypes::CATALOG,
            'item_key' => '1',
            'classifier_code' => '1',
            'description' => 'Cédula de identidad',
            'is_active' => true,
            'raw_data' => ['codigoClasificador' => 1, 'descripcion' => 'Cédula de identidad'],
            'synced_at' => now(),
        ]);

        return [$company, $user];
    }

    private function validData(): array
    {
        return [
            'identity_document' => '8324984',
            'first_name' => 'álvaro',
            'paternal_surname' => 'pacheco',
            'maternal_surname' => 'rojas',
            'birth_date' => '1990-05-12',
            'email' => 'titular@example.com',
            'phone' => '76543210',
            'identity_document_type_code' => '1',
            'document_number' => '8324984',
            'document_complement' => '',
            'legal_name' => 'Pacheco Servicios',
        ];
    }
}
