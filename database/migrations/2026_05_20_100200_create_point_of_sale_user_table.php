<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_of_sale_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('point_of_sale_id')->constrained('point_of_sales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['point_of_sale_id', 'user_id'], 'point_of_sale_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_of_sale_user');
    }
};
