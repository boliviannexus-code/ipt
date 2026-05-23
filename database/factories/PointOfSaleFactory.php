<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\PointOfSale;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointOfSale>
 */
class PointOfSaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'warehouse_id' => fn (array $attributes): int => Warehouse::factory()
                ->for(Branch::query()->find($attributes['branch_id']))
                ->create()
                ->id,
            'company_id' => fn (array $attributes): ?int => Warehouse::query()->find($attributes['warehouse_id'])?->company_id,
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->bothify('PV-###'),
            'receipt_prefix' => fake()->unique()->bothify('PV-###'),
            'sequence_number' => 1,
            'receipt_next_number' => 1,
            'receipt_digits' => 6,
            'is_active' => true,
        ];
    }

    public function forWarehouse(int $warehouseId): static
    {
        $warehouse = Warehouse::query()->find($warehouseId);

        return $this->state(fn (): array => [
            'company_id' => $warehouse?->company_id,
            'branch_id' => $warehouse?->branch_id,
            'warehouse_id' => $warehouseId,
        ]);
    }
}
