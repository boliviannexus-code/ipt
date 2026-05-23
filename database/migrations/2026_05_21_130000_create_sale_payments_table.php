<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method_name');
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'payment_method_id']);
        });

        foreach (['Efectivo', 'QR', 'Transferencia bancaria', 'Tarjeta'] as $name) {
            DB::table('payment_methods')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
