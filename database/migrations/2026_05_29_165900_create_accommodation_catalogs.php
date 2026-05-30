<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createCatalogTable('space_modes');
        $this->createCatalogTable('private_space_types');
        $this->createCatalogTable('shared_space_types');

        $this->createCatalogTable('bed_types', function (Blueprint $table): void {
            $table->unsignedSmallInteger('capacity')->default(1);
        });

        $this->createCatalogTable('bathroom_types');
        $this->createCatalogTable('general_services');
        $this->createCatalogTable('room_services');
    }

    public function down(): void
    {
        Schema::dropIfExists('room_services');
        Schema::dropIfExists('general_services');
        Schema::dropIfExists('bathroom_types');
        Schema::dropIfExists('bed_types');
        Schema::dropIfExists('shared_space_types');
        Schema::dropIfExists('private_space_types');
        Schema::dropIfExists('space_modes');
    }

    private function createCatalogTable(string $tableName, ?Closure $extraColumns = null): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($extraColumns): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $extraColumns?->call($this, $table);

            $table->index(['is_active', 'sort_order']);
        });
    }
};
