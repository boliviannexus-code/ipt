<?php

namespace Database\Factories;

use App\Models\CashRegister;
use App\Models\PointOfSale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegister>
 */
class CashRegisterFactory extends Factory
{
    public function definition(): array
    {
        $pointOfSale = PointOfSale::factory()->create();

        return [
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $pointOfSale->branch_id,
            'user_id' => User::factory(),
            'opening_amount' => fake()->randomFloat(2, 0, 500),
            'closing_amount' => null,
            'opened_at' => now(),
            'closed_at' => null,
            'status' => 'open',
        ];
    }
}
