<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\Personnel;
use App\Models\ProgramGradingScheme;
use App\Services\Academic\TeacherTrackingPdfService;
use App\Services\Academic\TeacherTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class TeacherTrackingController extends Controller
{
    public function __construct(private readonly TeacherTrackingService $tracking) {}

    public function index(Request $request): View
    {
        $personnel = $request->user()->personnel;
        $modules = $personnel instanceof Personnel
            ? AcademicModule::query()
                ->with(['program', 'level'])
                ->withCount(['studentAssignments', 'classSessions'])
                ->whereHas('currentTeacherAssignment', fn ($query) => $query->where('personnel_id', $personnel->id))
                ->orderByDesc('start_date')
                ->get()
            : collect();

        return view('teacher.tracking.index', [
            'modules' => $modules,
            'teacherUnavailableMessage' => $personnel instanceof Personnel ? null : 'Tu cuenta no está vinculada a un docente de la empresa activa.',
        ]);
    }

    public function show(Request $request, AcademicModule $module): View
    {
        return view('teacher.tracking.show', $this->reportData($request, $module));
    }

    public function print(Request $request, AcademicModule $module, TeacherTrackingPdfService $pdf): Response
    {
        return response($pdf->render($this->reportData($request, $module)), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="centralizador-notas-'.$module->id.'.pdf"',
        ]);
    }

    private function reportData(Request $request, AcademicModule $module): array
    {
        $this->authorizeTeacher($request, $module);
        $module->load([
            'program',
            'level',
            'studentAssignments.student',
            'singleGrades.skill.component',
            'classSessions' => fn ($query) => $query->orderBy('class_date')->with([
                'gradingSkills.skill.component',
                'gradingSkills.grades',
                'academicObservations.student',
            ]),
        ]);
        $schemeId = $module->singleGrades->first()?->skill?->component?->program_grading_scheme_id
            ?? $module->classSessions->sortByDesc('class_date')->first()?->program_grading_scheme_id;
        $scheme = $schemeId
            ? ProgramGradingScheme::query()->with('components.skills')->find($schemeId)
            : $module->configuredGradingScheme();
        $summary = $this->tracking->summarize($module, $scheme);

        return [...compact('module', 'scheme'), ...$summary];
    }

    private function authorizeTeacher(Request $request, AcademicModule $module): void
    {
        abort_unless($request->user()->personnel_id !== null, 403, 'Tu cuenta no está vinculada a un docente.');
        abort_unless($module->currentTeacherAssignment()->where('personnel_id', $request->user()->personnel_id)->exists(), 403);
    }
}
