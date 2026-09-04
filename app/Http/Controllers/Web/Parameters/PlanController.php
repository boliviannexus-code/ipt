<?php

namespace App\Http\Controllers\Web\Parameters;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Program;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('parameters.plans.index', [
            'programs' => Program::query()->withCount('plans')->orderBy('title')->paginate(15),
        ]);
    }

    public function show(Program $program): View
    {
        return view('parameters.plans.show', [
            'program' => $program,
            'plans' => $program->plans()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(Request $request, Program $program): View
    {
        if ($request->ajax()) {
            return view('parameters.plans.partials.create-form', compact('program'));
        }

        return view('parameters.plans.create', compact('program'));
    }

    public function store(Request $request, Program $program): JsonResponse|RedirectResponse
    {
        $companyId = CompanyContext::id($request->user());
        abort_unless($companyId !== null && $companyId > 0, 403);

        abort_unless((int) $program->company_id === (int) $companyId, 404);
        $data = $this->validated($request, $companyId);

        $plan = Plan::create([...$data, 'company_id' => $companyId]);
        $program->plans()->attach($plan);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Plan creado correctamente.', 'data' => ['id' => $plan->id]]);
        }

        return redirect()->route('parameters.plans.show', $program)->with('success', 'Plan creado correctamente.');
    }

    public function edit(Request $request, Program $program, Plan $plan): View
    {
        $this->ensurePlanBelongsToProgram($program, $plan);

        if ($request->ajax()) {
            return view('parameters.plans.partials.edit-form', compact('program', 'plan'));
        }

        return view('parameters.plans.edit', compact('program', 'plan'));
    }

    public function update(Request $request, Program $program, Plan $plan): JsonResponse|RedirectResponse
    {
        $this->ensurePlanBelongsToProgram($program, $plan);
        $data = $this->validated($request, (int) $plan->company_id, $plan);
        $plan->update($data);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Plan actualizado correctamente.', 'data' => ['id' => $plan->id]]);
        }

        return redirect()->route('parameters.plans.show', $program)->with('success', 'Plan actualizado correctamente.');
    }

    private function validated(Request $request, int $companyId, ?Plan $plan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('plans')->where('company_id', $companyId)->ignore($plan)],
            'monthly_cost' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
        ], [
            'name.unique' => 'Ya existe un plan con este nombre en la empresa activa.',
            'monthly_cost.decimal' => 'El costo mensual debe tener como máximo dos decimales.',
        ]);
    }

    private function ensurePlanBelongsToProgram(Program $program, Plan $plan): void
    {
        abort_unless(
            (int) $program->company_id === (int) $plan->company_id
            && $program->plans()->whereKey($plan->id)->exists(),
            404,
        );
    }
}
