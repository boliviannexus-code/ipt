<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spaces', function (Blueprint $table): void {
            $table->boolean('photos_skipped')->default(false)->after('shared_bathrooms_count');
        });

        Schema::table('space_rooms', function (Blueprint $table): void {
            $table->boolean('photos_skipped')->default(false)->after('max_capacity');
        });
    }

    public function down(): void
    {
        Schema::table('space_rooms', function (Blueprint $table): void {
            $table->dropColumn('photos_skipped');
        });

        Schema::table('spaces', function (Blueprint $table): void {
            $table->dropColumn('photos_skipped');
        });
    }
};
