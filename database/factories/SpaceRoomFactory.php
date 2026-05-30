<?php

namespace Database\Factories;

use App\Models\BathroomType;
use App\Models\Space;
use App\Models\SpaceRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpaceRoom>
 */
class SpaceRoomFactory extends Factory
{
    public function definition(): array
    {
        $space = Space::factory()->create();

        return [
            'company_id' => $space->company_id,
            'space_id' => $space->id,
            'name' => 'Habitacion '.fake()->numberBetween(1, 50),
            'room_number' => (string) fake()->numberBetween(100, 999),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'bathroom_type_id' => BathroomType::query()->firstOrCreate(
                ['slug' => 'privado'],
                ['name' => 'Privado', 'is_active' => true],
            )->id,
            'max_capacity' => fake()->numberBetween(1, 4),
            'status' => 'draft',
        ];
    }
}
