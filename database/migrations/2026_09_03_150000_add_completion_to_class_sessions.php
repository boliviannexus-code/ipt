<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table): void {
            $table->timestamp('ended_at')->nullable()->after('started_at');
            $table->foreignId('finalized_by')->nullable()->after('ended_at')->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropColumn('ended_at');
        });
    }
};
