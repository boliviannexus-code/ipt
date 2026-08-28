<?php

namespace Tests\Feature\Companies;

use App\Models\Campus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CampusTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_can_manage_campuses_in_ajax_modals(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('campuses.view');
        Permission::findOrCreate('campuses.manage');
        $user->givePermissionTo(['campuses.view', 'campuses.manage']);

        $this->actingAs($user)->get(route('campuses.create'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('data-ajax-form', false);

        $this->actingAs($user)->postJson(route('campuses.store'), [
            'name' => 'Sede Central',
            'code' => '1',
            'address' => 'Av. Principal 123',
        ])->assertOk()->assertJsonPath('success', true);

        $campus = Campus::query()->sole();
        $this->assertSame('1', $campus->code);
        $this->assertSame($company->id, $campus->company_id);

        $this->actingAs($user)->putJson(route('campuses.update', $campus), [
            'name' => 'Sede Centro',
            'code' => '2',
            'address' => 'Calle Central 456',
        ])->assertOk()->assertJsonPath('success', true);

        $this->actingAs($user)->get(route('campuses.index'))
            ->assertOk()->assertSee('Sede Centro')->assertSee('2')->assertSee('Calle Central 456');

        $this->actingAs($user)->deleteJson(route('campuses.destroy', $campus))->assertOk();
        $this->assertDatabaseMissing('campuses', ['id' => $campus->id]);
    }

    public function test_campuses_are_isolated_by_company_and_code_is_unique_per_company(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('campuses.view');
        Permission::findOrCreate('campuses.manage');
        $user->givePermissionTo(['campuses.view', 'campuses.manage']);

        Campus::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Central', 'code' => '1', 'address' => 'Dirección propia']);
        Campus::withoutGlobalScope('company')->create(['company_id' => $otherCompany->id, 'name' => 'Sede ajena', 'code' => '1', 'address' => 'Dirección ajena']);

        $this->actingAs($user)->post(route('campuses.store'), ['name' => 'Otra', 'code' => '1', 'address' => 'Nueva dirección'])->assertSessionHasErrors('code');
        $this->actingAs($user)->get(route('campuses.index'))->assertOk()->assertDontSee('Sede ajena');
    }

    public function test_code_must_be_exactly_one_numeric_digit_on_create_and_update(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('campuses.manage');
        $user->givePermissionTo('campuses.manage');

        foreach (['A', '10', ''] as $invalidCode) {
            $this->actingAs($user)->post(route('campuses.store'), [
                'name' => 'Sede '.$invalidCode,
                'code' => $invalidCode,
                'address' => 'Av. Principal',
            ])->assertSessionHasErrors('code');
        }

        $campus = Campus::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Central', 'code' => '1', 'address' => 'Dirección']);
        $this->actingAs($user)->put(route('campuses.update', $campus), [
            'name' => 'Central',
            'code' => '22',
            'address' => 'Dirección',
        ])->assertSessionHasErrors('code');
    }
}
