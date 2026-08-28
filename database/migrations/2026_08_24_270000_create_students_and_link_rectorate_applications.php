<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('identity_document', 30);
            $table->string('first_name', 100);
            $table->string('paternal_surname', 100);
            $table->string('maternal_surname', 100)->nullable();
            $table->date('birth_date');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('gender', 20);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['company_id', 'identity_document']);
        });

        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->foreignId('student_id')->nullable()->after('plan_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('student_id');
        });
        Schema::dropIfExists('students');
    }
};
