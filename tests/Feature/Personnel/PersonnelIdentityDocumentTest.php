<?php

namespace Tests\Feature\Personnel;

use App\Models\Area;
use App\Models\Campus;
use App\Models\Company;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PersonnelIdentityDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_personnel_can_be_registered_without_birth_date(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('personnel.view');
        Permission::findOrCreate('personnel.manage');
        $user->givePermissionTo(['personnel.view', 'personnel.manage']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Administración', 'is_active' => true]);
        $position = Position::create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Auxiliar', 'is_active' => true]);
        $campus = Campus::create(['company_id' => $company->id, 'name' => 'Sede Central', 'code' => 'CEN', 'address' => 'Av. Principal 123']);

        $this->actingAs($user)->post(route('personnel.store'), [
            'position_id' => $position->id,
            'campus_id' => $campus->id,
            'first_name' => 'Ana',
            'paternal_surname' => 'Flores',
            'identity_document' => '7456123',
            'phone' => '71234567',
            'email' => 'ana.flores@example.com',
            'is_active' => '1',
        ])->assertRedirect(route('personnel.index'));

        $this->assertDatabaseHas('personnel', [
            'identity_document' => '7456123',
            'birth_date' => null,
            'campus_id' => $campus->id,
        ]);

        $this->actingAs($user)->get(route('personnel.index'))->assertOk()->assertSee('Sede Central')->assertSee('CEN');
    }

    public function test_existing_identity_document_is_found_and_cannot_be_registered_twice_in_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('personnel.view');
        Permission::findOrCreate('personnel.manage');
        $user->givePermissionTo(['personnel.view', 'personnel.manage']);

        $area = Area::create(['company_id' => $company->id, 'name' => 'Tecnología', 'is_active' => true]);
        $position = Position::create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Director', 'is_active' => true]);
        $personnel = Personnel::create([
            'company_id' => $company->id,
            'position_id' => $position->id,
            'first_name' => 'Álvaro',
            'paternal_surname' => 'Pacheco',
            'identity_document' => '8324984',
            'birth_date' => '1990-01-01',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('personnel.lookup', ['identity_document' => '8324984']))
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('personnel.id', $personnel->id)
            ->assertJsonPath('personnel.first_name', 'Álvaro');

        $this->actingAs($user)
            ->post(route('personnel.store'), [
                'position_id' => $position->id,
                'first_name' => 'Otro',
                'paternal_surname' => 'Nombre',
                'identity_document' => '8324984',
                'birth_date' => '1991-01-01',
                'phone' => '76543210',
                'email' => 'otro@example.com',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors([
                'identity_document' => 'Ya existe personal registrado con este CI en la empresa activa.',
            ]);

        $this->assertSame(1, Personnel::query()->count());
    }
}
