<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('position');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['program_id', 'name']);
            $table->unique(['program_id', 'position']);
            $table->index(['company_id', 'program_id', 'is_active']);
        });

        DB::statement('ALTER TABLE program_levels ADD CONSTRAINT program_levels_position_check CHECK (position > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('program_levels');
    }
};
