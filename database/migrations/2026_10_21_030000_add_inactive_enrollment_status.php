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
        DB::statement("ALTER TABLE enrollment_contracts ADD CONSTRAINT enrollment_contracts_status_check CHECK (status IN ('pre_enrolled', 'enrolled', 'inactive', 'cancelled'))");
        Schema::table('enrollment_contracts', function (Blueprint $table): void {
            $table->timestampTz('attendance_inactive_at')->nullable();
            $table->foreignId('attendance_inactive_module_id')->nullable()->constrained('academic_modules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('enrollment_contracts')->where('status', 'inactive')->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan matrículas INACTIVAS.');
        }
        Schema::table('enrollment_contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('attendance_inactive_module_id');
            $table->dropColumn('attendance_inactive_at');
        });
        DB::statement('ALTER TABLE enrollment_contracts DROP CONSTRAINT enrollment_contracts_status_check');
        DB::statement("ALTER TABLE enrollment_contracts ADD CONSTRAINT enrollment_contracts_status_check CHECK (status IN ('pre_enrolled', 'enrolled', 'cancelled'))");
    }
};
