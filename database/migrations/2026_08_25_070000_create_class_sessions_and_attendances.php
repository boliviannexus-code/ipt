<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete();
            $table->foreignId('started_by')->constrained('users')->restrictOnDelete();
            $table->date('class_date');
            $table->timestamp('started_at');
            $table->timestamps();
            $table->unique(['academic_module_id', 'class_date']);
            $table->index(['company_id', 'personnel_id', 'class_date']);
        });

        Schema::create('student_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('status', 20);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->unique(['class_session_id', 'student_id']);
        });

        DB::statement("ALTER TABLE student_attendances ADD CONSTRAINT student_attendances_status_check CHECK (status IN ('present', 'absent', 'late', 'excused'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
        Schema::dropIfExists('class_sessions');
    }
};
