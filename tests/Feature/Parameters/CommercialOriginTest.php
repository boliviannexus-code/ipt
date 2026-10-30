<?php

namespace Tests\Feature\Parameters;

use App\Models\CommercialOrigin;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CommercialOriginTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_can_manage_commercial_origins_in_ajax_modals(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            Permission::findOrCreate("commercial-origins.{$action}");
        }
        $user->givePermissionTo(Permission::query()->where('name', 'like', 'commercial-origins.%')->get());

        $this->actingAs($user)
            ->get(route('parameters.commercial-origins.create'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('data-ajax-form', false);

        $this->actingAs($user)
            ->postJson(route('parameters.commercial-origins.store'), ['name' => 'Facebook'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $origin = CommercialOrigin::query()->firstOrFail();
        $this->assertSame($company->id, $origin->company_id);

        $this->actingAs($user)
            ->putJson(route('parameters.commercial-origins.update', $origin), ['name' => 'Redes sociales'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)->get(route('parameters.commercial-origins.index'))
            ->assertOk()
            ->assertSee('data-datatable', false)
            ->assertSee(route('datatables.commercial-origins'), false)
            ->assertSee('Origen comercial');

        $this->actingAs($user)->getJson(route('datatables.commercial-origins', [
            'draw' => 1, 'start' => 0, 'length' => 10,
        ]))->assertOk()->assertJsonFragment(['name' => 'Redes sociales']);

        $this->actingAs($user)
            ->deleteJson(route('parameters.commercial-origins.destroy', $origin))
            ->assertOk();

        $this->assertDatabaseMissing('commercial_origins', ['id' => $origin->id]);
    }

    public function test_name_is_unique_per_company_and_records_are_isolated(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('commercial-origins.view');
        Permission::findOrCreate('commercial-origins.create');
        $user->givePermissionTo(['commercial-origins.view', 'commercial-origins.create']);

        CommercialOrigin::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Referido']);
        CommercialOrigin::withoutGlobalScope('company')->create(['company_id' => $otherCompany->id, 'name' => 'Sitio ajeno']);

        $this->actingAs($user)->post(route('parameters.commercial-origins.store'), ['name' => 'Referido'])->assertSessionHasErrors('name');
        $this->actingAs($user)->getJson(route('datatables.commercial-origins', [
            'draw' => 1, 'start' => 0, 'length' => 10,
        ]))->assertOk()->assertDontSee('Sitio ajeno', false);
    }
}
