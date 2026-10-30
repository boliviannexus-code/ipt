<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_payments', function (Blueprint $table): void {
            $table->decimal('credit_amount', 12, 2)->default(0);
        });
        DB::statement('UPDATE account_payments SET credit_amount = GREATEST(0, amount - COALESCE((SELECT SUM(amount) FROM payment_allocations WHERE account_payment_id = account_payments.id), 0))');
        DB::statement('ALTER TABLE account_payments ADD CONSTRAINT account_payments_credit_check CHECK (credit_amount >= 0 AND credit_amount <= amount)');
        Schema::create('account_credit_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('applied_by')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestampTz('created_at')->useCurrent();
        });
        DB::statement('ALTER TABLE account_credit_applications ADD CONSTRAINT account_credit_applications_amount_check CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('account_credit_applications');
        DB::statement('ALTER TABLE account_payments DROP CONSTRAINT account_payments_credit_check');
        Schema::table('account_payments', fn (Blueprint $table) => $table->dropColumn('credit_amount'));
    }
};
