<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE spaces DROP CONSTRAINT IF EXISTS spaces_status_check');
        DB::statement("ALTER TABLE spaces ADD CONSTRAINT spaces_status_check CHECK (status IN ('draft', 'completed', 'approved', 'active', 'inactive'))");

        Schema::table('spaces', function (Blueprint $table): void {
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('spaces', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
        });

        DB::statement('ALTER TABLE spaces DROP CONSTRAINT IF EXISTS spaces_status_check');
        DB::statement("ALTER TABLE spaces ADD CONSTRAINT spaces_status_check CHECK (status IN ('draft', 'active', 'inactive'))");
    }
};
