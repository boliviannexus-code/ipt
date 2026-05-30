<?php

namespace Database\Seeders;

use App\Models\BathroomType;
use App\Models\BedType;
use App\Models\GeneralService;
use App\Models\PrivateSpaceType;
use App\Models\RoomService;
use App\Models\SharedSpaceType;
use App\Models\SpaceMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AccommodationCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCatalog(SpaceMode::class, [
            ['name' => 'Privado'],
            ['name' => 'Compartido'],
        ]);

        $this->seedCatalog(PrivateSpaceType::class, [
            ['name' => 'Casa'],
            ['name' => 'Apartamento'],
            ['name' => 'Cabaña'],
            ['name' => 'Departamento'],
            ['name' => 'Villa'],
            ['name' => 'Suite completa'],
            ['name' => 'Otro'],
        ]);

        $this->seedCatalog(SharedSpaceType::class, [
            ['name' => 'Hotel'],
            ['name' => 'Hostal'],
            ['name' => 'Alojamiento'],
            ['name' => 'Residencia'],
            ['name' => 'Cabaña'],
            ['name' => 'Hospedaje'],
            ['name' => 'Otro'],
        ]);

        $this->seedCatalog(BedType::class, [
            ['name' => 'Cama individual', 'capacity' => 1],
            ['name' => 'Cama matrimonial', 'capacity' => 2],
            ['name' => 'Cama queen', 'capacity' => 2],
            ['name' => 'Cama king', 'capacity' => 2],
            ['name' => 'Litera', 'capacity' => 1],
            ['name' => 'Sofá cama', 'capacity' => 2],
        ]);

        $this->seedCatalog(BathroomType::class, [
            ['name' => 'Privado'],
            ['name' => 'Compartido'],
            ['name' => 'Mixto'],
            ['name' => 'Sin baño'],
        ]);

        $this->seedCatalog(GeneralService::class, [
            ['name' => 'WiFi'],
            ['name' => 'Parqueo'],
            ['name' => 'Salón de reuniones'],
            ['name' => 'Parrillero'],
            ['name' => 'Cocina compartida'],
            ['name' => 'Calefacción'],
            ['name' => 'Lavandería'],
            ['name' => 'Recepción'],
            ['name' => 'Coworking'],
            ['name' => 'Desayuno'],
            ['name' => 'Restaurante'],
            ['name' => 'Guarda equipaje'],
        ]);

        $this->seedCatalog(RoomService::class, [
            ['name' => 'Baño privado'],
            ['name' => 'Ducha'],
            ['name' => 'Agua caliente'],
            ['name' => 'TV'],
            ['name' => 'Calefacción'],
            ['name' => 'Escritorio'],
            ['name' => 'Vista al lago'],
            ['name' => 'Ropa de cama'],
            ['name' => 'Toallas'],
            ['name' => 'WiFi en habitación'],
        ]);
    }

    private function seedCatalog(string $modelClass, array $items): void
    {
        foreach ($items as $index => $item) {
            $name = $item['name'];

            $modelClass::withTrashed()->firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $item['description'] ?? null,
                    'is_active' => $item['is_active'] ?? true,
                    'sort_order' => $item['sort_order'] ?? $index + 1,
                    ...array_diff_key($item, array_flip(['name', 'description', 'is_active', 'sort_order'])),
                ],
            );
        }
    }
}
