<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('dashboard.view');

        $products = CompanyContext::scope(Product::query());
        $categories = CompanyContext::scope(Category::query());
        $warehouses = CompanyContext::scope(Warehouse::query());
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        $todaySales = $this->salesQuery()->whereBetween('sale_date', [$today, now()])->where('status', 'completed');
        $monthSales = $this->salesQuery()->whereBetween('sale_date', [$monthStart, now()])->where('status', 'completed');
        $monthPurchases = $this->purchasesQuery()->whereBetween('purchase_date', [$monthStart->toDateString(), now()->toDateString()])->where('status', 'completed');

        return view('dashboard.index', [
            'dashboardCompany' => CompanyContext::activeCompany(),
            'totalProducts' => (clone $products)->count(),
            'activeProducts' => (clone $products)->where('is_active', true)->count(),
            'totalCategories' => (clone $categories)->count(),
            'activeCategories' => (clone $categories)->where('is_active', true)->count(),
            'totalWarehouses' => (clone $warehouses)->count(),
            'todaySalesTotal' => (float) (clone $todaySales)->sum('total'),
            'todaySalesCount' => (clone $todaySales)->count(),
            'monthSalesTotal' => (float) (clone $monthSales)->sum('total'),
            'monthSalesCount' => (clone $monthSales)->count(),
            'monthPurchasesTotal' => (float) (clone $monthPurchases)->sum('total'),
            'monthPurchasesCount' => (clone $monthPurchases)->count(),
            'openCashRegisters' => $this->cashRegistersQuery()->where('status', 'open')->count(),
            'currentStockTotal' => (int) $this->stockQuery()->sum('inventory_movements.quantity'),
            'lowStockProducts' => $this->lowStockProducts(),
            'topProducts' => $this->topProducts($monthStart, now()),
            'salesTrend' => $this->salesTrend(),
            'latestSales' => $this->latestSales(),
            'openRegisters' => $this->openRegisters(),
            'recentMovements' => $this->recentMovements(),
        ]);
    }

    private function salesQuery(): Builder
    {
        return Sale::query()
            ->when(CompanyContext::id(), fn (Builder $query, int $companyId): Builder => $query->whereHas('warehouse', fn (Builder $warehouse): Builder => $warehouse->where('company_id', $companyId)));
    }

    private function purchasesQuery(): Builder
    {
        return Purchase::query()
            ->when(CompanyContext::id(), fn (Builder $query, int $companyId): Builder => $query->whereHas('warehouse', fn (Builder $warehouse): Builder => $warehouse->where('company_id', $companyId)));
    }

    private function cashRegistersQuery(): Builder
    {
        return CashRegister::query()
            ->when(CompanyContext::id(), fn (Builder $query, int $companyId): Builder => $query->whereHas('pointOfSale', fn (Builder $pointOfSale): Builder => $pointOfSale->where('company_id', $companyId)));
    }

    private function stockQuery(): Builder
    {
        return InventoryMovement::query()
            ->join('warehouses', 'warehouses.id', '=', 'inventory_movements.warehouse_id')
            ->when(CompanyContext::id(), fn (Builder $query, int $companyId): Builder => $query->where('warehouses.company_id', $companyId));
    }

    private function lowStockProducts()
    {
        return InventoryMovement::query()
            ->select([
                'products.id',
                'products.name',
                DB::raw('SUM(inventory_movements.quantity) as stock'),
            ])
            ->join('products', 'products.id', '=', 'inventory_movements.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_movements.warehouse_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('warehouses.company_id', $companyId))
            ->groupBy('products.id', 'products.name')
            ->havingRaw('SUM(inventory_movements.quantity) <= 5')
            ->orderBy('stock')
            ->limit(6)
            ->get();
    }

    private function topProducts(Carbon $from, Carbon $to)
    {
        return DB::table('sale_details')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->join('products', 'products.id', '=', 'sale_details.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('warehouses.company_id', $companyId))
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc(DB::raw('SUM(sale_details.subtotal)'))
            ->limit(5)
            ->get([
                'products.name',
                DB::raw('SUM(sale_details.quantity) as units'),
                DB::raw('SUM(sale_details.subtotal) as total'),
            ]);
    }

    private function salesTrend(): array
    {
        return collect(range(6, 0))
            ->map(function (int $daysAgo): array {
                $day = now()->subDays($daysAgo);
                $query = $this->salesQuery()
                    ->where('status', 'completed')
                    ->whereBetween('sale_date', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);

                return [
                    'label' => $day->format('d/m'),
                    'total' => (float) $query->sum('total'),
                ];
            })
            ->all();
    }

    private function latestSales()
    {
        return $this->salesQuery()
            ->with(['warehouse', 'user'])
            ->where('status', 'completed')
            ->latest('sale_date')
            ->limit(5)
            ->get();
    }

    private function openRegisters()
    {
        return $this->cashRegistersQuery()
            ->with(['pointOfSale', 'branch', 'user'])
            ->where('status', 'open')
            ->latest('opened_at')
            ->limit(5)
            ->get();
    }

    private function recentMovements()
    {
        return InventoryMovement::query()
            ->with(['product', 'warehouse', 'user'])
            ->whereHas('warehouse', fn (Builder $query): Builder => CompanyContext::scope($query))
            ->latest()
            ->limit(6)
            ->get();
    }
}
