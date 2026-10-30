<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_module_student_results', function (Blueprint $table): void {
            $table->foreignId('program_grading_scheme_id')->nullable()->after('student_id')->constrained()->restrictOnDelete();
            $table->decimal('final_score', 5, 2)->nullable()->after('status');
            $table->decimal('passing_score', 5, 2)->nullable()->after('final_score');
            $table->json('grading_details')->nullable()->after('passing_score');
        });
    }

    public function down(): void
    {
        Schema::table('academic_module_student_results', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('program_grading_scheme_id');
            $table->dropColumn(['final_score', 'passing_score', 'grading_details']);
        });
    }
};
