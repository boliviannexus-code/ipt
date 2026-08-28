<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_payments', function (Blueprint $table): void {
            $table->unsignedSmallInteger('payment_method_code')->nullable()->after('amount');
        });

        DB::table('account_payments')->update(['payment_method_code' => 1]);
        DB::statement('ALTER TABLE account_payments ALTER COLUMN payment_method_code SET NOT NULL');

        Schema::table('account_payments', function (Blueprint $table): void {
            $table->dropColumn('payment_method');
            $table->index(['company_id', 'payment_method_code']);
        });
    }

    public function down(): void
    {
        Schema::table('account_payments', function (Blueprint $table): void {
            $table->string('payment_method', 30)->default('cash')->after('amount');
            $table->dropIndex(['company_id', 'payment_method_code']);
            $table->dropColumn('payment_method_code');
        });
    }
};
