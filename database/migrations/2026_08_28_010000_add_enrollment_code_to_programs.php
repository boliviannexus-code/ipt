<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table): void {
            $table->string('enrollment_code', 3)->nullable()->after('title');
            $table->unique(['company_id', 'enrollment_code']);
        });

        Schema::create('program_campus_enrollment_sequences', function (Blueprint $table): void {
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('last_number')->default(0);
            $table->timestampsTz();
            $table->primary(['program_id', 'campus_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_campus_enrollment_sequences');
        Schema::table('programs', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'enrollment_code']);
            $table->dropColumn('enrollment_code');
        });
    }
};
