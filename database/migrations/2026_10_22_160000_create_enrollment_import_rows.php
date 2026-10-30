<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('batch');
            $table->string('source_number');
            $table->string('fingerprint', 64);
            $table->foreignId('rectorate_application_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'batch', 'source_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_import_rows');
    }
};
