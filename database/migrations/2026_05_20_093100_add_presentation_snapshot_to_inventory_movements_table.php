<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->foreignId('product_presentation_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_presentations')
                ->nullOnDelete();
            $table->string('presentation_name')->nullable()->after('product_presentation_id');
            $table->integer('package_quantity')->nullable()->after('quantity');
            $table->unsignedInteger('units_per_package')->default(1)->after('package_quantity');

            $table->index(['product_id', 'warehouse_id', 'product_presentation_id'], 'inventory_product_warehouse_presentation_index');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->dropIndex('inventory_product_warehouse_presentation_index');
            $table->dropConstrainedForeignId('product_presentation_id');
            $table->dropColumn(['presentation_name', 'package_quantity', 'units_per_package']);
        });
    }
};
