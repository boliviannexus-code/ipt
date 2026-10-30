<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentAssignment;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentModuleAssignmentController extends Controller
{
    public function create(Request $request, Student $student): View
    {
        $this->ensureEnabled($student);
        $eligibleLevelsByProgram = $this->eligibleLevelsByProgram($student);
        $openAssignment = $student->moduleAssignments()
            ->with('module')
            ->whereNull('released_at')
            ->latest('assigned_at')
            ->first();
        $modules = $openAssignment
            ? collect()
            : AcademicModule::query()
                ->with(['program', 'level', 'currentTeacherAssignment.personnel'])
                ->whereIn('program_level_id', $eligibleLevelsByProgram->values())
                ->whereDate('end_date', '>=', today())
                ->whereDoesntHave('studentAssignments', fn ($query) => $query->where('student_id', $student->id))
                ->orderBy('start_date')
                ->orderBy('name')
                ->get();
        $student->load(['moduleAssignments.module.program', 'moduleAssignments.module.level']);

        return view($request->ajax() ? 'students.partials.module-form' : 'students.module-form', compact('student', 'modules', 'openAssignment'));
    }

    public function store(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        $this->ensureEnabled($student);
        $data = $request->validate(['academic_module_id' => ['required', 'integer']]);
        DB::transaction(function () use ($data, $request, $student): void {
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $eligibleLevelsByProgram = $this->eligibleLevelsByProgram($student);
            $module = AcademicModule::query()
                ->whereKey($data['academic_module_id'])
                ->whereIn('program_level_id', $eligibleLevelsByProgram->values())
                ->whereDate('end_date', '>=', today())
                ->first();

            if (! $module) {
                throw ValidationException::withMessages(['academic_module_id' => 'Seleccione un módulo vigente correspondiente al nivel actual del estudiante.']);
            }

            $hasAnotherOpenModule = $student->moduleAssignments()
                ->where('academic_module_id', '!=', $module->id)
                ->whereNull('released_at')
                ->exists();
            if ($hasAnotherOpenModule) {
                throw ValidationException::withMessages(['academic_module_id' => 'El estudiante ya está asignado a un módulo abierto. Debe cerrar ese módulo antes de realizar una nueva asignación.']);
            }

            AcademicModuleStudentAssignment::firstOrCreate(
                ['academic_module_id' => $module->id, 'student_id' => $student->id],
                ['company_id' => $student->company_id, 'assigned_by' => $request->user()->id, 'assigned_at' => now()],
            );
        });

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

    private function eligibleLevelsByProgram(Student $student): Collection
    {
        return $student->contracts()
            ->where('status', 'enrolled')
            ->with(['currentProgramLevel', 'program.levels'])
            ->get()
            ->mapWithKeys(function ($contract): array {
                $levelId = $contract->current_program_level_id
                    ?? $contract->program->levels->firstWhere('position', 1)?->id;

                return $levelId === null ? [] : [(int) $contract->program_id => (int) $levelId];
            });
    }
}
