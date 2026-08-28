<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_level_id')->constrained()->restrictOnDelete();
            $table->string('name', 160);
            $table->string('modality', 20);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('duration_value');
            $table->string('duration_unit', 15);
            $table->timestamps();
            $table->unique(['program_id', 'program_level_id', 'name']);
            $table->index(['company_id', 'program_id', 'program_level_id']);
        });

        DB::statement("ALTER TABLE academic_modules ADD CONSTRAINT academic_modules_modality_check CHECK (modality IN ('virtual', 'presential'))");
        DB::statement("ALTER TABLE academic_modules ADD CONSTRAINT academic_modules_duration_unit_check CHECK (duration_unit IN ('hours', 'days', 'weeks', 'months'))");
        DB::statement('ALTER TABLE academic_modules ADD CONSTRAINT academic_modules_duration_check CHECK (duration_value > 0)');
        DB::statement('ALTER TABLE academic_modules ADD CONSTRAINT academic_modules_schedule_check CHECK (ends_at > starts_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_modules');
    }
};
