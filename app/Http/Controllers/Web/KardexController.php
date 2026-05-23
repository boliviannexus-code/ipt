<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KardexController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        return view('inventory.kardex', [
            'categories' => Category::query()
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => Product::query()
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'warehouses' => Warehouse::query()
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Product $product): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);
        abort_unless(CompanyContext::belongsToUser($product->company_id, $request->user()), 403);

        $warehouseId = $request->integer('warehouse_id') ?: null;
        $warehouse = $warehouseId ? Warehouse::query()
            ->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->findOrFail($warehouseId) : null;
        $movements = InventoryMovement::query()
            ->with(['warehouse.branch', 'user'])
            ->where('product_id', $product->id)
            ->whereHas('warehouse', fn ($query) => $query->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId)))
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $balance = 0;
        $movements = $movements->map(function (InventoryMovement $movement) use (&$balance): InventoryMovement {
            $movement->setAttribute('previous_balance', $balance);
            $balance += (int) $movement->quantity;
            $movement->setAttribute('running_balance', $balance);

            return $movement;
        });

        return view('inventory.partials.kardex-detail', [
            'product' => $product,
            'warehouse' => $warehouse,
            'movements' => $movements,
        ]);
    }
}
