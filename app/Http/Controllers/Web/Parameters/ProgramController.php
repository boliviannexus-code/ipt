<?php

namespace App\Http\Controllers\Web\Parameters;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Program;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        return view('parameters.programs.index', [
            'programs' => Program::query()->with('plans')->withCount('levels')->orderBy('title')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('parameters.programs.create', ['plans' => Plan::query()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = (int) CompanyContext::id($request->user());
        $data = $this->validated($request, $companyId);
        $program = Program::create([...$data, 'company_id' => $companyId]);
        $program->plans()->sync($data['plan_ids']);

        return redirect()->route('parameters.programs.index')->with('success', 'Programa creado correctamente.');
    }

    public function edit(Program $program): View
    {
        return view('parameters.programs.edit', [
            'program' => $program->load('plans'),
            'plans' => Plan::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $data = $this->validated($request, (int) $program->company_id, $program);
        $program->update($data);
        $program->plans()->sync($data['plan_ids']);

        return redirect()->route('parameters.programs.index')->with('success', 'Programa actualizado correctamente.');
    }

    private function validated(Request $request, int $companyId, ?Program $program = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180', Rule::unique('programs')->where('company_id', $companyId)->ignore($program)],
            'duration_months' => ['required', 'integer', 'min:1', 'max:600'],
            'plan_ids' => ['required', 'array', 'min:1'],
            'plan_ids.*' => ['integer', Rule::exists('plans', 'id')->where('company_id', $companyId)],
        ], ['plan_ids.required' => 'Selecciona al menos un plan.']);
    }
}
