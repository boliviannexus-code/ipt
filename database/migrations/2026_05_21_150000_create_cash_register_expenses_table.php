<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_register_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('point_of_sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('responsible_name');
            $table->text('detail');
            $table->decimal('amount', 12, 2);
            $table->timestamp('spent_at');
            $table->timestamps();

            $table->index(['cash_register_id', 'spent_at']);
            $table->index(['point_of_sale_id', 'spent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_expenses');
    }
};
