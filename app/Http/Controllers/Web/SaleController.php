<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoidSaleRequest;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Services\CashRegisterService;
use App\Services\SaleService;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private readonly CashRegisterService $cashRegisters,
        private readonly SaleService $sales
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('sales.view'), 403);

        $cashRegisters = CashRegister::query()
            ->with(['pointOfSale', 'branch', 'user'])
            ->withCount('sales')
            ->withSum('sales as sales_total', 'total')
            ->withSum('expenses as expenses_total', 'amount')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->whereHas('pointOfSale', fn ($pointOfSale) => $pointOfSale->where('company_id', $companyId)))
            ->latest('opened_at')
            ->paginate(15);

        return view('sales.index', compact('cashRegisters'));
    }

    public function show(CashRegister $cashRegister): View
    {
        abort_unless(auth()->user()?->can('sales.view'), 403);
        abort_unless(CompanyContext::belongsToUser($cashRegister->pointOfSale?->company_id, auth()->user()), 403);

        $cashRegister->load(['pointOfSale', 'branch', 'user']);

        return view('sales.show', [
            'cashRegister' => $cashRegister,
            'cashSummary' => $this->cashRegisters->cashSummary($cashRegister),
        ]);
    }

    public function void(VoidSaleRequest $request, Sale $sale): JsonResponse|RedirectResponse
    {
        abort_unless(CompanyContext::belongsToUser($sale->warehouse?->company_id, $request->user()), 403);

        $this->sales->void($sale, (string) $request->validated('void_reason'), (int) $request->user()->id);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Venta anulada y stock devuelto correctamente.',
            ]);
        }

        return redirect()
            ->route('sales.cash-registers.show', $sale->cash_register_id)
            ->with('success', 'Venta anulada y stock devuelto correctamente.');
    }
}
