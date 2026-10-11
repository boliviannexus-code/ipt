<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_module_student_assignments', function (Blueprint $table): void {
            $table->timestamp('released_at')->nullable()->after('assigned_at');
            $table->index(['student_id', 'released_at']);
        });

        DB::statement(<<<'SQL'
            UPDATE academic_module_student_assignments AS assignments
            SET released_at = academic_modules.closed_at
            FROM academic_modules
            WHERE academic_modules.id = assignments.academic_module_id
              AND academic_modules.closed_at IS NOT NULL
              AND assignments.released_at IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('academic_module_student_assignments', function (Blueprint $table): void {
            $table->dropIndex(['student_id', 'released_at']);
            $table->dropColumn('released_at');
        });
    }
};
