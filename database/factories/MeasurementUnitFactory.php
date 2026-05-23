<?php

namespace Database\Factories;

use App\Models\MeasurementUnit;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeasurementUnit>
 */
class MeasurementUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'company_id' => Company::factory(),
            'abbreviation' => fake()->unique()->lexify('???'),
            'is_active' => true,
        ];
    }
}
