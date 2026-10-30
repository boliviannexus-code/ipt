<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table): void {
            $table->foreignId('program_grading_scheme_id')->nullable()->after('academic_module_id')->constrained()->restrictOnDelete();
        });

        Schema::create('class_session_grading_skills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_grading_skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('selected_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('selected_at');
            $table->timestamps();
            $table->unique(['class_session_id', 'program_grading_skill_id'], 'class_session_grading_skill_unique');
        });

        Schema::create('student_daily_grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_session_grading_skill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2);
            $table->foreignId('graded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('graded_at');
            $table->timestamps();
            $table->unique(['class_session_grading_skill_id', 'student_id'], 'student_daily_grade_unique');
            $table->index(['company_id', 'student_id']);
        });

        DB::statement('ALTER TABLE student_daily_grades ADD CONSTRAINT student_daily_grades_score_check CHECK (score >= 0 AND score <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('student_daily_grades');
        Schema::dropIfExists('class_session_grading_skills');
        Schema::table('class_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('program_grading_scheme_id');
        });
    }
};
