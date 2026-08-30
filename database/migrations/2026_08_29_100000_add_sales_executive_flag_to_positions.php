<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->boolean('is_sales_executive')->default(false)->after('is_academic');
            $table->index(['company_id', 'is_sales_executive', 'is_active']);
        });

        DB::table('positions')
            ->whereRaw("LOWER(TRIM(name)) = 'ejecutivo de ventas'")
            ->update(['is_sales_executive' => true]);
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'is_sales_executive', 'is_active']);
            $table->dropColumn('is_sales_executive');
        });
    }
};
