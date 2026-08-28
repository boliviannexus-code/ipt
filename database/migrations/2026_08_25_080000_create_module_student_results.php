<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_charges', function (Blueprint $table): void {
            $table->string('concept')->nullable()->after('plan_id');
        });
        DB::table('account_charges')->whereNull('concept')->update(['concept' => 'Mensualidad']);

        Schema::create('academic_module_student_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('status', 20);
            $table->foreignId('account_charge_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('finalized_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('finalized_at');
            $table->timestamps();
            $table->unique(['academic_module_id', 'student_id']);
            $table->index(['company_id', 'student_id']);
        });

        DB::statement("ALTER TABLE academic_module_student_results ADD CONSTRAINT academic_module_student_results_status_check CHECK (status IN ('approved', 'failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_module_student_results');
        Schema::table('account_charges', function (Blueprint $table): void {
            $table->dropColumn('concept');
        });
    }
};
