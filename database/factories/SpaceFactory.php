<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PrivateSpaceType;
use App\Models\Space;
use App\Models\SpaceMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Space>
 */
class SpaceFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory()->create();
        $title = fake()->company().' Lodge';

        return [
            'company_id' => $company->id,
            'space_mode_id' => SpaceMode::query()->firstOrCreate(
                ['slug' => 'privado'],
                ['name' => 'Privado', 'is_active' => true],
            )->id,
            'private_space_type_id' => PrivateSpaceType::query()->firstOrCreate(
                ['slug' => 'casa'],
                ['name' => 'Casa', 'is_active' => true],
            )->id,
            'shared_space_type_id' => null,
            'title' => $title,
            'name' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'short_description' => fake()->sentence(),
            'full_description' => fake()->paragraph(),
            'max_capacity' => fake()->numberBetween(1, 8),
            'bedrooms_count' => fake()->numberBetween(1, 4),
            'beds_count' => fake()->numberBetween(1, 6),
            'private_bathrooms_count' => fake()->numberBetween(0, 3),
            'shared_bathrooms_count' => fake()->numberBetween(0, 2),
            'status' => 'draft',
            'created_by' => User::factory()->create(['company_id' => $company->id])->id,
        ];
    }
}
