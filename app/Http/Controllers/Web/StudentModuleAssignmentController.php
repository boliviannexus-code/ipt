<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentAssignment;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentModuleAssignmentController extends Controller
{
    public function create(Request $request, Student $student): View
    {
        $this->ensureEnabled($student);
        $programIds = $student->contracts()->where('status', 'enrolled')->pluck('program_id');
        $modules = AcademicModule::query()
            ->with(['program', 'level', 'currentTeacherAssignment.personnel'])
            ->whereIn('program_id', $programIds)
            ->whereDate('end_date', '>=', today())
            ->whereDoesntHave('studentAssignments', fn ($query) => $query->where('student_id', $student->id))
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();
        $student->load(['moduleAssignments.module.program', 'moduleAssignments.module.level']);

        return view($request->ajax() ? 'students.partials.module-form' : 'students.module-form', compact('student', 'modules'));
    }

    public function store(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        $this->ensureEnabled($student);
        $data = $request->validate(['academic_module_id' => ['required', 'integer']]);
        $programIds = $student->contracts()->where('status', 'enrolled')->pluck('program_id');
        $module = AcademicModule::query()
            ->whereKey($data['academic_module_id'])
            ->whereIn('program_id', $programIds)
            ->whereDate('end_date', '>=', today())
            ->first();

        if (! $module) {
            throw ValidationException::withMessages(['academic_module_id' => 'Seleccione un módulo vigente del programa del estudiante.']);
        }

        AcademicModuleStudentAssignment::firstOrCreate(
            ['academic_module_id' => $module->id, 'student_id' => $student->id],
            ['company_id' => $student->company_id, 'assigned_by' => $request->user()->id, 'assigned_at' => now()],
        );

        $message = 'Estudiante asignado al módulo correctamente.';
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'refresh_url' => route('students.index')]);
        }

        return redirect()->route('students.index')->with('success', $message);
    }

    private function ensureEnabled(Student $student): void
    {
        abort_unless($student->is_active && $student->contracts()->where('status', 'enrolled')->exists(), 404);
    }
}
