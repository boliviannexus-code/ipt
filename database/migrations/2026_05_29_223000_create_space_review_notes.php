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
        DB::statement("ALTER TABLE spaces ADD CONSTRAINT spaces_status_check CHECK (status IN ('draft', 'completed', 'needs_corrections', 'approved', 'active', 'inactive'))");

        Schema::create('space_review_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('correction');
            $table->text('message');
            $table->timestamps();

            $table->index(['space_id', 'created_at']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_review_notes');

        DB::statement('ALTER TABLE spaces DROP CONSTRAINT IF EXISTS spaces_status_check');
        DB::statement("ALTER TABLE spaces ADD CONSTRAINT spaces_status_check CHECK (status IN ('draft', 'completed', 'approved', 'active', 'inactive'))");
    }
};
