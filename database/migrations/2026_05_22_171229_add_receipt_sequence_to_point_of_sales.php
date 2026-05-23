<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_of_sales', function (Blueprint $table): void {
            $table->string('receipt_prefix', 40)->nullable()->after('code');
            $table->unsignedInteger('receipt_next_number')->default(1)->after('sequence_number');
            $table->unsignedTinyInteger('receipt_digits')->default(6)->after('receipt_next_number');
        });

        DB::table('point_of_sales')
            ->orderBy('id')
            ->each(function (object $pointOfSale): void {
                $nextNumber = ((int) DB::table('sales')
                    ->where('point_of_sale_id', $pointOfSale->id)
                    ->max('sequence_number')) + 1;

                DB::table('point_of_sales')
                    ->where('id', $pointOfSale->id)
                    ->update([
                        'receipt_prefix' => $pointOfSale->code,
                        'receipt_next_number' => max(1, $nextNumber),
                        'receipt_digits' => 6,
                    ]);
            });

        Schema::table('sales', function (Blueprint $table): void {
            $table->unique(['point_of_sale_id', 'sequence_number'], 'sales_point_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique('sales_point_sequence_unique');
        });

        Schema::table('point_of_sales', function (Blueprint $table): void {
            $table->dropColumn(['receipt_prefix', 'receipt_next_number', 'receipt_digits']);
        });
    }
};
