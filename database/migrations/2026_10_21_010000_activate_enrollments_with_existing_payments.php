<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE enrollment_contracts AS contracts
            SET status = 'enrolled', enrolled_at = payments.first_paid_at, updated_at = CURRENT_TIMESTAMP
            FROM (
                SELECT enrollment_contract_id, MIN(paid_at) AS first_paid_at
                FROM account_payments
                WHERE amount > 0
                GROUP BY enrollment_contract_id
            ) AS payments
            WHERE contracts.id = payments.enrollment_contract_id
                AND contracts.status = 'pre_enrolled'
        SQL);
    }

    public function down(): void
    {
        // Do not deactivate paid enrollments when reverting this data migration.
    }
};
