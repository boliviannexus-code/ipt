<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashRegisters\CloseCashRegisterRequest;
use App\Http\Requests\CashRegisters\OpenCashRegisterRequest;
use App\Models\CashRegister;
use App\Models\SinCatalogItem;
use App\Services\CashRegisterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashRegisterController extends Controller
{
    public function __construct(
        private readonly CashRegisterService $cashRegisters,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CashRegister::class);
        $activeCashRegister = $this->cashRegisters->activeFor($request->user());
        $activeCashRegister?->load([
            'accountPayments' => fn ($query) => $query
                ->with('contract.student:id,first_name,paternal_surname,maternal_surname')
                ->latest('paid_at'),
        ]);

        return view('cash-registers.index', [
            'cashRegisterHistoryCount' => $this->cashRegisters->closedCount(),
            'activeCashRegister' => $activeCashRegister,
            'paymentMethodLabels' => SinCatalogItem::query()
                ->where('catalog_key', 'tipos_metodo_pago')
                ->pluck('description', 'classifier_code'),
        ]);
    }

    public function history(): View
    {
        $this->authorize('viewAny', CashRegister::class);

        return view('cash-registers.history', [
            'cashRegisters' => $this->cashRegisters->paginateClosed(),
        ]);
    }

    public function show(CashRegister $cashRegister): View
    {
        $this->authorize('view', $cashRegister);
        abort_if($cashRegister->isActive(), 404);

        $cashRegister->load([
            'user:id,name,email',
            'accountPayments' => fn ($query) => $query
                ->with(['contract.student:id,first_name,paternal_surname,maternal_surname', 'receiver:id,name'])
                ->oldest('paid_at'),
        ]);

        return view('cash-registers.show', [
            'cashRegister' => $cashRegister,
            'paymentMethodLabels' => SinCatalogItem::query()
                ->where('catalog_key', 'tipos_metodo_pago')
                ->pluck('description', 'classifier_code'),
        ]);
    }

    public function store(OpenCashRegisterRequest $request): RedirectResponse
    {
        $this->cashRegisters->open($request->user(), $request->validated());

        return redirect()
            ->route('cash-registers.index')
            ->with('success', 'Caja abierta correctamente.');
    }

    public function close(
        CloseCashRegisterRequest $request,
        CashRegister $cashRegister
    ): RedirectResponse {
        $this->cashRegisters->close($cashRegister, $request->user(), $request->validated());

        return redirect()
            ->route('cash-registers.index')
            ->with('success', 'Caja cerrada correctamente.');
    }
}
