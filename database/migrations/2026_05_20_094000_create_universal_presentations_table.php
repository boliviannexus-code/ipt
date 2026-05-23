<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentations', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('units_per_package')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        if (Schema::hasTable('product_presentations')) {
            DB::table('product_presentations')
                ->select('name', 'units_per_package')
                ->whereNull('deleted_at')
                ->distinct()
                ->orderBy('name')
                ->get()
                ->each(function ($presentation): void {
                    DB::table('presentations')->updateOrInsert(
                        ['name' => $presentation->name],
                        [
                            'units_per_package' => $presentation->units_per_package,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('presentations');
    }
};
