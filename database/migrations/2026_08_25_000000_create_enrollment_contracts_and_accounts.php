<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_contract_sequences', function (Blueprint $table): void {
            $table->foreignId('program_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestampsTz();
        });

        Schema::create('enrollment_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('rectorate_application_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('contract_number');
            $table->decimal('monthly_amount', 12, 2);
            $table->string('status', 30)->default('pre_enrolled');
            $table->timestampTz('confirmed_at');
            $table->timestampTz('enrolled_at')->nullable();
            $table->timestampsTz();
            $table->unique(['program_id', 'contract_number']);
            $table->index(['company_id', 'student_id', 'status']);
        });

        Schema::create('contract_plan_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->decimal('monthly_amount', 12, 2);
            $table->date('effective_from');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['enrollment_contract_id', 'effective_from']);
        });

        Schema::create('account_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->date('period');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestampsTz();
            $table->unique(['enrollment_contract_id', 'period']);
            $table->index(['company_id', 'status', 'due_date']);
        });

        Schema::create('account_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30)->default('cash');
            $table->string('reference')->nullable();
            $table->timestampTz('paid_at');
            $table->timestampsTz();
            $table->index(['company_id', 'paid_at']);
        });

        Schema::create('payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_charge_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['account_payment_id', 'account_charge_id']);
        });

        DB::statement('ALTER TABLE enrollment_contracts ADD CONSTRAINT enrollment_contracts_amount_check CHECK (monthly_amount > 0)');
        DB::statement("ALTER TABLE enrollment_contracts ADD CONSTRAINT enrollment_contracts_status_check CHECK (status IN ('pre_enrolled', 'enrolled', 'cancelled'))");
        DB::statement('ALTER TABLE account_charges ADD CONSTRAINT account_charges_amounts_check CHECK (amount > 0 AND paid_amount >= 0 AND paid_amount <= amount)');
        DB::statement("ALTER TABLE account_charges ADD CONSTRAINT account_charges_status_check CHECK (status IN ('pending', 'partial', 'paid', 'cancelled'))");
        DB::statement('ALTER TABLE account_payments ADD CONSTRAINT account_payments_amount_check CHECK (amount > 0)');
        DB::statement('ALTER TABLE payment_allocations ADD CONSTRAINT payment_allocations_amount_check CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('account_payments');
        Schema::dropIfExists('account_charges');
        Schema::dropIfExists('contract_plan_histories');
        Schema::dropIfExists('enrollment_contracts');
        Schema::dropIfExists('program_contract_sequences');
    }
};
