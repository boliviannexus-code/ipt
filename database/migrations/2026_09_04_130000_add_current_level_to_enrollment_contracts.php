<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_contracts', function (Blueprint $table): void {
            $table->foreignId('current_program_level_id')->nullable()->after('program_id')->constrained('program_levels')->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
            UPDATE enrollment_contracts
            SET current_program_level_id = (
                SELECT program_levels.id
                FROM program_levels
                WHERE program_levels.program_id = enrollment_contracts.program_id
                  AND program_levels.position = 1
                ORDER BY program_levels.id
                LIMIT 1
            )
            WHERE current_program_level_id IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('enrollment_contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_program_level_id');
        });
    }
};
