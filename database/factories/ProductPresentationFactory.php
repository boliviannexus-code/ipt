<?php

namespace Database\Factories;

use App\Models\Presentation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presentation>
 */
class ProductPresentationFactory extends Factory
{
    protected $model = Presentation::class;

    public function definition(): array
    {
        $units = fake()->unique()->numberBetween(2, 50);

        return [
            'name' => 'Caja x '.$units,
            'units_per_package' => $units,
            'is_active' => true,
        ];
    }
}
