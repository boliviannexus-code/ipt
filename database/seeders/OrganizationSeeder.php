<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Company;
use App\Models\Position;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'Rectoría' => ['Rector', 'Secretaria', 'Director Administrativo', 'Director de Tecnología'],
            'Marketing' => ['Ejecutivo de Ventas'],
            'Académico' => ['Director Académico', 'Docente'],
        ];

        Company::query()->each(function (Company $company) use ($structure): void {
            foreach ($structure as $areaName => $positions) {
                $area = Area::withoutGlobalScope('company')->updateOrCreate(
                    ['company_id' => $company->id, 'name' => $areaName],
                    ['is_active' => true],
                );
                foreach ($positions as $positionName) {
                    Position::withoutGlobalScope('company')->updateOrCreate(
                        ['company_id' => $company->id, 'area_id' => $area->id, 'name' => $positionName],
                        [
                            'is_academic' => $areaName === 'Académico',
                            'is_sales_executive' => $positionName === 'Ejecutivo de Ventas',
                            'is_active' => true,
                        ],
                    );
                }
            }
        });
    }
}
