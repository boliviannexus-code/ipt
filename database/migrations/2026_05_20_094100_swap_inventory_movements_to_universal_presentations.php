<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->foreignId('presentation_id')
                ->nullable()
                ->after('product_id')
                ->constrained('presentations')
                ->nullOnDelete();
        });

        if (Schema::hasTable('product_presentations') && Schema::hasColumn('inventory_movements', 'product_presentation_id')) {
            DB::table('inventory_movements')
                ->whereNotNull('product_presentation_id')
                ->orderBy('id')
                ->get(['id', 'product_presentation_id'])
                ->each(function ($movement): void {
                    $legacyPresentation = DB::table('product_presentations')
                        ->where('id', $movement->product_presentation_id)
                        ->first(['name']);

                    if (! $legacyPresentation) {
                        return;
                    }

                    $presentationId = DB::table('presentations')
                        ->where('name', $legacyPresentation->name)
                        ->value('id');

                    if ($presentationId) {
                        DB::table('inventory_movements')
                            ->where('id', $movement->id)
                            ->update(['presentation_id' => $presentationId]);
                    }
                });
        }

        Schema::table('inventory_movements', function (Blueprint $table): void {
            if (Schema::hasColumn('inventory_movements', 'product_presentation_id')) {
                $table->dropIndex('inventory_product_warehouse_presentation_index');
                $table->dropConstrainedForeignId('product_presentation_id');
            }
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->index(['product_id', 'warehouse_id', 'presentation_id'], 'inventory_product_warehouse_presentation_universal_index');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->dropIndex('inventory_product_warehouse_presentation_universal_index');
            $table->dropConstrainedForeignId('presentation_id');
        });
    }
};
