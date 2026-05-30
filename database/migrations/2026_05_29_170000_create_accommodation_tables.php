<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('space_mode_id')->constrained()->restrictOnDelete();
            $table->foreignId('private_space_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('shared_space_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('title')->nullable();
            $table->string('name')->nullable();
            $table->string('slug');
            $table->text('short_description')->nullable();
            $table->longText('full_description')->nullable();
            $table->unsignedInteger('max_capacity')->nullable();
            $table->unsignedInteger('bedrooms_count')->nullable();
            $table->unsignedInteger('beds_count')->nullable();
            $table->unsignedInteger('private_bathrooms_count')->nullable();
            $table->unsignedInteger('shared_bathrooms_count')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('space_rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('room_number')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('bathroom_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('max_capacity')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'space_id']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('room_beds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('space_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bed_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('capacity_per_bed');
            $table->unsignedInteger('total_capacity');
            $table->timestamps();

            $table->index(['company_id', 'space_room_id']);
        });

        Schema::create('space_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->enum('type', ['main', 'gallery'])->default('gallery');
            $table->unsignedInteger('sort_order')->nullable();
            $table->string('alt_text')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'space_id', 'type']);
        });

        Schema::create('room_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('space_room_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->enum('type', ['main', 'gallery'])->default('gallery');
            $table->unsignedInteger('sort_order')->nullable();
            $table->string('alt_text')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'space_room_id', 'type']);
        });

        Schema::create('space_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->string('country');
            $table->string('state_or_region')->nullable();
            $table->string('city');
            $table->string('zone_or_neighborhood')->nullable();
            $table->string('address');
            $table->string('reference')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('postal_code')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'space_id']);
        });

        Schema::create('space_general_service', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('general_service_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'space_id', 'general_service_id']);
        });

        Schema::create('room_room_service', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('space_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_service_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'space_room_id', 'room_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_room_service');
        Schema::dropIfExists('space_general_service');
        Schema::dropIfExists('space_locations');
        Schema::dropIfExists('room_photos');
        Schema::dropIfExists('space_photos');
        Schema::dropIfExists('room_beds');
        Schema::dropIfExists('space_rooms');
        Schema::dropIfExists('spaces');
    }
};
