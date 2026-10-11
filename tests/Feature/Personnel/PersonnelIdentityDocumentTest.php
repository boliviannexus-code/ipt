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
        $campus = Campus::create(['company_id' => $company->id, 'name' => 'Sede Central', 'code' => '1', 'address' => 'Av. Principal 123']);

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

        $this->actingAs($user)->get(route('personnel.index'))->assertOk()->assertSee('data-datatable')->assertSee(route('datatables.personnel'));
        $this->actingAs($user)->getJson(route('datatables.personnel'))->assertOk()
            ->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.full_name', 'Ana Flores');
        $this->assertStringContainsString('Sede Central', $this->getJson(route('datatables.personnel'))->json('data.0.campus_label'));

        $personnel = Personnel::query()->where('identity_document', '7456123')->firstOrFail();
        $this->actingAs($user)
            ->patch(route('personnel.sales-enabled', $personnel))
            ->assertRedirect(route('personnel.index'));

        $this->assertTrue($personnel->refresh()->is_sales_enabled);
        $this->assertStringContainsString('Habilitado para ventas', $this->actingAs($user)->getJson(route('datatables.personnel'))->json('data.0.sales'));
    }

    public function test_personnel_datatable_search_pagination_company_scope_and_permissions(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('personnel.view');
        $user->givePermissionTo('personnel.view');
        $area = Area::create(['company_id' => $company->id, 'name' => 'Administración']);
        $position = Position::create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Auxiliar']);
        foreach (['Ana', 'Zoe'] as $name) {
            Personnel::create(['company_id' => $company->id, 'position_id' => $position->id,
                'first_name' => $name, 'paternal_surname' => 'Flores', 'identity_document' => $name]);
        }
        $foreignCompany = Company::factory()->create();
        Personnel::withoutGlobalScope('company')->create(['company_id' => $foreignCompany->id,
            'position_id' => $position->id, 'first_name' => 'Ajena', 'paternal_surname' => 'Flores', 'identity_document' => 'otra']);
        $parameters = ['draw' => 1, 'start' => 0, 'length' => 1,
            'columns' => [['data' => 'full_name', 'name' => 'full_name', 'searchable' => 'true', 'orderable' => 'true']],
            'order' => [['column' => 0, 'dir' => 'desc']]];
        $response = $this->actingAs($user)->getJson(route('datatables.personnel', $parameters));
        $response->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Zoe Flores');
        $this->assertStringNotContainsString('Editar', $response->json('data.0.actions'));
        $this->assertStringNotContainsString('<form', $response->json('data.0.sales'));
        $parameters['search'] = ['value' => 'Ana Flores', 'regex' => 'false'];
        $this->getJson(route('datatables.personnel', $parameters))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.full_name', 'Ana Flores');
        $user->revokePermissionTo('personnel.view');
        $this->getJson(route('datatables.personnel'))->assertForbidden();
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
