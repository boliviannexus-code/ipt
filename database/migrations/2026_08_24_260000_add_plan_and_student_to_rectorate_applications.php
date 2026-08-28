<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->foreignId('plan_id')->nullable()->after('customer_id')->constrained()->restrictOnDelete();
            $table->string('student_identity_document', 30)->nullable();
            $table->string('student_first_name', 100)->nullable();
            $table->string('student_paternal_surname', 100)->nullable();
            $table->string('student_maternal_surname', 100)->nullable();
            $table->date('student_birth_date')->nullable();
            $table->string('student_email')->nullable();
            $table->string('student_phone', 30)->nullable();
            $table->string('student_relationship', 40)->nullable();
            $table->string('student_gender', 20)->nullable();
            $table->index(['company_id', 'plan_id']);
        });
    }

    public function down(): void
    {
        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'plan_id']);
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn([
                'student_identity_document',
                'student_first_name',
                'student_paternal_surname',
                'student_maternal_surname',
                'student_birth_date',
                'student_email',
                'student_phone',
                'student_relationship',
                'student_gender',
            ]);
        });
    }
};
