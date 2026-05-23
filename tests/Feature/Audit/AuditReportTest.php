<?php

namespace Tests\Feature\Audit;

use App\Models\Company;
use App\Models\Product;
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
        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Producto auditado']);
        $product->update(['name' => 'Producto visible']);

        $this->actingAs($otherUser);
        $foreignProduct = Product::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Producto ajeno']);
        $foreignProduct->update(['name' => 'Producto oculto']);

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
                'auditable_type' => Product::class,
            ]))
            ->assertOk()
            ->assertJsonFragment(['auditable_label' => 'Productos'])
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
        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Antes']);
        $product->update(['name' => 'Despues']);
        $ownAudit = Audit::query()
            ->where('auditable_type', Product::class)
            ->where('auditable_id', $product->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->actingAs($otherUser);
        $foreignProduct = Product::factory()->create(['company_id' => $otherCompany->id, 'name' => 'Otro antes']);
        $foreignProduct->update(['name' => 'Otro despues']);
        $foreignAudit = Audit::query()
            ->where('auditable_type', Product::class)
            ->where('auditable_id', $foreignProduct->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->get(route('audits.show', $ownAudit))
            ->assertOk()
            ->assertSee('Productos')
            ->assertSee('Antes')
            ->assertSee('Despues');

        $this
            ->actingAs($user)
            ->get(route('audits.show', $foreignAudit))
            ->assertForbidden();
    }
}
