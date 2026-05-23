<?php

namespace Database\Seeders;

use App\Models\Presentation;
use Illuminate\Database\Seeder;

class PresentationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Unidad', 'units_per_package' => 1],
            ['name' => 'Caja x 10', 'units_per_package' => 10],
            ['name' => 'Caja x 20', 'units_per_package' => 20],
            ['name' => 'Paquete x 6', 'units_per_package' => 6],
            ['name' => 'Paquete x 12', 'units_per_package' => 12],
        ] as $presentation) {
            Presentation::firstOrCreate(
                ['name' => $presentation['name']],
                ['units_per_package' => $presentation['units_per_package'], 'is_active' => true]
            );
        }
    }
}
