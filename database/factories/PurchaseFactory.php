<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'user_id' => User::factory(),
            'reference' => fake()->unique()->numerify('1-1-######'),
            'sequence_number' => fake()->unique()->numberBetween(1, 999999),
            'purchase_date' => fake()->date(),
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'status' => 'completed',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
