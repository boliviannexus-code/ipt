<?php

namespace Database\Factories;

use App\Models\Presentation;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presentation>
 */
class PresentationFactory extends Factory
{
    public function definition(): array
    {
        $units = fake()->unique()->numberBetween(2, 60);

        return [
            'name' => 'Caja x '.$units,
            'company_id' => Company::factory(),
            'units_per_package' => $units,
            'is_active' => true,
        ];
    }
}
