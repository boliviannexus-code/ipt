<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\UpdateProgramGradingConfigurationRequest;
use App\Models\Program;
use App\Models\ProgramGradingScheme;
use App\Services\Academic\ProgramGradingConfigurationService;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AcademicControlController extends Controller
{
    public function __construct(private readonly ProgramGradingConfigurationService $configurations) {}

    public function index(): View
    {
        return view('academic-control.index', [
            'programs' => Program::query()->withCount(['levels', 'academicModules'])->orderBy('title')->paginate(15),
        ]);
    }

    public function schemesIndex(): View
    {
        return view('academic-control.schemes', [
            'schemes' => ProgramGradingScheme::query()->withExists(['levels', 'classSessions', 'studentResults'])->orderBy('name')->get(),
        ]);
    }

    public function editAssignments(Program $program): View
    {
        $program->load('levels.gradingScheme');

        return view('academic-control.assignments', [
            'program' => $program,
            'schemes' => ProgramGradingScheme::query()->where('is_active', true)->where('status', 'finalized')->orderBy('name')->get(),
        ]);
    }

    public function show(ProgramGradingScheme $scheme): View
    {
        $scheme->load('components.skills')->loadExists(['levels', 'classSessions', 'studentResults']);

        return view('academic-control.show', compact('scheme'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'source_scheme_id' => ['nullable', 'integer'],
        ]);
        $companyId = CompanyContext::id();
        abort_unless($companyId && $companyId > 0, 403);
        $source = isset($data['source_scheme_id'])
            ? ProgramGradingScheme::query()->where('status', 'finalized')->findOrFail($data['source_scheme_id']) : null;
        $scheme = $this->configurations->createIndependent($data['name'], $companyId, $source);

        return redirect()->route('academic.weightings.show', $scheme)->with('success', 'Ponderación creada como borrador independiente.');
    }

    public function update(UpdateProgramGradingConfigurationRequest $request, ProgramGradingScheme $scheme): RedirectResponse
    {
        $this->configurations->update($scheme, $request->validated());

        return redirect()->route('academic.weightings.show', $scheme)->with('success', 'Borrador actualizado correctamente.');
    }

    public function rename(Request $request, ProgramGradingScheme $scheme): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150']]);
        $this->configurations->rename($scheme, $data['name']);

        return back()->with('success', 'Nombre de la ponderación actualizado correctamente.');
    }

    public function finalize(Request $request, ProgramGradingScheme $scheme): RedirectResponse
    {
        $this->configurations->finalize($scheme, $request->user());

        return redirect()->route('academic.weightings.show', $scheme)->with('success', 'Ponderación finalizada y activada correctamente.');
    }

    public function destroyVersion(ProgramGradingScheme $scheme): RedirectResponse
    {
        $this->configurations->delete($scheme);

        return redirect()->route('academic.weightings.index')->with('success', 'Ponderación eliminada correctamente.');
    }

    public function assign(Request $request, Program $program): RedirectResponse
    {
        $data = $request->validate([
            'levels' => ['required', 'array'],
            'levels.*.id' => ['required', 'integer', 'distinct'],
            'levels.*.scheme_id' => ['nullable', 'integer'],
        ]);
        DB::transaction(function () use ($data, $program): void {
            foreach ($data['levels'] as $assignment) {
                $level = $program->levels()->lockForUpdate()->findOrFail($assignment['id']);
                $scheme = filled($assignment['scheme_id'] ?? null)
                    ? ProgramGradingScheme::query()->where('company_id', $program->company_id)->findOrFail($assignment['scheme_id']) : null;
                if ($scheme && (! $scheme->is_active || ! $scheme->isFinalized())) {
                    throw ValidationException::withMessages(['levels' => 'Solo puedes vincular ponderaciones finalizadas y activas.']);
                }
                $level->update(['program_grading_scheme_id' => $scheme?->id]);
            }
        });

        return redirect()->route('academic.control.levels.edit', $program)->with('success', 'Ponderaciones vinculadas a los niveles del programa.');
    }
}
