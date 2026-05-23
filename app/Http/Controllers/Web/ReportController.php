<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const REPORT_TYPES = [
        'summary' => 'Resumen general',
        'sales-products' => 'Ventas por producto',
        'purchases-products' => 'Compras por producto',
        'stock' => 'Stock actual',
        'cash' => 'Cajas',
        'voided-sales' => 'Ventas anuladas',
        'voided-purchases' => 'Compras anuladas',
    ];

    public function index(Request $request): View
    {
        $this->authorizeReports($request);

        $filters = $this->filters($request);
        $reportType = $this->reportType($request);
        $warehouses = CompanyContext::scope(Warehouse::query()->orderBy('name'))->get(['id', 'name']);

        return view('reports.index', [
            'filters' => $filters,
            'reportType' => $reportType,
            'reportTypes' => self::REPORT_TYPES,
            'reportTitle' => self::REPORT_TYPES[$reportType],
            'warehouses' => $warehouses,
            'selectedWarehouse' => $filters['warehouse_id'] ? $warehouses->firstWhere('id', $filters['warehouse_id']) : null,
            'company' => CompanyContext::activeCompany($request->user()),
            ...$this->reportData($reportType, $filters),
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorizeReports($request);

        $filters = $this->filters($request);
        $reportType = $this->reportType($request);
        $warehouses = CompanyContext::scope(Warehouse::query()->orderBy('name'))->get(['id', 'name']);

        return view('reports.print', [
            'filters' => $filters,
            'reportType' => $reportType,
            'reportTypes' => self::REPORT_TYPES,
            'reportTitle' => self::REPORT_TYPES[$reportType],
            'selectedWarehouse' => $filters['warehouse_id'] ? $warehouses->firstWhere('id', $filters['warehouse_id']) : null,
            'company' => CompanyContext::activeCompany($request->user()),
            'generatedBy' => $request->user(),
            ...$this->reportData($reportType, $filters),
        ]);
    }

    private function filters(Request $request): array
    {
        $from = $request->date('from')?->toImmutable() ?? now()->startOfMonth()->toImmutable();
        $to = $request->date('to')?->toImmutable() ?? now()->toImmutable();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from' => $from->startOfDay(),
            'to' => $to->endOfDay(),
            'warehouse_id' => $request->integer('warehouse_id') ?: null,
        ];
    }

    private function authorizeReports(Request $request): void
    {
        abort_unless(($request->user()?->can('reports.view') ?? false) && CompanyContext::canOperate($request->user()), 403);
    }

    private function reportType(Request $request): string
    {
        $type = Str::of($request->string('type', 'summary')->toString())->trim()->lower()->toString();

        return array_key_exists($type, self::REPORT_TYPES) ? $type : 'summary';
    }

    private function reportData(string $reportType, array $filters): array
    {
        return match ($reportType) {
            'sales-products' => [
                'salesSummary' => $this->salesSummary($filters),
                'salesByProduct' => $this->salesByProduct($filters),
            ],
            'purchases-products' => [
                'purchasesSummary' => $this->purchasesSummary($filters),
                'purchasesByProduct' => $this->purchasesByProduct($filters),
            ],
            'stock' => [
                'stockRows' => $this->stockRows($filters),
            ],
            'cash' => [
                'cashRows' => $this->cashRows($filters),
            ],
            'voided-sales' => [
                'voidedSales' => $this->voidedSales($filters),
            ],
            'voided-purchases' => [
                'voidedPurchases' => $this->voidedPurchases($filters),
            ],
            default => [
                'salesSummary' => $this->salesSummary($filters),
                'salesByProduct' => $this->salesByProduct($filters),
                'purchasesSummary' => $this->purchasesSummary($filters),
                'purchasesByProduct' => $this->purchasesByProduct($filters),
                'stockRows' => $this->stockRows($filters),
                'cashRows' => $this->cashRows($filters),
                'voidedSales' => $this->voidedSales($filters),
                'voidedPurchases' => $this->voidedPurchases($filters),
            ],
        };
    }

    private function salesSummary(array $filters): array
    {
        $query = $this->salesQuery($filters)->where('status', 'completed');

        return [
            'count' => (clone $query)->count(),
            'subtotal' => (float) (clone $query)->sum('subtotal'),
            'discount' => (float) (clone $query)->sum('discount'),
            'total' => (float) (clone $query)->sum('total'),
        ];
    }

    private function purchasesSummary(array $filters): array
    {
        $query = $this->purchasesQuery($filters)->where('status', 'completed');

        return [
            'count' => (clone $query)->count(),
            'subtotal' => (float) (clone $query)->sum('subtotal'),
            'total' => (float) (clone $query)->sum('total'),
        ];
    }

    private function salesByProduct(array $filters)
    {
        return DB::table('sale_details')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->join('products', 'products.id', '=', 'sale_details.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('warehouses.company_id', $companyId))
            ->when($filters['warehouse_id'], fn ($query, $warehouseId) => $query->where('sales.warehouse_id', $warehouseId))
            ->whereBetween('sales.sale_date', [$filters['from'], $filters['to']])
            ->where('sales.status', 'completed')
            ->groupBy('sale_details.product_id', 'products.name')
            ->orderByDesc(DB::raw('SUM(sale_details.subtotal)'))
            ->limit(10)
            ->get([
                'products.name',
                DB::raw('SUM(sale_details.quantity) as units'),
                DB::raw('SUM(sale_details.subtotal) as total'),
            ]);
    }

    private function purchasesByProduct(array $filters)
    {
        return DB::table('purchase_details')
            ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->join('products', 'products.id', '=', 'purchase_details.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'purchases.warehouse_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('warehouses.company_id', $companyId))
            ->when($filters['warehouse_id'], fn ($query, $warehouseId) => $query->where('purchases.warehouse_id', $warehouseId))
            ->whereBetween('purchases.purchase_date', [$filters['from']->toDateString(), $filters['to']->toDateString()])
            ->where('purchases.status', 'completed')
            ->groupBy('purchase_details.product_id', 'products.name')
            ->orderByDesc(DB::raw('SUM(purchase_details.subtotal)'))
            ->limit(10)
            ->get([
                'products.name',
                DB::raw('SUM(purchase_details.quantity) as units'),
                DB::raw('SUM(purchase_details.subtotal) as total'),
            ]);
    }

    private function stockRows(array $filters)
    {
        return InventoryMovement::query()
            ->select([
                'warehouses.name as warehouse_name',
                'products.name as product_name',
                DB::raw('SUM(inventory_movements.quantity) as stock'),
            ])
            ->join('warehouses', 'warehouses.id', '=', 'inventory_movements.warehouse_id')
            ->join('products', 'products.id', '=', 'inventory_movements.product_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('warehouses.company_id', $companyId))
            ->when($filters['warehouse_id'], fn ($query, $warehouseId) => $query->where('inventory_movements.warehouse_id', $warehouseId))
            ->groupBy('warehouses.name', 'products.name')
            ->orderBy('warehouses.name')
            ->orderBy('products.name')
            ->limit(50)
            ->get();
    }

    private function cashRows(array $filters)
    {
        return CashRegister::query()
            ->with(['pointOfSale', 'branch', 'user'])
            ->withSum(['sales as sales_total' => fn ($query) => $query->where('status', 'completed')], 'total')
            ->withSum('expenses as expenses_total', 'amount')
            ->whereBetween('opened_at', [$filters['from'], $filters['to']])
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->whereHas('pointOfSale', fn ($pointOfSale) => $pointOfSale->where('company_id', $companyId)))
            ->when($filters['warehouse_id'], fn ($query, $warehouseId) => $query->whereHas('pointOfSale', fn ($pointOfSale) => $pointOfSale->where('warehouse_id', $warehouseId)))
            ->latest('opened_at')
            ->limit(20)
            ->get();
    }

    private function voidedSales(array $filters)
    {
        return $this->salesQuery($filters)
            ->with(['warehouse', 'user'])
            ->where('status', 'voided')
            ->latest('sale_date')
            ->limit(20)
            ->get();
    }

    private function voidedPurchases(array $filters)
    {
        return $this->purchasesQuery($filters)
            ->with(['warehouse', 'supplier', 'user'])
            ->where('status', 'voided')
            ->latest('purchase_date')
            ->limit(20)
            ->get();
    }

    private function salesQuery(array $filters): Builder
    {
        return Sale::query()
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->whereHas('warehouse', fn ($warehouse) => $warehouse->where('company_id', $companyId)))
            ->when($filters['warehouse_id'], fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->whereBetween('sale_date', [$filters['from'], $filters['to']]);
    }

    private function purchasesQuery(array $filters): Builder
    {
        return Purchase::query()
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->whereHas('warehouse', fn ($warehouse) => $warehouse->where('company_id', $companyId)))
            ->when($filters['warehouse_id'], fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->whereBetween('purchase_date', [$filters['from']->toDateString(), $filters['to']->toDateString()]);
    }
}
