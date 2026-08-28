<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\ClassSession;
use App\Models\Personnel;
use App\Models\StudentAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherModuleController extends Controller
{
    public function index(Request $request): View
    {
        $personnel = $this->personnel($request);
        $modules = AcademicModule::query()
            ->with(['program', 'level', 'studentAssignments', 'studentResults', 'classSessions' => fn ($query) => $query->whereDate('class_date', today())->withCount('attendances')])
            ->whereHas('currentTeacherAssignment', fn ($query) => $query->where('personnel_id', $personnel->id))
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();

        return view('teacher.modules.index', compact('modules'));
    }

    public function start(Request $request, AcademicModule $module): RedirectResponse
    {
        $personnel = $this->authorizedTeacher($request, $module);
        if (today()->lt($module->start_date) || today()->gt($module->end_date)) {
            throw ValidationException::withMessages(['class' => 'El módulo no se encuentra vigente en la fecha actual.']);
        }

        $session = ClassSession::firstOrCreate(
            ['academic_module_id' => $module->id, 'class_date' => today()->toDateString()],
            ['company_id' => $module->company_id, 'personnel_id' => $personnel->id, 'started_by' => $request->user()->id, 'started_at' => now()],
        );

        return redirect()->route('teacher.modules.attendance.edit', [$module, $session])->with('success', 'Clase iniciada. Ya puede registrar la asistencia.');
    }

    public function editAttendance(Request $request, AcademicModule $module, ClassSession $session): View
    {
        $this->authorizedTeacher($request, $module);
        abort_unless($session->academic_module_id === $module->id, 404);
        $module->load(['program', 'level', 'studentAssignments.student']);
        $session->load('attendances');
        $attendanceByStudent = $session->attendances->keyBy('student_id');

        return view('teacher.modules.attendance', compact('module', 'session', 'attendanceByStudent'));
    }

    public function updateAttendance(Request $request, AcademicModule $module, ClassSession $session): RedirectResponse
    {
        $this->authorizedTeacher($request, $module);
        abort_unless($session->academic_module_id === $module->id, 404);
        $data = $request->validate([
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
        ]);
        $studentIds = $module->studentAssignments()->pluck('student_id')->map(fn ($id) => (int) $id);
        $submittedIds = collect(array_keys($data['attendance']))->map(fn ($id) => (int) $id);
        if ($submittedIds->diff($studentIds)->isNotEmpty() || $studentIds->diff($submittedIds)->isNotEmpty()) {
            throw ValidationException::withMessages(['attendance' => 'La lista de asistencia no coincide con los estudiantes asignados al módulo.']);
        }

        DB::transaction(function () use ($data, $module, $request, $session): void {
            foreach ($data['attendance'] as $studentId => $status) {
                StudentAttendance::updateOrCreate(
                    ['class_session_id' => $session->id, 'student_id' => (int) $studentId],
                    ['company_id' => $module->company_id, 'status' => $status, 'recorded_by' => $request->user()->id, 'recorded_at' => now()],
                );
            }
        });

        return redirect()->route('teacher.modules.index')->with('success', 'Asistencia registrada correctamente.');
    }

    private function personnel(Request $request): Personnel
    {
        abort_unless($request->user()->personnel_id !== null, 403, 'El usuario no está vinculado a personal.');

        return $request->user()->personnel;
    }

    private function authorizedTeacher(Request $request, AcademicModule $module): Personnel
    {
        $personnel = $this->personnel($request);
        abort_unless($module->currentTeacherAssignment()->where('personnel_id', $personnel->id)->exists(), 403);

        return $personnel;
    }
}
