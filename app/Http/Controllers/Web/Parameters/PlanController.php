<?php

namespace App\Http\Controllers\Web\Parameters;

use App\Http\Controllers\Controller;
use App\Models\Plan;
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
            'plans' => Plan::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        if ($request->ajax()) {
            return view('parameters.plans.partials.create-form');
        }

        return view('parameters.plans.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $companyId = CompanyContext::id($request->user());
        abort_unless($companyId !== null && $companyId > 0, 403);

        $data = $this->validated($request, $companyId);

        $plan = Plan::create([...$data, 'company_id' => $companyId]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Plan creado correctamente.', 'data' => ['id' => $plan->id]]);
        }

        return redirect()->route('parameters.plans.index')->with('success', 'Plan creado correctamente.');
    }

    public function edit(Request $request, Plan $plan): View
    {
        if ($request->ajax()) {
            return view('parameters.plans.partials.edit-form', compact('plan'));
        }

        return view('parameters.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request, (int) $plan->company_id, $plan);
        $plan->update($data);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Plan actualizado correctamente.', 'data' => ['id' => $plan->id]]);
        }

        return redirect()->route('parameters.plans.index')->with('success', 'Plan actualizado correctamente.');
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
}
