<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_enrollment_sequences', function (Blueprint $table): void {
            $table->foreignId('campus_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestampsTz();
        });

        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->foreignId('campus_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
            $table->string('account_number', 60)->nullable()->after('campus_id');
            $table->unique(['company_id', 'account_number']);
            $table->index(['campus_id', 'created_at']);
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->foreignId('campus_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->string('account_number', 60)->nullable()->after('campus_id');
            $table->unique(['company_id', 'account_number']);
            $table->index(['campus_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropIndex(['campus_id', 'is_active']);
            $table->dropUnique(['company_id', 'account_number']);
            $table->dropConstrainedForeignId('campus_id');
            $table->dropColumn('account_number');
        });

        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->dropIndex(['campus_id', 'created_at']);
            $table->dropUnique(['company_id', 'account_number']);
            $table->dropConstrainedForeignId('campus_id');
            $table->dropColumn('account_number');
        });

        Schema::dropIfExists('campus_enrollment_sequences');
    }
};
