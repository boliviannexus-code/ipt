<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel', function (Blueprint $table): void {
            $table->foreignId('campus_id')->nullable()->after('position_id')->constrained()->nullOnDelete();
            $table->index(['company_id', 'campus_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('personnel', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'campus_id', 'is_active']);
            $table->dropConstrainedForeignId('campus_id');
        });
    }
};
