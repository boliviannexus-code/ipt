<?php

namespace Database\Factories;

use App\Models\BedType;
use App\Models\RoomBed;
use App\Models\SpaceRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomBed>
 */
class RoomBedFactory extends Factory
{
    public function definition(): array
    {
        $room = SpaceRoom::factory()->create();
        $bedType = BedType::query()->firstOrCreate(
            ['slug' => 'cama-individual'],
            ['name' => 'Cama individual', 'capacity' => 1, 'is_active' => true],
        );
        $quantity = fake()->numberBetween(1, 3);

        return [
            'company_id' => $room->company_id,
            'space_room_id' => $room->id,
            'bed_type_id' => $bedType->id,
            'quantity' => $quantity,
            'capacity_per_bed' => $bedType->capacity,
            'total_capacity' => $quantity * $bedType->capacity,
        ];
    }
}
