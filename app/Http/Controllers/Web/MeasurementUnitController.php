<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeasurementUnitRequest;
use App\Http\Requests\UpdateMeasurementUnitRequest;
use App\Models\MeasurementUnit;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeasurementUnitController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('measurement-units.view'), 403);

        return view('measurement-units.index');
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()?->can('measurement-units.create'), 403);

        if ($request->ajax()) {
            return view('measurement-units.partials.create-form');
        }

        return view('measurement-units.create');
    }

    public function store(StoreMeasurementUnitRequest $request): JsonResponse|RedirectResponse
    {
        $unit = MeasurementUnit::query()->create(CompanyContext::applyToData($request->validated(), $request->user()));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Unidad de medida creada correctamente.',
                'data' => ['id' => $unit->id],
            ], 201);
        }

        return redirect()->route('measurement-units.index')->with('success', 'Unidad de medida creada correctamente.');
    }

    public function show(Request $request, MeasurementUnit $measurementUnit): View
    {
        abort_unless(auth()->user()?->can('measurement-units.view'), 403);
        abort_unless(CompanyContext::belongsToUser($measurementUnit->company_id, $request->user()), 403);

        if ($request->ajax()) {
            return view('measurement-units.partials.show', compact('measurementUnit'));
        }

        return view('measurement-units.show', compact('measurementUnit'));
    }

    public function edit(Request $request, MeasurementUnit $measurementUnit): View
    {
        abort_unless(auth()->user()?->can('measurement-units.update'), 403);
        abort_unless(CompanyContext::belongsToUser($measurementUnit->company_id, $request->user()), 403);

        if ($request->ajax()) {
            return view('measurement-units.partials.edit-form', compact('measurementUnit'));
        }

        return view('measurement-units.edit', compact('measurementUnit'));
    }

    public function update(UpdateMeasurementUnitRequest $request, MeasurementUnit $measurementUnit): JsonResponse|RedirectResponse
    {
        abort_unless(CompanyContext::belongsToUser($measurementUnit->company_id, $request->user()), 403);

        $measurementUnit->update(CompanyContext::applyToData($request->validated(), $request->user()));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Unidad de medida actualizada correctamente.',
                'data' => ['id' => $measurementUnit->id],
            ]);
        }

        return redirect()->route('measurement-units.index')->with('success', 'Unidad de medida actualizada correctamente.');
    }

    public function destroy(MeasurementUnit $measurementUnit): RedirectResponse
    {
        abort_unless(auth()->user()?->can('measurement-units.delete'), 403);
        abort_unless(CompanyContext::belongsToUser($measurementUnit->company_id, auth()->user()), 403);

        abort_if($measurementUnit->products()->exists(), 422, 'No se puede eliminar una unidad con productos asociados.');

        $measurementUnit->delete();

        return redirect()->route('measurement-units.index')->with('success', 'Unidad de medida eliminada correctamente.');
    }
}
