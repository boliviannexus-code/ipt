<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();

            $table->index(['company_id', 'is_active']);
        });

        Schema::table('warehouses', function (Blueprint $table): void {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();

            $table->index(['company_id', 'is_active']);
        });

        Schema::table('point_of_sales', function (Blueprint $table): void {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();

            $table->index(['company_id', 'is_active']);
        });

        DB::table('warehouses')
            ->join('branches', 'branches.id', '=', 'warehouses.branch_id')
            ->update(['warehouses.company_id' => DB::raw('branches.company_id')]);

        DB::table('point_of_sales')
            ->join('branches', 'branches.id', '=', 'point_of_sales.branch_id')
            ->update(['point_of_sales.company_id' => DB::raw('branches.company_id')]);
    }

    public function down(): void
    {
        Schema::table('point_of_sales', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'is_active']);
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('warehouses', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'is_active']);
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'is_active']);
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
