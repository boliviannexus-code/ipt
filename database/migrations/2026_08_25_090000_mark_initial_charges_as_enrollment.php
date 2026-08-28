<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE account_charges
            SET concept = 'Inscripción'
            WHERE id IN (
                SELECT DISTINCT ON (enrollment_contract_id) id
                FROM account_charges
                ORDER BY enrollment_contract_id, period, id
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            UPDATE account_charges
            SET concept = 'Mensualidad'
            WHERE concept = 'Inscripción'
        SQL);
    }
};
