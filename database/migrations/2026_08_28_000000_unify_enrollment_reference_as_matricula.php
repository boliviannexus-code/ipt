<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('enrollment_contracts')
            ->whereNotNull('account_number')
            ->update(['contract_number' => DB::raw('CAST(account_number AS BIGINT)')]);
    }

    public function down(): void
    {
        // La numeración anterior no puede reconstruirse de forma confiable.
    }
};
