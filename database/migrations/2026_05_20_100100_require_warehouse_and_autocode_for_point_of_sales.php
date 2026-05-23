<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('point_of_sales')->whereNull('warehouse_id')->delete();

        Schema::table('point_of_sales', function (Blueprint $table): void {
            if (! Schema::hasColumn('point_of_sales', 'sequence_number')) {
                $table->unsignedInteger('sequence_number')->default(1)->after('code');
            }
        });

        DB::table('point_of_sales')
            ->join('warehouses', 'warehouses.id', '=', 'point_of_sales.warehouse_id')
            ->select('point_of_sales.id', 'point_of_sales.warehouse_id', 'warehouses.branch_id')
            ->orderBy('point_of_sales.id')
            ->get()
            ->each(function ($pointOfSale): void {
                DB::table('point_of_sales')
                    ->where('id', $pointOfSale->id)
                    ->update([
                        'sequence_number' => 1,
                        'code' => $pointOfSale->branch_id.'-'.$pointOfSale->warehouse_id.'-000001',
                    ]);
            });

        Schema::table('point_of_sales', function (Blueprint $table): void {
            $table->dropForeign(['warehouse_id']);
            $table->foreignId('warehouse_id')->nullable(false)->change();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();

            if (! collect(Schema::getIndexes('point_of_sales'))->contains('name', 'point_of_sales_warehouse_unique')) {
                $table->unique('warehouse_id', 'point_of_sales_warehouse_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('point_of_sales', function (Blueprint $table): void {
            if (collect(Schema::getIndexes('point_of_sales'))->contains('name', 'point_of_sales_warehouse_unique')) {
                $table->dropUnique('point_of_sales_warehouse_unique');
            }
            $table->dropForeign(['warehouse_id']);
            $table->foreignId('warehouse_id')->nullable()->change();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();

            if (Schema::hasColumn('point_of_sales', 'sequence_number')) {
                $table->dropColumn('sequence_number');
            }
        });
    }
};
