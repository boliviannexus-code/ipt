<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use App\Models\MeasurementUnit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 5, 300);

        return [
            'name' => fake()->unique()->words(3, true),
            'company_id' => Company::factory(),
            'barcode' => fake()->unique()->ean13(),
            'category_id' => Category::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'description' => fake()->optional()->sentence(),
            'image_path' => null,
            'purchase_price' => $purchasePrice,
            'sale_price' => $purchasePrice * fake()->randomFloat(2, 1.15, 1.8),
            'minimum_stock' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
