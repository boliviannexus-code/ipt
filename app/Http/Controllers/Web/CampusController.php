<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CampusController extends Controller
{
    public function index(): View
    {
        return view('campuses.index', [
            'campuses' => Campus::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        return $request->ajax() ? view('campuses.partials.create-form') : view('campuses.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $companyId = CompanyContext::id($request->user());
        abort_unless($companyId !== null && $companyId > 0, 403);

        $campus = Campus::create([...$this->validated($request, $companyId), 'company_id' => $companyId]);

        return $this->response($request, 'Sede creada correctamente.', $campus);
    }

    public function edit(Request $request, Campus $campus): View
    {
        return $request->ajax()
            ? view('campuses.partials.edit-form', compact('campus'))
            : view('campuses.edit', compact('campus'));
    }

    public function update(Request $request, Campus $campus): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request, (int) $campus->company_id, $campus);

        if ($data['code'] !== $campus->code && DB::table('campus_enrollment_sequences')->where('campus_id', $campus->id)->where('last_number', '>', 0)->exists()) {
            throw ValidationException::withMessages(['code' => 'El código no puede cambiar porque la sede ya tiene números de cuenta generados.']);
        }

        $campus->update($data);

        return $this->response($request, 'Sede actualizada correctamente.', $campus);
    }

    public function destroy(Request $request, Campus $campus): JsonResponse|RedirectResponse
    {
        if ($campus->applications()->exists()) {
            $message = 'No se puede eliminar una sede que tiene inscripciones registradas.';

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => ['campus' => [$message]]], 422);
            }

            return back()->withErrors(['campus' => $message]);
        }

        $campus->delete();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Sede eliminada correctamente.']);
        }

        return redirect()->route('campuses.index')->with('success', 'Sede eliminada correctamente.');
    }

    private function validated(Request $request, int $companyId, ?Campus $campus = null): array
    {
        $request->merge(['code' => trim((string) $request->input('code'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('campuses')->where('company_id', $companyId)->ignore($campus)],
            'code' => ['required', 'string', 'size:1', 'regex:/^[0-9]$/', Rule::unique('campuses')->where('company_id', $companyId)->ignore($campus)],
            'address' => ['required', 'string', 'max:255'],
        ], [
            'name.unique' => 'Ya existe una sede con este nombre en la empresa activa.',
            'code.size' => 'El código de la sede debe tener exactamente un dígito.',
            'code.regex' => 'El código de la sede debe ser numérico, entre 0 y 9.',
            'code.unique' => 'Ya existe una sede con este código en la empresa activa.',
        ]);
    }

    private function response(Request $request, string $message, Campus $campus): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => ['id' => $campus->id]]);
        }

        return redirect()->route('campuses.index')->with('success', $message);
    }
}
