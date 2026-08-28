<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentAssignment;
use App\Models\AcademicModuleTeacherAssignment;
use App\Models\Area;
use App\Models\Company;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TeacherClassAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_start_assigned_module_class_and_record_attendance(): void
    {
        [$user, $module, $student] = $this->context();

        $this->actingAs($user)->get(route('teacher.modules.index'))
            ->assertOk()->assertSee($module->name)->assertSee('Iniciar clase');

        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module))
            ->assertRedirect();
        $session = $module->classSessions()->sole();
        $this->actingAs($user)->post(route('teacher.modules.sessions.start', $module))->assertRedirect();
        $this->assertDatabaseCount('class_sessions', 1);

        $this->actingAs($user)->get(route('teacher.modules.attendance.edit', [$module, $session]))
            ->assertOk()->assertSee($student->first_name)->assertSee('Presente');
        $this->actingAs($user)->put(route('teacher.modules.attendance.update', [$module, $session]), [
            'attendance' => [$student->id => 'late'],
        ])->assertRedirect(route('teacher.modules.index'));

        $this->assertDatabaseHas('student_attendances', [
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'late',
            'recorded_by' => $user->id,
        ]);
    }

    public function test_teacher_cannot_manage_a_module_assigned_to_another_teacher(): void
    {
        [$user, $module] = $this->context();
        $otherPersonnel = Personnel::withoutGlobalScope('company')->create(['company_id' => $user->company_id, 'position_id' => $user->personnel->position_id, 'first_name' => 'Otro', 'paternal_surname' => 'Docente', 'identity_document' => '9002', 'phone' => '70000002', 'email' => 'otro@example.com', 'is_active' => true]);
        $otherUser = User::factory()->create(['company_id' => $user->company_id, 'personnel_id' => $otherPersonnel->id]);
        $otherUser->givePermissionTo(['teaching.view', 'teaching.manage']);

        $this->actingAs($otherUser)->post(route('teacher.modules.sessions.start', $module))->assertForbidden();
        $this->assertDatabaseCount('class_sessions', 0);
    }

    private function context(): array
    {
        $company = Company::factory()->create();
        Permission::findOrCreate('teaching.view');
        Permission::findOrCreate('teaching.manage');
        $area = Area::withoutGlobalScope('company')->create(['company_id' => $company->id, 'name' => 'Académico', 'is_active' => true]);
        $position = Position::withoutGlobalScope('company')->create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Docente', 'is_academic' => true, 'is_active' => true]);
        $personnel = Personnel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'position_id' => $position->id, 'first_name' => 'Ana', 'paternal_surname' => 'Flores', 'identity_document' => '9001', 'phone' => '70000001', 'email' => 'ana@example.com', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id, 'personnel_id' => $personnel->id]);
        $user->givePermissionTo(['teaching.view', 'teaching.manage']);
        $program = Program::withoutGlobalScope('company')->create(['company_id' => $company->id, 'title' => 'Inglés', 'duration_months' => 12]);
        $level = ProgramLevel::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'name' => 'Básico 1', 'position' => 1]);
        $module = AcademicModule::withoutGlobalScope('company')->create(['company_id' => $company->id, 'program_id' => $program->id, 'program_level_id' => $level->id, 'name' => 'Módulo Básico 1', 'modality' => 'virtual', 'starts_at' => '18:00', 'ends_at' => '20:00', 'start_date' => today()->subDay(), 'end_date' => today()->addMonth()]);
        AcademicModuleTeacherAssignment::withoutGlobalScope('company')->create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'personnel_id' => $personnel->id, 'assigned_at' => now()]);
        $student = Student::withoutGlobalScope('company')->create(['company_id' => $company->id, 'identity_document' => '3001', 'first_name' => 'Lucía', 'paternal_surname' => 'Pérez', 'birth_date' => '2010-01-01', 'gender' => 'Femenino', 'is_active' => true]);
        AcademicModuleStudentAssignment::withoutGlobalScope('company')->create(['company_id' => $company->id, 'academic_module_id' => $module->id, 'student_id' => $student->id, 'assigned_by' => $user->id, 'assigned_at' => now()]);

        return [$user, $module, $student];
    }
}
