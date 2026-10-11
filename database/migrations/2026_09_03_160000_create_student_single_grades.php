<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_single_grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_grading_skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2);
            $table->foreignId('graded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('graded_at');
            $table->timestamps();
            $table->unique(['academic_module_id', 'program_grading_skill_id', 'student_id'], 'student_single_grade_unique');
            $table->index(['company_id', 'student_id']);
        });

        DB::statement('ALTER TABLE student_single_grades ADD CONSTRAINT student_single_grades_score_check CHECK (score >= 0 AND score <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('student_single_grades');
    }
};
