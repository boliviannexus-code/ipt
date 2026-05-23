<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignId('point_of_sale_id')
                ->nullable()
                ->after('cash_register_id')
                ->constrained('point_of_sales')
                ->nullOnDelete();
            $table->unsignedInteger('sequence_number')->nullable()->after('receipt_number');
            $table->text('notes')->nullable()->after('status');
            $table->index(['point_of_sale_id', 'sequence_number'], 'sales_point_sequence_index');
        });

        Schema::table('sale_details', function (Blueprint $table): void {
            $table->foreignId('presentation_id')
                ->nullable()
                ->after('product_id')
                ->constrained('presentations')
                ->nullOnDelete();
            $table->string('presentation_name')->nullable()->after('presentation_id');
            $table->unsignedInteger('package_quantity')->default(1)->after('presentation_name');
            $table->unsignedInteger('units_per_package')->default(1)->after('package_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('presentation_id');
            $table->dropColumn(['presentation_name', 'package_quantity', 'units_per_package']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex('sales_point_sequence_index');
            $table->dropConstrainedForeignId('point_of_sale_id');
            $table->dropColumn(['sequence_number', 'notes']);
        });
    }
};
