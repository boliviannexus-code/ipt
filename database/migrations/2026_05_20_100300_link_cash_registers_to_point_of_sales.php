<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table): void {
            $table->foreignId('point_of_sale_id')
                ->nullable()
                ->after('id')
                ->constrained('point_of_sales')
                ->restrictOnDelete();

            $table->index(['point_of_sale_id', 'status'], 'cash_registers_point_status_index');
            $table->index(['user_id', 'status'], 'cash_registers_user_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table): void {
            $table->dropIndex('cash_registers_point_status_index');
            $table->dropIndex('cash_registers_user_status_index');
            $table->dropConstrainedForeignId('point_of_sale_id');
        });
    }
};
