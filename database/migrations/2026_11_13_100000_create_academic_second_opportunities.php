<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_second_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_module_student_result_id')->unique('second_opportunity_result_unique')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_contract_id')->constrained()->restrictOnDelete();
            $table->decimal('original_score', 5, 2);
            $table->string('original_status', 20);
            $table->decimal('score', 5, 2);
            $table->string('status', 20);
            $table->decimal('passing_score', 5, 2);
            $table->text('reason');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['company_id', 'recorded_at']);
        });
        $permission = Permission::findOrCreate('second-opportunities.manage', 'web');
        foreach (Role::where('guard_name', 'web')->whereIn('name', ['admin', 'super_admin'])->get() as $role) {
            $role->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_second_opportunities');
        Permission::where('name', 'second-opportunities.manage')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
