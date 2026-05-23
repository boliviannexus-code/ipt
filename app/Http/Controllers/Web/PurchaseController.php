<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\VoidPurchaseRequest;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchases
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('purchases.view'), 403);

        return view('purchases.index', [
            'defaultDate' => now()->toDateString(),
            'suppliers' => Supplier::query()
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'company_name']),
            'users' => User::query()
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('purchases.create'), 403);

        $warehouses = Warehouse::query()
            ->with('branch')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('purchases.create', [
            'products' => Product::query()
                ->with('measurementUnit')
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'presentations' => Presentation::query()
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->where('is_active', true)
                ->orderBy('units_per_package')
                ->orderBy('name')
                ->get(),
            'referencePreviews' => $warehouses
                ->mapWithKeys(fn (Warehouse $warehouse): array => [$warehouse->id => $this->purchases->previewReference($warehouse->id)])
                ->all(),
            'suppliers' => Supplier::query()
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'warehouses' => $warehouses,
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $purchase = $this->purchases->create($request->validated(), (int) $request->user()->id);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Compra registrada correctamente. Comprobante: '.$purchase->reference);
    }

    public function show(Purchase $purchase): View
    {
        abort_unless(auth()->user()?->can('purchases.view'), 403);
        abort_unless(CompanyContext::belongsToUser($purchase->warehouse?->company_id, auth()->user()), 403);

        return view('purchases.show', [
            'purchase' => $purchase->load(['supplier', 'warehouse.branch', 'user', 'details.product.measurementUnit', 'details.presentation']),
        ]);
    }

    public function void(VoidPurchaseRequest $request, Purchase $purchase): JsonResponse|RedirectResponse
    {
        abort_unless(CompanyContext::belongsToUser($purchase->warehouse?->company_id, $request->user()), 403);

        $this->purchases->void($purchase, (string) $request->validated('void_reason'), (int) $request->user()->id);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Compra anulada y stock revertido correctamente.',
            ]);
        }

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Compra anulada y stock revertido correctamente.');
    }
}
