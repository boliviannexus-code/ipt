<?php

namespace App\Http\Controllers\Web;

use App\Enums\GradingFrequency;
use App\Enums\GradingScoringMethod;
use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\ClassSession;
use App\Models\EnrollmentContract;
use App\Models\Personnel;
use App\Models\ProgramGradingScheme;
use App\Services\Academic\AttendanceGradingPolicy;
use App\Services\Academic\ClassSessionService;
use App\Services\Academic\DailyGradeService;
use App\Services\Academic\StudentAttendanceRegularityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherModuleController extends Controller
{
    public function __construct(private readonly DailyGradeService $dailyGrades, private readonly ClassSessionService $sessions, private readonly StudentAttendanceRegularityService $regularity) {}

    public function index(Request $request): View
    {
        $personnel = $request->user()->personnel;

        if (! $personnel instanceof Personnel) {
            return view('teacher.modules.index', [
                'modules' => collect(),
                'teacherUnavailableMessage' => 'Tu cuenta no está vinculada a un docente de la empresa activa. Solicita a un administrador que revise tu asignación de personal.',
            ]);
        }

        $modules = AcademicModule::query()
            ->with(['program', 'level', 'classSessions' => fn ($query) => $query->where(fn ($query) => $query->whereDate('class_date', today())->orWhereNull('ended_at'))->orderBy('class_date')])
            ->withCount(['studentAssignments' => fn ($query) => $query->whereNull('released_at')])
            ->whereNull('closed_at')->whereDoesntHave('studentResults')
            ->whereHas('currentTeacherAssignment', fn ($query) => $query->where('personnel_id', $personnel->id))
            ->orderBy('starts_at')
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();

        return view('teacher.modules.index', compact('modules'));
    }

    public function history(Request $request): View
    {
        $personnel = $request->user()->personnel;
        $modules = AcademicModule::query()
            ->with(['program', 'level'])
            ->withCount(['studentAssignments', 'classSessions'])
            ->where(fn (Builder $query) => $query->whereNotNull('closed_at')->orWhereHas('studentResults'))
            ->whereHas('currentTeacherAssignment', fn (Builder $query) => $query->where('personnel_id', $personnel?->id))
            ->when(! $personnel instanceof Personnel, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->orderByRaw('COALESCE(closed_at, end_date) DESC')->orderByDesc('id')
            ->paginate(15);

        return view('teacher.modules.history', [
            'modules' => $modules,
            'teacherUnavailableMessage' => $personnel instanceof Personnel ? null : 'Tu cuenta no está vinculada a un docente de la empresa activa. Solicita a un administrador que revise tu asignación de personal.',
        ]);
    }

    public function start(Request $request, AcademicModule $module): RedirectResponse
    {
        $personnel = $this->authorizedTeacher($request, $module);
        $data = $request->validate(['class_date' => ['sometimes', 'required', 'date_format:Y-m-d']], [
            'class_date.required' => 'Selecciona la fecha de la clase.',
            'class_date.date_format' => 'Selecciona una fecha válida para la clase.',
        ]);
        $session = $this->sessions->start($module, $personnel, $request->user(), $data['class_date'] ?? today()->toDateString());

        return redirect()->route('teacher.modules.attendance.edit', [$module, $session])->with('success', 'Clase iniciada. Ya puede realizar el registro diario.');
    }

    public function editAttendance(Request $request, AcademicModule $module, ClassSession $session): View
    {
        $this->authorizedTeacher($request, $module);
        abort_unless($session->academic_module_id === $module->id, 404);
        if ($session->ended_at !== null) {
            throw ValidationException::withMessages(['class' => 'Esta clase ya fue finalizada y no puede modificarse.']);
        }
        $module->load(['program', 'level', 'studentAssignments' => fn ($query) => $query->whereNull('released_at')->with('student')]);
        $session->load('gradingSkills.grades');
        $session->load('academicObservations');
        $scheme = $this->sessionScheme($session, $module);
        $dailyComponents = $scheme?->components ?? collect();
        $dailySkills = $dailyComponents->flatMap(fn ($component) => $component->skills->each(fn ($skill) => $skill->setRelation('component', $component)));
        $attendanceSkills = AttendanceGradingPolicy::skills($scheme);
        $dailyComponents = $dailyComponents->reject(fn ($component) => mb_strtolower(trim($component->name)) === 'attendance')->values();
        $selectedSkillIds = $session->gradingSkills->pluck('program_grading_skill_id')->merge($attendanceSkills->pluck('id'))->unique()->map(fn ($id): int => (int) $id);
        $dailyGradesBySkill = $session->gradingSkills->mapWithKeys(fn ($selection): array => [
            $selection->program_grading_skill_id => $selection->grades->pluck('score', 'student_id'),
        ]);
        $observationsByStudent = $session->academicObservations->pluck('observation', 'student_id');
        $inactiveStudentIds = EnrollmentContract::query()->where('program_id', $module->program_id)
            ->where('status', 'inactive')->whereIn('student_id', $module->studentAssignments->pluck('student_id'))->pluck('student_id');
        $absenceCounts = $this->regularity->absenceCounts($module);

        return view('teacher.modules.attendance', compact('module', 'session', 'dailyComponents', 'dailySkills', 'selectedSkillIds', 'dailyGradesBySkill', 'observationsByStudent', 'attendanceSkills', 'inactiveStudentIds', 'absenceCounts'));
    }

    public function updateAttendance(Request $request, AcademicModule $module, ClassSession $session): RedirectResponse
    {
        $this->authorizedTeacher($request, $module);
        abort_unless($session->academic_module_id === $module->id, 404);
        if ($session->ended_at !== null) {
            throw ValidationException::withMessages(['class' => 'Esta clase ya fue finalizada y no puede modificarse.']);
        }
        $request->validate([
            'selected_skills' => ['nullable', 'array'],
            'selected_skills.*' => ['required', 'integer', 'distinct'],
            'grades' => ['nullable', 'array'],
            'observations' => ['nullable', 'array'],
            'observations.*' => ['nullable', 'string', 'max:1000'],
        ]);
        $studentIds = $module->studentAssignments()->whereNull('released_at')->pluck('student_id')->map(fn ($id) => (int) $id);
        $observationStudentIds = collect(array_keys($request->input('observations', [])))->map(fn ($id): int => (int) $id);
        if ($observationStudentIds->diff($studentIds)->isNotEmpty()) {
            throw ValidationException::withMessages(['observations' => 'Una de las observaciones no pertenece a un estudiante del módulo.']);
        }

        $selectedSkillIds = collect($request->input('selected_skills', []))->map(fn ($id): int => (int) $id)->unique()->values();
        $scheme = $this->sessionScheme($session, $module);
        $selectedSkillIds = $selectedSkillIds->merge(AttendanceGradingPolicy::skills($scheme)->pluck('id'))->unique()->values();
        $allowedSkills = $scheme?->components->flatMap(fn ($component) => $component->skills->each(fn ($skill) => $skill->setRelation('component', $component)))->keyBy('id') ?? collect();
        if ($selectedSkillIds->diff($allowedSkills->keys()->map(fn ($id): int => (int) $id))->isNotEmpty()) {
            throw ValidationException::withMessages(['selected_skills' => 'Una de las habilidades seleccionadas no pertenece a la configuración diaria de este módulo.']);
        }

        $grades = [];
        foreach ($selectedSkillIds as $skillId) {
            $skill = $allowedSkills->get($skillId);
            $isSimple = $skill->component->scoring_method === GradingScoringMethod::Simple;
            $validatedGrades = $request->validate([
                "grades.{$skillId}" => ['required', 'array'],
                "grades.{$skillId}.*" => $isSimple
                    ? ['required', 'integer', 'in:0,1']
                    : ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            ])['grades'][$skillId];
            $gradeStudentIds = collect(array_keys($validatedGrades))->map(fn ($id): int => (int) $id);
            if ($gradeStudentIds->diff($studentIds)->isNotEmpty() || $studentIds->diff($gradeStudentIds)->isNotEmpty()) {
                throw ValidationException::withMessages(["grades.{$skillId}" => 'Debes registrar esta habilidad para todos los estudiantes del módulo.']);
            }
            $grades[$skillId] = collect($validatedGrades)->map(
                fn ($score) => $isSimple ? ((int) $score === 1 ? 100 : 0) : $score,
            )->all();
        }

        DB::transaction(function () use ($grades, $request, $selectedSkillIds, $session, $studentIds, $module): void {
            AcademicModule::query()->whereKey($module->id)->lockForUpdate()->firstOrFail();
            $session = ClassSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($session->ended_at !== null) {
                throw ValidationException::withMessages(['class' => 'Esta clase ya fue finalizada.']);
            }
            $this->dailyGrades->save($session, $request->user(), $selectedSkillIds->all(), $grades);
            $this->dailyGrades->saveObservations($session, $request->user(), $studentIds->all(), $request->input('observations', []));
            $session->update(['ended_at' => now(), 'finalized_by' => $request->user()->id]);
            $this->regularity->evaluate($module);
        });

        return redirect()->route('teacher.modules.index')->with('success', 'Clase finalizada y registro académico guardado correctamente.');
    }

    public function autosaveDailyRecord(Request $request, AcademicModule $module, ClassSession $session): JsonResponse
    {
        $this->authorizedTeacher($request, $module);
        abort_unless($session->academic_module_id === $module->id, 404);
        if ($session->ended_at !== null) {
            return response()->json(['message' => 'Esta clase ya fue finalizada.'], 409);
        }

        $data = $request->validate([
            'type' => ['required', 'in:grade,observation'],
            'student_id' => ['required', 'integer'],
            'skill_id' => ['required_if:type,grade', 'nullable', 'integer'],
            'score' => ['required_if:type,grade', 'nullable'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ]);
        $studentId = (int) $data['student_id'];
        abort_unless($module->studentAssignments()->whereNull('released_at')->where('student_id', $studentId)->exists(), 404);

        if ($data['type'] === 'observation') {
            $this->dailyGrades->saveObservation($session, $request->user(), $studentId, $data['observation'] ?? null);

            return response()->json(['message' => 'Observación guardada.']);
        }

        $skillId = (int) $data['skill_id'];
        $skill = $this->sessionScheme($session, $module)?->components->flatMap(
            fn ($component) => $component->skills->each(fn ($item) => $item->setRelation('component', $component)),
        )->firstWhere('id', $skillId);
        abort_unless($skill !== null, 404);
        $isSimple = $skill->component->scoring_method === GradingScoringMethod::Simple;
        $validated = validator(['score' => $data['score']], [
            'score' => $isSimple ? ['required', 'integer', 'in:0,1'] : ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
        ])->validate();
        $score = $isSimple ? ((int) $validated['score'] === 1 ? 100.0 : 0.0) : (float) $validated['score'];
        $this->dailyGrades->saveGrade($session, $request->user(), $skillId, $studentId, $score);

        return response()->json(['score' => $score]);
    }

    private function personnel(Request $request): Personnel
    {
        $personnel = $request->user()->personnel;
        abort_unless($personnel instanceof Personnel, 403, 'Tu cuenta no está vinculada a un docente de la empresa activa.');

        return $personnel;
    }

    private function authorizedTeacher(Request $request, AcademicModule $module): Personnel
    {
        $personnel = $this->personnel($request);
        abort_unless($module->currentTeacherAssignment()->where('personnel_id', $personnel->id)->exists(), 403);

        return $personnel;
    }

    private function sessionScheme(ClassSession $session, AcademicModule $module): ?ProgramGradingScheme
    {
        $scheme = $session->gradingScheme()->with(['components' => fn ($query) => $query->where('frequency', GradingFrequency::Daily->value)->with('skills')])->first();

        $scheme ??= $module->configuredGradingScheme();
        if ($scheme) {
            $scheme->setRelation('components', $scheme->components->where('frequency', GradingFrequency::Daily)->values());
        }

        return $scheme;
    }
}
