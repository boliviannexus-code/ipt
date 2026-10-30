<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE enrollment_contracts DROP CONSTRAINT enrollment_contracts_status_check');
        DB::statement("ALTER TABLE enrollment_contracts ADD CONSTRAINT enrollment_contracts_status_check CHECK (status IN ('pre_enrolled', 'enrolled', 'inactive', 'suspended', 'abandoned', 'withdrawn', 'cancelled'))");
        Schema::table('enrollment_contracts', function (Blueprint $table): void {
            $table->date('status_registered_on')->nullable();
            $table->date('reactivation_date')->nullable();
        });
        Schema::create('enrollment_status_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_contract_id')->constrained()->cascadeOnDelete();
            $table->string('previous_status', 30);
            $table->string('status', 30);
            $table->text('observation');
            $table->date('registered_on');
            $table->date('reactivation_date')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->uuid('submission_token')->unique();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        if (DB::table('enrollment_status_changes')->exists()
            || DB::table('enrollment_contracts')->whereIn('status', ['suspended', 'abandoned', 'withdrawn'])->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan cambios de estado registrados.');
        }
        Schema::dropIfExists('enrollment_status_changes');
        Schema::table('enrollment_contracts', function (Blueprint $table): void {
            $table->dropColumn(['status_registered_on', 'reactivation_date']);
        });
        DB::statement('ALTER TABLE enrollment_contracts DROP CONSTRAINT enrollment_contracts_status_check');
        DB::statement("ALTER TABLE enrollment_contracts ADD CONSTRAINT enrollment_contracts_status_check CHECK (status IN ('pre_enrolled', 'enrolled', 'inactive', 'cancelled'))");
    }
};
