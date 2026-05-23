<?php

namespace Database\Seeders;

use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

class MeasurementUnitSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Unidad', 'abbreviation' => 'un'],
            ['name' => 'Caja', 'abbreviation' => 'cja'],
            ['name' => 'Paquete', 'abbreviation' => 'paq'],
            ['name' => 'Kilogramo', 'abbreviation' => 'kg'],
            ['name' => 'Gramo', 'abbreviation' => 'g'],
            ['name' => 'Litro', 'abbreviation' => 'l'],
            ['name' => 'Metro', 'abbreviation' => 'm'],
        ] as $unit) {
            MeasurementUnit::firstOrCreate(
                ['abbreviation' => $unit['abbreviation']],
                ['name' => $unit['name'], 'is_active' => true]
            );
        }
    }
}
