<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function index(): View
    {
        return view('areas.index');
    }

    public function create(Request $request): View
    {
        return $request->ajax() ? view('areas.partials.create-form') : view('areas.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $companyId = $this->companyId($request);
        $data = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:120', Rule::unique('areas')->where('company_id', $companyId)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $area = Area::create(['company_id' => $companyId, 'name' => $data['name'], 'is_active' => (bool) ($data['is_active'] ?? false)]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Área creada correctamente.', 'data' => ['id' => $area->id]]);
        }

        return redirect()->route('areas.index')->with('success', 'Área creada correctamente.');
    }

    public function edit(Request $request, Area $area): View
    {
        return $request->ajax() ? view('areas.partials.edit-form', compact('area')) : view('areas.edit', compact('area'));
    }

    public function update(Request $request, Area $area): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('areas')->where('company_id', $area->company_id)->ignore($area)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $area->update(['name' => $data['name'], 'is_active' => (bool) ($data['is_active'] ?? false)]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Área actualizada correctamente.', 'data' => ['id' => $area->id]]);
        }

        return redirect()->route('areas.index')->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        if ($area->positions()->exists()) {
            return back()->withErrors(['area' => 'No se puede eliminar un área con cargos registrados.']);
        }
        $area->delete();

        return redirect()->route('areas.index')->with('success', 'Área eliminada correctamente.');
    }

    private function companyId(Request $request): int
    {
        return CompanyContext::isGlobalAdmin($request->user()) ? (int) $request->input('company_id') : (int) $request->user()->company_id;
    }
}
