<?php

namespace Tests\Feature\Audit;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditable_models_record_actor_company_and_changes(): void
    {
        config(['audit.console' => true]);

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        $target = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Usuario auditable',
        ]);
        $target->update(['name' => 'Usuario auditado']);
        $target->delete();

        $this->assertDatabaseHas('audits', [
            'company_id' => $company->id,
            'event' => 'created',
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
            'user_type' => User::class,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('audits', [
            'company_id' => $company->id,
            'event' => 'updated',
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
        ]);
        $this->assertDatabaseHas('audits', [
            'company_id' => $company->id,
            'event' => 'deleted',
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
        ]);

        $updateAudit = DB::table('audits')
            ->where('auditable_type', User::class)
            ->where('auditable_id', $target->id)
            ->where('event', 'updated')
            ->first();

        $this->assertStringContainsString('Usuario auditable', (string) $updateAudit->old_values);
        $this->assertStringContainsString('Usuario auditado', (string) $updateAudit->new_values);
    }

    public function test_user_password_is_not_written_to_audit_values(): void
    {
        config(['audit.console' => true]);

        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        $target = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($actor);

        $target->update(['password' => 'new-secure-password']);

        $audit = DB::table('audits')
            ->where('auditable_type', User::class)
            ->where('auditable_id', $target->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertStringNotContainsString('password', (string) $audit->old_values);
        $this->assertStringNotContainsString('password', (string) $audit->new_values);
    }
}
