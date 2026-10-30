<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('additional_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('title', 150);
            $table->text('detail');
            $table->decimal('cost', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->string('automatic_trigger', 40)->nullable();
            $table->timestampsTz();
            $table->unique(['company_id', 'title']);
            $table->unique(['company_id', 'automatic_trigger']);
        });
        DB::statement('ALTER TABLE additional_services ADD CONSTRAINT additional_services_cost_check CHECK (cost >= 0)');
        Schema::table('account_charges', function (Blueprint $table): void {
            $table->dropUnique(['enrollment_contract_id', 'period']);
            $table->date('period')->nullable()->change();
            $table->string('charge_type', 20)->default('monthly');
            $table->foreignId('additional_service_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('detail')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->uuid('submission_token')->nullable()->unique();
            $table->foreignId('placement_exam_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
        DB::statement("ALTER TABLE account_charges ADD CONSTRAINT account_charges_type_check CHECK (charge_type IN ('monthly', 'additional'))");
        DB::statement("CREATE UNIQUE INDEX account_charges_monthly_period_unique ON account_charges (enrollment_contract_id, period) WHERE charge_type = 'monthly'");
        foreach (['additional-services.view', 'additional-services.manage', 'additional-charges.view', 'additional-charges.manage'] as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            foreach (['super_admin', 'admin', 'manager'] as $roleName) {
                Role::query()->where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($permission);
            }
            if (str_starts_with($name, 'additional-charges.') || $name === 'additional-services.view') {
                foreach (['cashier', 'branch_admin'] as $roleName) {
                    Role::query()->where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($permission);
                }
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve posted financial records: do not silently discard additional charges on rollback.
        if (DB::table('account_charges')->where('charge_type', 'additional')->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan cargos adicionales registrados.');
        }
        DB::statement('DROP INDEX account_charges_monthly_period_unique');
        DB::statement('ALTER TABLE account_charges DROP CONSTRAINT account_charges_type_check');
        Schema::table('account_charges', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('placement_exam_id');
            $table->dropConstrainedForeignId('additional_service_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['charge_type', 'detail', 'submission_token']);
            $table->date('period')->nullable(false)->change();
            $table->unique(['enrollment_contract_id', 'period']);
        });
        Schema::dropIfExists('additional_services');
        Permission::query()->whereIn('name', ['additional-services.view', 'additional-services.manage', 'additional-charges.view', 'additional-charges.manage'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
