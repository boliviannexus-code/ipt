<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\AcademicModuleStudentResult;
use App\Models\AcademicSecondOpportunity;
use App\Models\Program;
use App\Services\Academic\SecondOpportunityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class SecondOpportunityController extends Controller
{
    public function __construct(private readonly SecondOpportunityService $opportunities) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'program_id' => ['nullable', 'integer'], 'module_id' => ['nullable', 'integer'],
        ]);
        $apply = function ($query) use ($filters): void {
            $query->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas('student', fn ($query) => $query
                ->where(fn ($query) => $query->where('first_name', 'ilike', '%'.$search.'%')
                    ->orWhere('paternal_surname', 'ilike', '%'.$search.'%')->orWhere('maternal_surname', 'ilike', '%'.$search.'%')
                    ->orWhere('identity_document', 'ilike', '%'.$search.'%')
                    ->orWhereRaw("concat_ws(' ', first_name, paternal_surname, maternal_surname) ilike ?", ['%'.$search.'%']))))
                ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->whereHas('module', fn ($query) => $query->where('program_id', $id)))
                ->when($filters['module_id'] ?? null, fn ($query, $id) => $query->where('academic_module_id', $id));
        };
        $query = $this->opportunities->candidates();
        $apply($query);
        $candidates = $query->with(['student', 'module.program', 'module.level'])->latest('finalized_at')->paginate(15, ['*'], 'candidates_page')->withQueryString();
        $history = AcademicSecondOpportunity::query()->whereHas('result', $apply)
            ->with(['result.student', 'result.module.program', 'recordedBy'])->latest('recorded_at')
            ->paginate(15, ['*'], 'history_page')->withQueryString();

        return view('academic-second-opportunities.index', [
            'candidates' => $candidates, 'history' => $history,
            'programs' => Program::query()->orderBy('title')->get(),
            'modules' => AcademicModule::query()->whereNotNull('closed_at')->orderBy('name')->get(),
        ]);
    }

    public function create(AcademicModuleStudentResult $result): View|RedirectResponse
    {
        try {
            return view('academic-second-opportunities.form', $this->opportunities->context($result));
        } catch (ValidationException $exception) {
            if (request()->expectsJson()) {
                throw $exception;
            }

            return redirect()->route('academic.second-opportunities.index')->withErrors($exception->errors());
        }
    }

    public function preview(Request $request, AcademicModuleStudentResult $result): View|RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $context = $this->opportunities->context($result);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            return redirect()->route('academic.second-opportunities.index')->withErrors($exception->errors());
        }

        if ($request->boolean('back')) {
            return view('academic-second-opportunities.form', [...$context, 'data' => $data]);
        }

        return view('academic-second-opportunities.confirm', [...$context, 'data' => $data]);
    }

    public function store(Request $request, AcademicModuleStudentResult $result): RedirectResponse
    {
        try {
            $this->opportunities->record($request->user(), $result, $this->validated($request));
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            return redirect()->route('academic.second-opportunities.index')->withErrors($exception->errors());
        }

        return redirect()->route('academic.second-opportunities.index')->with('success', 'Nota 2T registrada. El resultado y nivel del estudiante fueron actualizados.');
    }

    private function validated(Request $request): array
    {
        if (is_string($request->input('reason'))) {
            $request->merge(['reason' => trim($request->input('reason'))]);
        }

        return $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
    }
}
