<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PlacementExam;
use App\Models\ProgramLevel;
use App\Models\Student;
use App\Services\Academic\PlacementExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlacementExamController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['q'] ?? '');
        $students = Student::query()->with(['campus', 'contracts' => fn ($query) => $query->where('status', 'enrolled')->with('program')])
            ->where('is_active', true)->whereHas('contracts', fn ($query) => $query->where('status', 'enrolled'))
            ->whereDoesntHave('moduleAssignments', fn ($query) => $query->whereNull('released_at'))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('identity_document', 'ilike', "%{$search}%")->orWhere('account_number', 'ilike', "%{$search}%")
                    ->orWhereRaw("concat_ws(' ', first_name, paternal_surname, maternal_surname) ilike ?", ["%{$search}%"]);
            }))->orderBy('first_name')->orderBy('paternal_surname')->paginate(15)->withQueryString();

        return view('placement-exams.index', compact('students', 'search'));
    }

    public function history(): View
    {
        $exams = PlacementExam::query()->with(['student', 'program', 'teacher', 'recorder', 'levels', 'contract'])
            ->latest('examined_at')->latest('id')->paginate(20);

        return view('placement-exams.history', compact('exams'));
    }

    public function create(Request $request, Student $student, PlacementExamService $service): View
    {
        abort_unless($student->is_active && $student->contracts()->where('status', 'enrolled')->exists(), 404);
        $data = $request->validate([
            'enrollment_contract_id' => ['nullable', 'integer'],
            'target_program_level_id' => ['nullable', 'integer'],
        ]);
        $contracts = $student->contracts()->where('status', 'enrolled')->with(['program', 'currentProgramLevel'])->get();
        $contractId = old('enrollment_contract_id', $data['enrollment_contract_id'] ?? ($contracts->count() === 1 ? $contracts->first()->id : null));
        $contract = $contractId ? $contracts->firstWhere('id', (int) $contractId) : null;
        abort_if($contractId && ! $contract, 404);
        $current = $contract ? $service->currentLevel($contract) : null;
        $levels = $contract && $current ? ProgramLevel::query()->where('program_id', $contract->program_id)
            ->where('is_active', true)->where('position', '>', $current->position)->orderBy('position')->get() : collect();
        $targetId = old('target_program_level_id', $data['target_program_level_id'] ?? null);
        $target = $targetId ? $levels->firstWhere('id', (int) $targetId) : null;
        $preview = $target ? $service->preview($student, $contract, $target) : collect();
        $teachers = $service->teachers((int) $student->company_id);
        $hasModule = $student->moduleAssignments()->whereNull('released_at')->exists();
        $submissionToken = old('submission_token', (string) Str::uuid());

        return view('placement-exams.create', compact('student', 'contracts', 'contract', 'current', 'levels', 'target', 'preview', 'teachers', 'hasModule', 'submissionToken'));
    }

    public function store(Request $request, Student $student, PlacementExamService $service): RedirectResponse
    {
        $data = $request->validate([
            'enrollment_contract_id' => ['required', 'integer'],
            'expected_current_level_id' => ['nullable', 'integer'],
            'target_program_level_id' => ['required', 'integer'],
            'score' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'teacher_id' => ['required', 'integer'],
            'observation' => ['required', 'string', 'max:2000'],
            'submission_token' => ['required', 'uuid'],
        ]);
        $service->record($request->user(), $student, $data);

        return redirect()->route('placement-exams.history')->with('success', 'Examen de nivel registrado. El nivel fue actualizado y el estudiante queda pendiente de asignación a un módulo.');
    }
}
