<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table): void {
            $table->unsignedInteger('sequence_number')->nullable()->after('reference');
            $table->text('notes')->nullable()->after('status');
            $table->unique(['warehouse_id', 'sequence_number'], 'purchases_warehouse_sequence_unique');
        });

        Schema::table('purchase_details', function (Blueprint $table): void {
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
        Schema::table('purchase_details', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('presentation_id');
            $table->dropColumn(['presentation_name', 'package_quantity', 'units_per_package']);
        });

        Schema::table('purchases', function (Blueprint $table): void {
            $table->dropUnique('purchases_warehouse_sequence_unique');
            $table->dropColumn(['sequence_number', 'notes']);
        });
    }
};
