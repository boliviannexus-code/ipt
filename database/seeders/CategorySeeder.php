<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Alimentos', 'description' => 'Productos alimenticios de consumo general.'],
            ['name' => 'Bebidas', 'description' => 'Bebidas frias, calientes y embotelladas.'],
            ['name' => 'Limpieza', 'description' => 'Articulos de limpieza y cuidado del hogar.'],
            ['name' => 'Tecnologia', 'description' => 'Equipos, accesorios y consumibles tecnologicos.'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
