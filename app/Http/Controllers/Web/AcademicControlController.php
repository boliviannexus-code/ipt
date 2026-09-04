<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\UpdateProgramGradingConfigurationRequest;
use App\Models\Program;
use App\Models\ProgramGradingScheme;
use App\Services\Academic\ProgramGradingConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class AcademicControlController extends Controller
{
    public function __construct(private readonly ProgramGradingConfigurationService $configurations) {}

    public function index(): View
    {
        return view('academic-control.index', [
            'programs' => Program::query()
                ->with('gradingScheme')
                ->withCount(['levels', 'academicModules'])
                ->orderBy('title')
                ->paginate(15),
            'availableSources' => $this->availableSources(),
        ]);
    }

    public function show(Request $request, Program $program): View
    {
        $program->loadCount(['levels', 'academicModules']);
        $versions = $program->gradingSchemes()->with('finalizedBy')->get();
        $selectedVersion = $request->integer('version');
        $scheme = $versions->firstWhere('version', $selectedVersion)
            ?? $versions->firstWhere('status', 'draft')
            ?? $versions->firstWhere('is_active', true)
            ?? $versions->first();
        abort_unless($scheme, 404);
        $scheme->load('components.skills');

        return view('academic-control.show', [
            'program' => $program,
            'scheme' => $scheme,
            'versions' => $versions,
            'availableSources' => $this->availableSources(),
        ]);
    }

    public function update(UpdateProgramGradingConfigurationRequest $request, Program $program, ProgramGradingScheme $scheme): RedirectResponse
    {
        abort_unless((int) $scheme->program_id === $program->id, 404);
        $this->configurations->update($scheme, $request->validated());

        return redirect()->route('academic.control.show', ['program' => $program, 'version' => $scheme->version])->with('success', 'Borrador actualizado correctamente.');
    }

    public function finalize(Request $request, Program $program, ProgramGradingScheme $scheme): RedirectResponse
    {
        abort_unless((int) $scheme->program_id === $program->id, 404);
        $this->configurations->finalize($scheme, $request->user());

        return redirect()->route('academic.control.show', ['program' => $program, 'version' => $scheme->version])->with('success', 'Versión finalizada y activada correctamente.');
    }

    public function createVersion(Request $request, Program $program): RedirectResponse
    {
        $data = $request->validate([
            'source_scheme_id' => ['nullable', 'integer'],
        ]);
        $createEmpty = $request->exists('source_scheme_id') && blank($request->input('source_scheme_id'));
        $source = isset($data['source_scheme_id'])
            ? ProgramGradingScheme::query()->where('status', 'finalized')->findOrFail($data['source_scheme_id'])
            : null;
        $scheme = $this->configurations->createNextVersion($program, $source, $createEmpty);

        return redirect()->route('academic.control.show', ['program' => $program, 'version' => $scheme->version])->with('success', $source
            ? "Ponderaciones copiadas desde {$source->program->title}, versión {$source->version}. El nuevo borrador ya puede editarse."
            : 'Nueva configuración creada como borrador vacío.');
    }

    public function destroyVersion(Program $program, ProgramGradingScheme $scheme): RedirectResponse
    {
        abort_unless((int) $scheme->program_id === $program->id, 404);
        $this->configurations->deleteDraft($scheme);
        $remainingScheme = $program->gradingSchemes()->first();

        return $remainingScheme
            ? redirect()->route('academic.control.show', ['program' => $program, 'version' => $remainingScheme->version])->with('success', 'Borrador eliminado correctamente.')
            : redirect()->route('academic.control.index')->with('success', 'Borrador eliminado. El programa quedó sin ponderaciones configuradas.');
    }

    private function availableSources(): Collection
    {
        return ProgramGradingScheme::query()
            ->where('status', 'finalized')
            ->with('program')
            ->get()
            ->sortBy(fn (ProgramGradingScheme $scheme): string => mb_strtolower($scheme->program->title).'|'.str_pad((string) (999999 - $scheme->version), 6, '0', STR_PAD_LEFT))
            ->values();
    }
}
