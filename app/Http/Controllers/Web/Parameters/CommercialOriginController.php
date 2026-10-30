<?php

namespace App\Http\Controllers\Web\Parameters;

use App\Http\Controllers\Controller;
use App\Models\CommercialOrigin;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommercialOriginController extends Controller
{
    public function index(): View
    {
        return view('parameters.commercial-origins.index');
    }

    public function create(Request $request): View
    {
        return $request->ajax()
            ? view('parameters.commercial-origins.partials.create-form')
            : view('parameters.commercial-origins.create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $companyId = CompanyContext::id($request->user());
        abort_unless($companyId !== null && $companyId > 0, 403);

        $commercialOrigin = CommercialOrigin::create([
            ...$this->validated($request, $companyId),
            'company_id' => $companyId,
        ]);

        return $this->response($request, 'Origen comercial creado correctamente.', $commercialOrigin);
    }

    public function edit(Request $request, CommercialOrigin $commercialOrigin): View
    {
        return $request->ajax()
            ? view('parameters.commercial-origins.partials.edit-form', compact('commercialOrigin'))
            : view('parameters.commercial-origins.edit', compact('commercialOrigin'));
    }

    public function update(Request $request, CommercialOrigin $commercialOrigin): JsonResponse|RedirectResponse
    {
        $commercialOrigin->update($this->validated($request, (int) $commercialOrigin->company_id, $commercialOrigin));

        return $this->response($request, 'Origen comercial actualizado correctamente.', $commercialOrigin);
    }

    public function destroy(Request $request, CommercialOrigin $commercialOrigin): JsonResponse|RedirectResponse
    {
        $commercialOrigin->delete();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Origen comercial eliminado correctamente.']);
        }

        return redirect()->route('parameters.commercial-origins.index')->with('success', 'Origen comercial eliminado correctamente.');
    }

    private function validated(Request $request, int $companyId, ?CommercialOrigin $commercialOrigin = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('commercial_origins')->where('company_id', $companyId)->ignore($commercialOrigin),
            ],
        ], [
            'name.unique' => 'Ya existe un origen comercial con este nombre en la empresa activa.',
        ]);
    }

    private function response(Request $request, string $message, CommercialOrigin $commercialOrigin): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => ['id' => $commercialOrigin->id]]);
        }

        return redirect()->route('parameters.commercial-origins.index')->with('success', $message);
    }
}
