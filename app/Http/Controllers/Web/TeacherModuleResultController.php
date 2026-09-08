<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Services\Academic\ModuleCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherModuleResultController extends Controller
{
    public function __construct(private readonly ModuleCompletionService $completion) {}

    public function edit(Request $request, AcademicModule $module): View
    {
        $this->authorizeTeacher($request, $module);
        abort_if(today()->lt($module->end_date), 409, 'El módulo todavía no finalizó.');
        $module->load(['program', 'level', 'studentAssignments.student', 'studentResults']);
        $resultsByStudent = $module->studentResults->keyBy('student_id');
        $grading = $module->closed_at === null ? $this->completion->preview($module) : null;

        return view('teacher.modules.results', compact('module', 'resultsByStudent', 'grading'));
    }

    public function update(Request $request, AcademicModule $module): RedirectResponse
    {
        $this->authorizeTeacher($request, $module);
        abort_if(today()->lt($module->end_date), 409, 'El módulo todavía no finalizó.');
        $this->completion->complete($request->user(), $module);

        return redirect()->route('teacher.modules.index')->with('success', 'Curso cerrado y resultados académicos registrados correctamente.');
    }

    private function authorizeTeacher(Request $request, AcademicModule $module): void
    {
        abort_unless($request->user()->personnel_id !== null, 403);
        abort_unless($module->currentTeacherAssignment()->where('personnel_id', $request->user()->personnel_id)->exists(), 403);
    }
}
