<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdditionalService;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdditionalServiceController extends Controller
{
    public function index(): View
    {
        $services = AdditionalService::query()->orderBy('title')->paginate(20);

        return view('additional-services.index', compact('services'));
    }

    public function create(): View
    {
        return view('additional-services.form', ['service' => new AdditionalService(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = CompanyContext::id($request->user());
        abort_unless($companyId && $companyId > 0, 403);
        AdditionalService::create([...$this->validated($request, $companyId), 'company_id' => $companyId]);

        return redirect()->route('additional-services.index')->with('success', 'Servicio adicional registrado correctamente.');
    }

    public function edit(AdditionalService $service): View
    {
        return view('additional-services.form', compact('service'));
    }

    public function update(Request $request, AdditionalService $service): RedirectResponse
    {
        $service->update($this->validated($request, (int) $service->company_id, $service));

        return redirect()->route('additional-services.index')->with('success', 'Servicio actualizado. Los cargos registrados conservan su costo original.');
    }

    private function validated(Request $request, int $companyId, ?AdditionalService $service = null): array
    {
        $request->merge(['title' => trim((string) $request->input('title')), 'detail' => trim((string) $request->input('detail'))]);

        return $request->validate([
            'title' => ['required', 'string', 'max:150', Rule::unique('additional_services')->where('company_id', $companyId)->ignore($service)],
            'detail' => ['required', 'string', 'max:2000'],
            'cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
