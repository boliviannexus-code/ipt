<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'company_id' => fn (array $attributes): ?int => Branch::query()->find($attributes['branch_id'])?->company_id,
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->bothify('ALM-###'),
            'is_active' => true,
        ];
    }
}
