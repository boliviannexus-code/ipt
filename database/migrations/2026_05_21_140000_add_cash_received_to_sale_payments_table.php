<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_payments', function (Blueprint $table): void {
            $table->decimal('received_amount', 12, 2)->nullable()->after('amount');
            $table->decimal('change_amount', 12, 2)->nullable()->after('received_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table): void {
            $table->dropColumn(['received_amount', 'change_amount']);
        });
    }
};
