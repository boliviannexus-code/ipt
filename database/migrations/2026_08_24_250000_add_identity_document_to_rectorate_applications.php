<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->string('identity_document', 30)->nullable()->after('customer_id');
            $table->index(['company_id', 'identity_document', 'created_at'], 'rectorate_company_ci_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->dropIndex('rectorate_company_ci_created_index');
            $table->dropColumn('identity_document');
        });
    }
};
