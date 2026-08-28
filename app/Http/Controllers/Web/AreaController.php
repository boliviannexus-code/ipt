<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function index(): View
    {
        return view('areas.index', ['areas' => Area::query()->withCount('positions')->orderBy('name')->paginate(15)]);
    }

    public function create(): View
    {
        return view('areas.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);
        $data = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:120', Rule::unique('areas')->where('company_id', $companyId)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        Area::create(['company_id' => $companyId, 'name' => $data['name'], 'is_active' => (bool) ($data['is_active'] ?? false)]);

        return redirect()->route('areas.index')->with('success', 'Área creada correctamente.');
    }

    public function edit(Area $area): View
    {
        return view('areas.edit', compact('area'));
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('areas')->where('company_id', $area->company_id)->ignore($area)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $area->update(['name' => $data['name'], 'is_active' => (bool) ($data['is_active'] ?? false)]);

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
