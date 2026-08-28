<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->unsignedSmallInteger('duration_months');
            $table->timestamps();
            $table->unique(['company_id', 'title']);
        });

        Schema::create('plan_program', function (Blueprint $table): void {
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['program_id', 'plan_id']);
        });

        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->foreignId('program_id')->nullable()->after('customer_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('program_id');
        });
        Schema::dropIfExists('plan_program');
        Schema::dropIfExists('programs');
    }
};
