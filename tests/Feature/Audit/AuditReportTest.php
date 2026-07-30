<?php

namespace Tests\Feature\Audit;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuditReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_audit_report_and_see_company_scoped_rows(): void
    {
        config(['audit.console' => true]);
        Permission::findOrCreate('audits.view');

        $company = Company::factory()->create(['name' => 'Empresa visible']);
        $otherCompany = Company::factory()->create(['name' => 'Empresa oculta']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $user->givePermissionTo('audits.view');
        $otherUser->givePermissionTo('audits.view');

        $this->actingAs($user);
        $target = User::factory()->create(['company_id' => $company->id, 'name' => 'Usuario auditado']);
        $target->update(['name' => 'Usuario visible']);

        $this->actingAs($otherUser);
        $foreignTarget = User::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Usuario ajeno']);
        $foreignTarget->update(['name' => 'Usuario oculto']);

        $this
            ->actingAs($user)
            ->get(route('audits.index'))
            ->assertOk()
            ->assertSee('Auditoria de acciones')
            ->assertSee('audit-filters');

        $this
            ->actingAs($user)
            ->getJson(route('datatables.audits', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'auditable_type' => User::class,
            ]))
            ->assertOk()
            ->assertJsonFragment(['auditable_label' => 'Usuarios'])
            ->assertSee('Empresa visible', false)
            ->assertDontSee('Empresa oculta', false);
    }

    public function test_user_can_open_audit_detail_only_for_own_company(): void
    {
        config(['audit.console' => true]);
        Permission::findOrCreate('audits.view');

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $user->givePermissionTo('audits.view');
        $otherUser->givePermissionTo('audits.view');

        $this->actingAs($user);
        $target = User::factory()->create(['company_id' => $company->id, 'name' => 'Antes']);
        $target->update(['name' => 'Despues']);
        $ownAudit = Audit::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $target->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->actingAs($otherUser);
        $foreignTarget = User::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Otro antes']);
        $foreignTarget->update(['name' => 'Otro despues']);
        $foreignAudit = Audit::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $foreignTarget->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->get(route('audits.show', $ownAudit))
            ->assertOk()
            ->assertSee('Usuarios')
            ->assertSee('Antes')
            ->assertSee('Despues');

        $this
            ->actingAs($user)
            ->get(route('audits.show', $foreignAudit))
            ->assertForbidden();
    }
}
