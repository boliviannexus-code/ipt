<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE enrollment_contracts DROP CONSTRAINT enrollment_contracts_amount_check');
        DB::statement('ALTER TABLE enrollment_contracts ADD CONSTRAINT enrollment_contracts_amount_check CHECK (monthly_amount >= 0)');
        DB::statement('ALTER TABLE account_charges DROP CONSTRAINT account_charges_amounts_check');
        DB::statement('ALTER TABLE account_charges ADD CONSTRAINT account_charges_amounts_check CHECK (amount >= 0 AND paid_amount >= 0 AND paid_amount <= amount)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE account_charges DROP CONSTRAINT account_charges_amounts_check');
        DB::statement('ALTER TABLE account_charges ADD CONSTRAINT account_charges_amounts_check CHECK (amount > 0 AND paid_amount >= 0 AND paid_amount <= amount)');
        DB::statement('ALTER TABLE enrollment_contracts DROP CONSTRAINT enrollment_contracts_amount_check');
        DB::statement('ALTER TABLE enrollment_contracts ADD CONSTRAINT enrollment_contracts_amount_check CHECK (monthly_amount > 0)');
    }
};
