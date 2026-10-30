<?php

namespace Tests\Feature\Enrollment;

use App\Models\Campus;
use App\Models\CommercialOrigin;
use App\Models\Company;
use App\Models\Personnel;
use App\Models\Plan;
use App\Models\Program;
use App\Services\Imports\EnrollmentCsvImport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnrollmentCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_import_reuses_holders_and_is_idempotent_and_atomic(): void
    {
        $company = Company::factory()->create();
        $campus = Campus::create(['company_id' => $company->id, 'name' => 'Migrada', 'code' => '0', 'short' => 'LPM', 'address' => 'Migrada']);
        $program = Program::create(['company_id' => $company->id, 'title' => 'SABADO', 'duration_months' => 15, 'enrollment_code' => 'IAS']);
        $plan = Plan::create(['company_id' => $company->id, 'name' => 'BECA', 'monthly_cost' => 300]);
        $plan->programs()->attach($program);
        CommercialOrigin::create(['company_id' => $company->id, 'name' => 'Facebook']);
        $area = DB::table('areas')->insertGetId(['company_id' => $company->id, 'name' => 'Comercial', 'created_at' => now(), 'updated_at' => now()]);
        $position = DB::table('positions')->insertGetId(['company_id' => $company->id, 'name' => 'Ventas', 'area_id' => $area, 'created_at' => now(), 'updated_at' => now()]);
        Personnel::create(['company_id' => $company->id, 'position_id' => $position, 'campus_id' => $campus->id, 'first_name' => 'Ana', 'paternal_surname' => 'Perez', 'identity_document' => '1234567', 'birth_date' => '1985-12-11', 'phone' => '77777777', 'email' => 'ana@example.com', 'is_active' => true, 'is_sales_enabled' => true]);
        $row = ['numero' => '1', 'fecha' => '3/30/2026', 'nombre_titular' => 'JUAN', 'paterno_titular' => 'PEREZ', 'materno_titular' => 'LOPEZ', 'ci_titular' => '1234567', 'programa' => 'sabado', 'plan' => 'BECA', 'ejecutivo_de_ventas' => 'Ana Perez', 'origen_comercial' => 'FACEBOOK', 'nombre_estudiante' => 'JUAN', 'paterno_estudiante' => 'PEREZ', 'materno_estudiante' => 'LOPEZ', 'ci_estudiante' => '1234567', 'contacto' => '77777777.00'];
        $other = [...$row, 'numero' => '2', 'nombre_estudiante' => 'PEDRO', 'ci_estudiante' => '7654321'];
        $service = app(EnrollmentCsvImport::class);
        $preview = $service->run([$row, $other], $campus, 'test', 'Otro');
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('customers', 0);
        $this->assertSame('IAS00001', $preview[0]['matricula']);
        $result = $service->run([$row, $other], $campus, 'test', 'Otro', commit: true);
        $this->assertSame($result[0]['titular_id'], $result[1]['titular_id']);
        $this->assertSame('Titular', $result[0]['parentesco']);
        $this->assertSame('Otro', $result[1]['parentesco']);
        $this->assertDatabaseHas('account_charges', ['period' => '2026-03-01', 'amount' => 300, 'paid_amount' => 0]);
        $this->assertDatabaseHas('enrollment_contracts', ['confirmed_at' => '2026-03-30 00:00:00', 'status' => 'pre_enrolled']);
        $this->assertDatabaseHas('students', ['birth_date' => '1985-12-11', 'phone' => '77777777']);
        $service->run([$row, $other], $campus, 'test', 'Otro', commit: true);
        $this->assertDatabaseCount('enrollment_contracts', 2);
        $this->assertDatabaseCount('account_charges', 2);
        $this->assertDatabaseCount('customers', 1);
        $this->assertNull(Carbon::getTestNow());
        try {
            $service->run([[...$row, 'numero' => '3', 'ci_estudiante' => '9876543'], [...$other, 'numero' => '4', 'plan' => 'NO EXISTE']], $campus, 'failed', 'Otro', commit: true);
            $this->fail('Debe fallar el lote completo');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Catálogo', $exception->getMessage());
        }
        $this->assertDatabaseCount('students', 2);
        $this->assertDatabaseCount('enrollment_import_rows', 2);
    }
}
