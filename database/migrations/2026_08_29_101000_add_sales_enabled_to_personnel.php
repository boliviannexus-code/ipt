<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel', function (Blueprint $table): void {
            $table->boolean('is_sales_enabled')->default(false)->after('is_active');
            $table->index(['company_id', 'is_sales_enabled', 'is_active']);
        });

        DB::table('personnel')
            ->whereIn('position_id', DB::table('positions')->where('is_sales_executive', true)->select('id'))
            ->update(['is_sales_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('personnel', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'is_sales_enabled', 'is_active']);
            $table->dropColumn('is_sales_enabled');
        });
    }
};
