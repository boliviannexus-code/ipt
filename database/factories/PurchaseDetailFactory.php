<?php

namespace Database\Factories;

use App\Models\Presentation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseDetail>
 */
class PurchaseDetailFactory extends Factory
{
    public function definition(): array
    {
        $packageQuantity = fake()->numberBetween(1, 10);
        $unitsPerPackage = fake()->numberBetween(1, 30);
        $unitPrice = fake()->randomFloat(2, 5, 200);

        return [
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'presentation_id' => Presentation::factory(),
            'presentation_name' => 'Caja x '.$unitsPerPackage,
            'package_quantity' => $packageQuantity,
            'units_per_package' => $unitsPerPackage,
            'quantity' => $packageQuantity * $unitsPerPackage,
            'unit_price' => $unitPrice,
            'subtotal' => $packageQuantity * $unitPrice,
        ];
    }
}
