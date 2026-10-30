<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('previous_program_level_id')->nullable()->constrained('program_levels')->restrictOnDelete();
            $table->foreignId('target_program_level_id')->constrained('program_levels')->restrictOnDelete();
            $table->string('previous_level_name', 150)->nullable();
            $table->string('target_level_name', 150);
            $table->decimal('score', 5, 2);
            $table->foreignId('teacher_id')->constrained('personnel')->restrictOnDelete();
            $table->text('observation');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('examined_at');
            $table->uuid('submission_token')->unique();
            $table->timestampsTz();
            $table->index(['company_id', 'student_id']);
        });
        DB::statement('ALTER TABLE placement_exams ADD CONSTRAINT placement_exams_score_check CHECK (score >= 0 AND score <= 100)');
        Schema::create('placement_exam_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_level_id')->constrained()->restrictOnDelete();
            $table->string('level_name', 150);
            $table->unsignedInteger('position');
            $table->unique(['student_id', 'program_id', 'program_level_id'], 'placement_recognized_level_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_exam_levels');
        Schema::dropIfExists('placement_exams');
    }
};
