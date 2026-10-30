<?php

namespace Tests\Feature\Academic;

use App\Models\Company;
use App\Models\Customer;
use App\Models\RectorateApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicPersonalDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_protects_the_menu_and_all_routes(): void
    {
        [$user, $application] = $this->context();
        $user->revokePermissionTo('academic-personal-data.edit');
        $this->actingAs($user)->get(route('academic.personal-data.index'))->assertForbidden();
        $this->get(route('academic.personal-data.edit', $application))->assertForbidden();
        $this->put(route('academic.personal-data.update', $application), $this->payload())->assertForbidden();
        $this->get(route('dashboard'))->assertDontSee('Datos de titular y estudiante');
    }

    public function test_authorized_user_can_search_and_update_personal_data_without_enrollment_permission(): void
    {
        [$user, $application] = $this->context();
        $otherApplication = $application->replicate();
        $otherApplication->save();
        $this->actingAs($user)->get(route('academic.personal-data.index', ['q' => 'Lucía']))
            ->assertOk()->assertSee('Datos de titular y estudiante')->assertSee('Titular');
        $this->get(route('academic.personal-data.edit', $application))->assertOk()->assertSee('Guardar cambios');
        $this->put(route('academic.personal-data.update', $application), [...$this->payload(), 'company_id' => 999, 'status' => 'draft'])
            ->assertSessionHasNoErrors()->assertRedirect(route('academic.personal-data.edit', $application));
        $this->assertSame('Luciana', $application->student->fresh()->first_name);
        $this->assertSame('Luciana', $otherApplication->fresh()->student_first_name);
        $this->assertSame('María', $application->fresh()->first_name);
        $this->assertSame('Titular', $otherApplication->fresh()->first_name);
        $this->assertSame('completed', $application->fresh()->status);
        $this->assertSame($user->company_id, $application->student->fresh()->company_id);
    }

    public function test_other_company_cannot_view_search_or_update_the_record(): void
    {
        [, $application] = $this->context();
        $otherUser = User::factory()->create(['company_id' => Company::factory()->create()->id]);
        $otherUser->givePermissionTo('academic-personal-data.edit');
        $this->actingAs($otherUser)->get(route('academic.personal-data.index'))->assertOk()->assertDontSee('Lucía');
        $this->get(route('academic.personal-data.edit', $application))->assertNotFound();
        $this->put(route('academic.personal-data.update', $application), $this->payload())->assertNotFound();
    }

    public function test_invalid_data_and_duplicate_student_document_do_not_change_records(): void
    {
        [$user, $application] = $this->context();
        $duplicate = $application->student->replicate();
        $duplicate->identity_document = '998877';
        $duplicate->save();
        $this->actingAs($user)->put(route('academic.personal-data.update', $application), [
            ...$this->payload(), 'student_identity_document' => '998877', 'email' => 'invalid',
            'student_birth_date' => today()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['student_identity_document', 'email', 'student_birth_date']);
        $this->assertSame('Lucía', $application->student->fresh()->first_name);
        $this->assertSame('Titular', $application->fresh()->first_name);
    }

    public function test_access_can_be_granted_and_revoked_through_a_role(): void
    {
        [$user] = $this->context();
        $user->revokePermissionTo('academic-personal-data.edit');
        $role = Role::findOrCreate('editor-datos', 'web');
        $role->givePermissionTo('academic-personal-data.edit');
        $user->assignRole($role);
        $this->actingAs($user)->get(route('academic.personal-data.index'))->assertOk();
        $this->assertSame('Datos de titular y estudiante', permission_module_label('academic-personal-data'));
        $role->revokePermissionTo('academic-personal-data.edit');
        $user->unsetRelation('roles');
        $this->get(route('academic.personal-data.index'))->assertForbidden();
    }

    public function test_other_contact_requires_reference_details(): void
    {
        [$user, $application] = $this->context();
        $this->actingAs($user)->put(route('academic.personal-data.update', $application), [
            ...$this->payload(), 'primary_contact_type' => 'Otro',
        ])->assertSessionHasErrors(['reference_first_name', 'reference_last_name', 'reference_relationship', 'reference_phone']);
    }

    private function context(): array
    {
        $company = Company::factory()->create();
        Permission::findOrCreate('academic-personal-data.edit', 'web');
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->givePermissionTo('academic-personal-data.edit');
        $student = Student::withoutGlobalScope('company')->create([
            'company_id' => $company->id, 'identity_document' => '123456', 'first_name' => 'Lucía',
            'paternal_surname' => 'Pérez', 'birth_date' => '2010-01-01', 'gender' => 'Femenino', 'is_active' => true,
        ]);
        $application = RectorateApplication::withoutGlobalScope('company')->create([
            'company_id' => $company->id, 'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id,
            'student_id' => $student->id, 'identity_document' => '654321', 'first_name' => 'Titular',
            'paternal_surname' => 'Pérez', 'birth_date' => '1980-01-01', 'email' => 'titular@example.com',
            'phone' => '70000000', 'status' => 'completed', 'student_relationship' => 'Hijo/a', 'primary_contact_type' => 'Titular',
        ]);

        return [$user, $application];
    }

    private function payload(): array
    {
        return [
            'identity_document' => '654321', 'first_name' => 'María', 'paternal_surname' => 'Pérez',
            'maternal_surname' => null, 'birth_date' => '1980-01-01', 'email' => 'maria@example.com', 'phone' => '71111111',
            'student_identity_document' => '123456', 'student_first_name' => 'Luciana', 'student_paternal_surname' => 'Pérez',
            'student_maternal_surname' => null, 'student_birth_date' => '2010-01-01', 'student_email' => null,
            'student_phone' => null, 'student_gender' => 'Femenino', 'student_relationship' => 'Hijo/a', 'primary_contact_type' => 'Titular',
        ];
    }
}
