<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Personnel;
use App\Models\Position;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonnelController extends Controller
{
    public function index(): View
    {
        return view('personnel.index', ['personnel' => Personnel::with(['position.area', 'campus', 'user'])->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('personnel.create', [
            'positions' => Position::with('area')->where('is_active', true)->orderBy('name')->get(),
            'campuses' => Campus::query()->orderBy('name')->get(),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identity_document' => ['required', 'string', 'max:30'],
            'exclude_id' => ['nullable', 'integer'],
        ]);

        $personnel = Personnel::query()
            ->withTrashed()
            ->with(['position.area', 'campus', 'user'])
            ->when($data['exclude_id'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
            ->where('identity_document', $data['identity_document'])
            ->first();

        if (! $personnel) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'message' => 'Este CI ya pertenece a personal registrado en la empresa.',
            'personnel' => [
                'id' => $personnel->id,
                'first_name' => $personnel->first_name,
                'paternal_surname' => $personnel->paternal_surname,
                'maternal_surname' => $personnel->maternal_surname,
                'birth_date' => $personnel->birth_date?->format('Y-m-d'),
                'phone' => $personnel->phone,
                'email' => $personnel->email,
                'position_id' => $personnel->position_id,
                'position' => $personnel->position?->name,
                'area' => $personnel->position?->area?->name,
                'campus_id' => $personnel->campus_id,
                'campus' => $personnel->campus?->name,
                'has_user' => $personnel->user !== null,
                'deleted' => $personnel->trashed(),
                'show_url' => $personnel->trashed() ? null : route('personnel.show', $personnel),
                'edit_url' => $personnel->trashed() ? null : route('personnel.edit', $personnel),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $position = Position::with('area')->findOrFail($request->integer('position_id'));
        $companyId = CompanyContext::isGlobalAdmin($request->user()) ? (int) $position->company_id : (int) $request->user()->company_id;
        $data = $this->validated($request, $companyId);
        Personnel::create([...$data, 'company_id' => $companyId, 'is_active' => (bool) ($data['is_active'] ?? false)]);

        return redirect()->route('personnel.index')->with('success', 'Personal registrado correctamente. Ahora puede asignarle un usuario.');
    }

    public function show(Personnel $personnel): View
    {
        return view('personnel.show', ['personnel' => $personnel->load(['company', 'position.area', 'campus', 'user.roles'])]);
    }

    public function edit(Personnel $personnel): View
    {
        return view('personnel.edit', [
            'personnel' => $personnel,
            'positions' => Position::with('area')->where('company_id', $personnel->company_id)->orderBy('name')->get(),
            'campuses' => Campus::query()->forCompany((int) $personnel->company_id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Personnel $personnel): RedirectResponse
    {
        $data = $this->validated($request, (int) $personnel->company_id, $personnel);
        $personnel->update([...$data, 'is_active' => (bool) ($data['is_active'] ?? false)]);
        if ($personnel->user) {
            $personnel->user->update(['name' => $personnel->full_name, 'email' => $personnel->email ?? $personnel->user->email]);
        }

        return redirect()->route('personnel.index')->with('success', 'Personal actualizado correctamente.');
    }

    public function destroy(Personnel $personnel): RedirectResponse
    {
        if ($personnel->user()->exists()) {
            return back()->withErrors(['personnel' => 'Desvincule o elimine el usuario antes de eliminar el personal.']);
        }
        $personnel->delete();

        return redirect()->route('personnel.index')->with('success', 'Personal eliminado correctamente.');
    }

    private function validated(Request $request, int $companyId, ?Personnel $personnel = null): array
    {
        return $request->validate([
            'position_id' => ['required', Rule::exists('positions', 'id')->where('company_id', $companyId)],
            'campus_id' => ['nullable', 'integer', Rule::exists('campuses', 'id')->where('company_id', $companyId)],
            'first_name' => ['required', 'string', 'max:100'],
            'paternal_surname' => ['required', 'string', 'max:100'],
            'maternal_surname' => ['nullable', 'string', 'max:100'],
            'identity_document' => ['required', 'string', 'max:30', Rule::unique('personnel')->where('company_id', $companyId)->ignore($personnel)],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'identity_document.unique' => 'Ya existe personal registrado con este CI en la empresa activa.',
        ]);
    }
}
