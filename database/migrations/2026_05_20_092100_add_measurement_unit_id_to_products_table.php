<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $unitId = DB::table('measurement_units')->insertGetId([
            'name' => 'Unidad',
            'abbreviation' => 'un',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('products', function (Blueprint $table) use ($unitId): void {
            $table->foreignId('measurement_unit_id')
                ->default($unitId)
                ->after('category_id')
                ->constrained('measurement_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('measurement_unit_id');
        });
    }
};
