<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class]);

        $company = Company::updateOrCreate(
            ['name' => 'Bolivian Nexus'],
            ['legal_name' => 'Bolivian Nexus', 'country' => 'Bolivia', 'is_active' => true],
        );

        $this->call([OrganizationSeeder::class]);

        $position = Position::withoutGlobalScope('company')->where('company_id', $company->id)->where('name', 'Director de Tecnología')->firstOrFail();
        $personnel = Personnel::withoutGlobalScope('company')->updateOrCreate(
            ['company_id' => $company->id, 'identity_document' => '8324984'],
            [
                'position_id' => $position->id,
                'first_name' => 'Álvaro',
                'paternal_surname' => 'Pacheco',
                'maternal_surname' => null,
                'birth_date' => null,
                'email' => 'boliviannexus@gmail.com',
                'is_active' => true,
            ],
        );

        $admin = User::updateOrCreate(
            ['email' => 'boliviannexus@gmail.com'],
            [
                'company_id' => $company->id,
                'personnel_id' => $personnel->id,
                'name' => $personnel->full_name,
                'password' => Hash::make('83249842'),
                'is_active' => true,
            ]
        );

        $admin->assignRole('super_admin');
    }
}
