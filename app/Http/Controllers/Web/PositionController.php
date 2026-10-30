<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Position;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(): View
    {
        return view('positions.index', ['positions' => Position::with('area')->withCount('personnel')->orderBy('name')->paginate(15)]);
    }

    public function create(Request $request): View
    {
        $data = ['areas' => Area::where('is_active', true)->orderBy('name')->get()];

        return $request->ajax() ? view('positions.partials.create-form', $data) : view('positions.create', $data);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $companyId = CompanyContext::isGlobalAdmin($request->user()) ? (int) Area::findOrFail($request->integer('area_id'))->company_id : (int) $request->user()->company_id;
        $data = $request->validate([
            'area_id' => ['required', Rule::exists('areas', 'id')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:120', Rule::unique('positions')->where(fn ($q) => $q->where('company_id', $companyId)->where('area_id', $request->integer('area_id')))],
            'is_academic' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $position = Position::create([...$data, 'company_id' => $companyId, 'is_academic' => (bool) ($data['is_academic'] ?? false), 'is_active' => (bool) ($data['is_active'] ?? false)]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Cargo creado correctamente.', 'data' => ['id' => $position->id]]);
        }

        return redirect()->route('positions.index')->with('success', 'Cargo creado correctamente.');
    }

    public function edit(Request $request, Position $position): View
    {
        $data = ['position' => $position, 'areas' => Area::where('company_id', $position->company_id)->orderBy('name')->get()];

        return $request->ajax() ? view('positions.partials.edit-form', $data) : view('positions.edit', $data);
    }

    public function update(Request $request, Position $position): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'area_id' => ['required', Rule::exists('areas', 'id')->where('company_id', $position->company_id)],
            'name' => ['required', 'string', 'max:120', Rule::unique('positions')->where(fn ($q) => $q->where('company_id', $position->company_id)->where('area_id', $request->integer('area_id')))->ignore($position)],
            'is_academic' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $position->update([...$data, 'is_academic' => (bool) ($data['is_academic'] ?? false), 'is_active' => (bool) ($data['is_active'] ?? false)]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Cargo actualizado correctamente.', 'data' => ['id' => $position->id]]);
        }

        return redirect()->route('positions.index')->with('success', 'Cargo actualizado correctamente.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->personnel()->exists()) {
            return back()->withErrors(['position' => 'No se puede eliminar un cargo asignado a personal.']);
        }
        $position->delete();

        return redirect()->route('positions.index')->with('success', 'Cargo eliminado correctamente.');
    }
}
