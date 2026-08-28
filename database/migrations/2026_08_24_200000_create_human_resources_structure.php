<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::create('positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'area_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('personnel', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->string('first_name');
            $table->string('paternal_surname');
            $table->string('maternal_surname')->nullable();
            $table->string('identity_document', 30);
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'identity_document']);
            $table->index(['company_id', 'position_id', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('personnel_id')->nullable()->after('company_id')->constrained('personnel')->nullOnDelete();
            $table->string('username')->nullable()->after('name');
            $table->unique('personnel_id');
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropUnique(['personnel_id']);
            $table->dropConstrainedForeignId('personnel_id');
            $table->dropColumn('username');
        });
        Schema::dropIfExists('personnel');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('areas');
    }
};
