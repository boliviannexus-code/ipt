<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentContract;
use App\Services\Rectorate\EnrollmentContractDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminContractController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['q'] ?? '');
        $contracts = EnrollmentContract::query()->with(['student', 'program', 'campus'])
            ->withExists(['payments', 'creditApplications', 'charges as has_paid_charges' => fn ($query) => $query->where('paid_amount', '>', 0)->orWhereHas('allocations')])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('account_number', 'ilike', "%{$search}%")
                    ->orWhereRaw('CAST(contract_number AS TEXT) ILIKE ?', ["%{$search}%"])
                    ->orWhereHas('student', fn ($query) => $query->whereRaw("concat_ws(' ', first_name, paternal_surname, maternal_surname) ilike ?", ["%{$search}%"])
                        ->orWhere('identity_document', 'ilike', "%{$search}%"));
            }))
            ->latest('id')->paginate(20)->withQueryString();

        return view('super-admin.contracts.index', compact('contracts', 'search'));
    }

    public function destroy(Request $request, EnrollmentContract $contract, EnrollmentContractDeletionService $deletion): RedirectResponse
    {
        $deletion->delete($request->user(), $contract);

        return redirect()->route('super-admin.contracts.index')->with('success', 'Contrato, inscripción y dependencias eliminados correctamente.');
    }
}
