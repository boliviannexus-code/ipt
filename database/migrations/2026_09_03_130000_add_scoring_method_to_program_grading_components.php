<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_grading_components', function (Blueprint $table): void {
            $table->string('scoring_method', 20)->default('percentage')->after('skill_mode');
        });

        DB::statement("ALTER TABLE program_grading_components ADD CONSTRAINT program_grading_components_scoring_method_check CHECK (scoring_method IN ('percentage', 'simple'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE program_grading_components DROP CONSTRAINT IF EXISTS program_grading_components_scoring_method_check');
        Schema::table('program_grading_components', function (Blueprint $table): void {
            $table->dropColumn('scoring_method');
        });
    }
};
