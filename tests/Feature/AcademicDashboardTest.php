<?php

namespace Tests\Feature;

use App\Models\AcademicModule;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AcademicDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_academic_information_instead_of_administration_metrics(): void
    {
        $company = Company::factory()->create(['name' => 'Instituto Académico']);
        $user = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('dashboard.view');
        $user->givePermissionTo('dashboard.view');
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        Plan::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Regular', 'monthly_cost' => 300]);
        $level = ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'name' => 'Básico 1', 'position' => 1]);
        AcademicModule::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'program_level_id' => $level->id, 'name' => 'Módulo Básico 1', 'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00', 'start_date' => today()->subDay(), 'end_date' => today()->addWeek()]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard académico')
            ->assertSee('Estudiantes inscritos')
            ->assertSee('Programas y matrícula')
            ->assertSee('Inglés')
            ->assertSee('Módulo Básico 1')
            ->assertSee('Atención académica')
            ->assertDontSee('Roles y permisos')
            ->assertDontSee('Actividad reciente');
    }
}
