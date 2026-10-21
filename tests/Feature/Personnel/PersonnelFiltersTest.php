<?php

namespace Tests\Feature\Personnel;

use App\Models\Area;
use App\Models\Company;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PersonnelFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_combine_by_area_and_position_and_exports_preserve_company_scope(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('personnel.view');
        Permission::findOrCreate('personnel.manage');
        $user->givePermissionTo(['personnel.view', 'personnel.manage']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Académico']);
        $other = Area::create(['company_id' => $company->id, 'name' => 'Administración']);
        $junior = Position::create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Junior']);
        $senior = Position::create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Senior']);
        $admin = Position::create(['company_id' => $company->id, 'area_id' => $other->id, 'name' => 'Administrador']);
        foreach ([[$junior, 'Ana'], [$senior, 'María'], [$admin, 'Pedro']] as [$position, $name]) {
            Personnel::create(['company_id' => $company->id, 'position_id' => $position->id, 'first_name' => $name,
                'paternal_surname' => 'Prueba', 'identity_document' => $name, 'is_active' => true]);
        }
        $foreign = Company::factory()->create();
        $foreignArea = Area::create(['company_id' => $foreign->id, 'name' => 'Área ajena']);
        $foreignPosition = Position::create(['company_id' => $foreign->id, 'area_id' => $foreignArea->id, 'name' => 'Cargo ajeno']);
        Personnel::create(['company_id' => $foreign->id, 'position_id' => $foreignPosition->id, 'first_name' => 'Ajeno', 'paternal_surname' => 'Prueba', 'identity_document' => 'EXT']);
        $this->actingAs($user)->get(route('personnel.index'))->assertOk()->assertSee('personnel-filters')->assertSee('Académico')->assertDontSee('Cargo ajeno');
        $this->getJson(route('datatables.personnel', ['area_id' => $area->id]))->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson(route('datatables.personnel', ['position_id' => $junior->id]))->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.full_name', 'Ana Prueba');
        $this->getJson(route('datatables.personnel', ['area_id' => $other->id, 'position_id' => $junior->id]))->assertOk()->assertJsonPath('recordsFiltered', 0);
        $this->getJson(route('datatables.personnel', ['area_id' => $foreignArea->id]))->assertOk()->assertJsonPath('recordsFiltered', 0);
        $csv = $this->get(route('personnel.export', ['format' => 'csv', 'area_id' => $area->id, 'position_id' => $junior->id]))->assertOk()->streamedContent();
        $this->assertStringContainsString('Ana Prueba', $csv);
        $this->assertStringNotContainsString('María Prueba', $csv);
        $this->assertStringNotContainsString('Ajeno', $csv);
        $this->getJson(route('datatables.personnel', ['area_id' => 'incorrecto']))->assertUnprocessable()->assertJsonValidationErrors('area_id');
    }
}
