<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->foreignId('commercial_origin_id')->nullable()->after('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_executive_id')->nullable()->after('commercial_origin_id')->constrained('personnel')->restrictOnDelete();
            $table->index(['company_id', 'commercial_origin_id']);
            $table->index(['company_id', 'sales_executive_id']);
        });
    }

    public function down(): void
    {
        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'commercial_origin_id']);
            $table->dropIndex(['company_id', 'sales_executive_id']);
            $table->dropConstrainedForeignId('sales_executive_id');
            $table->dropConstrainedForeignId('commercial_origin_id');
        });
    }
};
