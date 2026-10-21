<?php

namespace App\Http\Controllers\Web\Rectorate;

use App\Enums\AccountPaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rectorate\StoreAccountPaymentRequest;
use App\Models\EnrollmentContract;
use App\Services\CashRegisterService;
use App\Services\Enrollment\AccountPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountStatementController extends Controller
{
    public function __construct(
        private readonly AccountPaymentService $payments,
        private readonly CashRegisterService $cashRegisters,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $activeCashRegister = $this->cashRegisters->activeFor($request->user());
        if ($activeCashRegister === null) {
            return redirect()->route('cash-registers.index')
                ->withErrors(['cash_register' => 'Debes abrir una caja para acceder a las cuentas por cobrar.']);
        }

        $data = $request->validate(['buscar' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['buscar'] ?? '');
        $contracts = EnrollmentContract::query()
            ->with(['student.campus', 'program', 'plan', 'application.campus'])
            ->withSum(['charges as total_charged' => fn ($query) => $query->where('status', '!=', 'cancelled')], 'amount')
            ->withSum(['charges as total_paid' => fn ($query) => $query->where('status', '!=', 'cancelled')], 'paid_amount')
            ->where('status', '!=', 'cancelled')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('student', fn ($student) => $student
                        ->where('identity_document', 'ilike', "%{$search}%")
                        ->orWhere('account_number', 'ilike', "%{$search}%")
                        ->orWhereRaw("concat_ws(' ', first_name, paternal_surname, maternal_surname) ilike ?", ["%{$search}%"]))
                        ->orWhere('account_number', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('account_number')
            ->paginate(15)
            ->withQueryString();

        return view('rectorate.collectible-accounts', compact('activeCashRegister', 'contracts', 'search'));
    }

    public function show(Request $request, EnrollmentContract $contract): View
    {
        $contract->load([
            'creditApplications.operator', 'creditApplications.contract',
            'student.campus', 'program', 'plan', 'application.customer', 'application.campus',
            'charges' => fn ($query) => $query->orderBy('period'),
            'payments' => fn ($query) => $query->with('cashRegister')->withSum('allocations', 'amount')->latest('paid_at'),
        ]);

        return view('rectorate.account-statement', [
            'contract' => $contract,
            'activeCashRegister' => $this->cashRegisters->activeFor($request->user()),
            'paymentMethods' => AccountPaymentMethod::options(),
            'paymentMethodLabels' => AccountPaymentMethod::labels(),
        ]);
    }

    public function store(StoreAccountPaymentRequest $request, EnrollmentContract $contract): RedirectResponse
    {
        $this->payments->record(
            $request->user(),
            $contract,
            (string) $request->validated('amount'),
            $request->safe()->only(['payment_method_code', 'reference']),
        );

        return redirect()->route('rectorate.contracts.account.show', $contract)
            ->with('success', 'Pago registrado correctamente.');
    }
}
