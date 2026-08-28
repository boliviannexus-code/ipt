<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rectorate_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('first_name', 100);
            $table->string('paternal_surname', 100);
            $table->string('maternal_surname', 100)->nullable();
            $table->date('birth_date');
            $table->string('email');
            $table->string('phone', 30);
            $table->unsignedSmallInteger('current_step')->default(1);
            $table->string('status', 30)->default('draft');
            $table->timestamps();
            $table->index(['company_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rectorate_applications');
    }
};
