<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Services\Academic\ModuleCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherModuleResultController extends Controller
{
    public function __construct(private readonly ModuleCompletionService $completion) {}

    public function edit(Request $request, AcademicModule $module): View
    {
        $this->authorizeTeacher($request, $module);
        abort_if(today()->lt($module->end_date), 409, 'El módulo todavía no finalizó.');
        $module->load(['program', 'level', 'studentAssignments.student', 'studentResults.charge']);
        $resultsByStudent = $module->studentResults->keyBy('student_id');

        return view('teacher.modules.results', compact('module', 'resultsByStudent'));
    }

    public function update(Request $request, AcademicModule $module): RedirectResponse
    {
        $this->authorizeTeacher($request, $module);
        abort_if(today()->lt($module->end_date), 409, 'El módulo todavía no finalizó.');
        $data = $request->validate([
            'results' => ['required', 'array', 'min:1'],
            'results.*' => ['required', Rule::in(['approved', 'failed'])],
            'concepts' => ['required', 'array', 'min:1'],
            'concepts.*' => ['required', 'string', 'max:160'],
        ]);
        $studentIds = $module->studentAssignments()->pluck('student_id')->map(fn ($id) => (int) $id);
        $submittedIds = collect(array_keys($data['results']))->map(fn ($id) => (int) $id);
        $conceptStudentIds = collect(array_keys($data['concepts']))->map(fn ($id) => (int) $id);
        if ($submittedIds->diff($studentIds)->isNotEmpty() || $studentIds->diff($submittedIds)->isNotEmpty() || $conceptStudentIds->diff($studentIds)->isNotEmpty() || $studentIds->diff($conceptStudentIds)->isNotEmpty()) {
            throw ValidationException::withMessages(['results' => 'La lista no coincide con los estudiantes asignados al módulo.']);
        }

        $this->completion->complete($request->user(), $module, $data['results'], $data['concepts']);

        return redirect()->route('teacher.modules.index')->with('success', 'Módulo finalizado y cargos generados correctamente.');
    }

    private function authorizeTeacher(Request $request, AcademicModule $module): void
    {
        abort_unless($request->user()->personnel_id !== null, 403);
        abort_unless($module->currentTeacherAssignment()->where('personnel_id', $request->user()->personnel_id)->exists(), 403);
    }
}
