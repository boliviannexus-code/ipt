<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_modules', function (Blueprint $table): void {
            $table->timestamp('closed_at')->nullable()->after('end_date');
            $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            $table->index(['company_id', 'closed_at']);
        });

        DB::statement(<<<'SQL'
            UPDATE academic_modules
            SET closed_at = closures.closed_at,
                closed_by = closures.closed_by
            FROM (
                SELECT academic_module_id, MAX(finalized_at) AS closed_at, MAX(finalized_by) AS closed_by
                FROM academic_module_student_results
                GROUP BY academic_module_id
            ) AS closures
            WHERE closures.academic_module_id = academic_modules.id
        SQL);
    }

    public function down(): void
    {
        Schema::table('academic_modules', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'closed_at']);
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn('closed_at');
        });
    }
};
