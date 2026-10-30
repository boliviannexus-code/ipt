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
        DB::statement("ALTER TABLE enrollment_contracts ADD CONSTRAINT enrollment_contracts_status_check CHECK (status IN ('pre_enrolled', 'enrolled', 'inactive', 'suspended', 'abandoned', 'withdrawn', 'finished', 'cancelled'))");
        Schema::table('enrollment_contracts', fn (Blueprint $table) => $table->timestampTz('completed_at')->nullable());
    }

    public function down(): void
    {
        if (DB::table('enrollment_contracts')->where('status', 'finished')->exists()) {
            throw new RuntimeException('Existen matrículas FINALIZADAS; no se puede revertir su estado.');
        }
        Schema::table('enrollment_contracts', fn (Blueprint $table) => $table->dropColumn('completed_at'));
        DB::statement('ALTER TABLE enrollment_contracts DROP CONSTRAINT enrollment_contracts_status_check');
        DB::statement("ALTER TABLE enrollment_contracts ADD CONSTRAINT enrollment_contracts_status_check CHECK (status IN ('pre_enrolled', 'enrolled', 'inactive', 'suspended', 'abandoned', 'withdrawn', 'cancelled'))");
    }
};
