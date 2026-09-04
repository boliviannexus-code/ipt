<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\GradingFrequency;
use App\Enums\GradingScoringMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\UpdateSingleGradesRequest;
use App\Models\AcademicModule;
use App\Models\ProgramGradingScheme;
use App\Services\Academic\SingleGradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TeacherSingleGradeController extends Controller
{
    public function __construct(private readonly SingleGradeService $singleGrades) {}

    public function edit(Request $request, AcademicModule $module): View
    {
        $this->authorizeTeacher($request, $module);
        $module->load(['program', 'level', 'studentAssignments.student', 'singleGrades.skill.component']);
        $scheme = $this->schemeFor($module);
        $components = $scheme?->components ?? collect();
        $gradesBySkill = $module->singleGrades
            ->whereIn('program_grading_skill_id', $components->flatMap->skills->pluck('id'))
            ->groupBy('program_grading_skill_id')
            ->map(fn ($grades) => $grades->pluck('score', 'student_id'));

        return view('teacher.modules.single-grades', compact('module', 'scheme', 'components', 'gradesBySkill'));
    }

    public function update(UpdateSingleGradesRequest $request, AcademicModule $module): RedirectResponse
    {
        $this->authorizeTeacher($request, $module);
        $module->load('singleGrades.skill.component');
        $scheme = $this->schemeFor($module);
        $components = $scheme?->components ?? collect();
        $skills = $components->flatMap->skills->keyBy('id');
        if ($skills->isEmpty()) {
            throw ValidationException::withMessages(['grades' => 'El programa no tiene ponderaciones únicas configuradas.']);
        }

        $studentIds = $module->studentAssignments()->pluck('student_id')->map(fn ($id): int => (int) $id)->sort()->values();
        $submitted = collect($request->validated('grades'));
        $submittedSkillIds = $submitted->keys()->map(fn ($id): int => (int) $id)->sort()->values();
        if ($submittedSkillIds->all() !== $skills->keys()->map(fn ($id): int => (int) $id)->sort()->values()->all()) {
            throw ValidationException::withMessages(['grades' => 'Las habilidades enviadas no coinciden con la configuración vigente.']);
        }

        $grades = [];
        foreach ($skills as $skillId => $skill) {
            $skillGrades = collect($submitted->get((string) $skillId, $submitted->get($skillId, [])));
            $submittedStudentIds = $skillGrades->keys()->map(fn ($id): int => (int) $id)->sort()->values();
            if ($submittedStudentIds->all() !== $studentIds->all()) {
                throw ValidationException::withMessages(["grades.{$skillId}" => 'Debes calificar a todos los estudiantes del módulo.']);
            }

            $isSimple = $skill->component->scoring_method === GradingScoringMethod::Simple;
            $validated = validator(['grades' => [$skillId => $skillGrades->all()]], [
                "grades.{$skillId}.*" => $isSimple
                    ? ['required', 'integer', 'in:0,1']
                    : ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            ])->validate()['grades'][$skillId];
            $grades[$skillId] = collect($validated)->map(
                fn ($score): float => $isSimple ? ((int) $score === 1 ? 100.0 : 0.0) : (float) $score,
            )->all();
        }

        $this->singleGrades->save($module, $request->user(), $grades);

        return redirect()->route('teacher.modules.single-grades.edit', $module)->with('success', 'Ponderaciones únicas guardadas correctamente.');
    }

    private function schemeFor(AcademicModule $module): ?ProgramGradingScheme
    {
        $schemeId = $module->singleGrades->first()?->skill?->component?->program_grading_scheme_id
            ?? $module->classSessions()->whereNotNull('program_grading_scheme_id')->latest('class_date')->value('program_grading_scheme_id');

        return ProgramGradingScheme::query()
            ->with(['components' => fn ($query) => $query->where('frequency', GradingFrequency::Single->value)->with('skills')])
            ->when($schemeId, fn ($query) => $query->whereKey($schemeId), fn ($query) => $query->where('program_id', $module->program_id)->where('is_active', true))
            ->first();
    }

    private function authorizeTeacher(Request $request, AcademicModule $module): void
    {
        abort_unless($request->user()->personnel_id !== null, 403, 'Tu cuenta no está vinculada a un docente.');
        abort_unless($module->currentTeacherAssignment()->where('personnel_id', $request->user()->personnel_id)->exists(), 403);
    }
}
