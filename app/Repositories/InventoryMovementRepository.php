<?php

namespace App\Repositories;

use App\Models\InventoryMovement;
use App\Support\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryMovementRepository
{
    public function latest(int $perPage = 20): LengthAwarePaginator
    {
        return InventoryMovement::query()
            ->with(['product', 'presentation', 'warehouse.branch', 'user'])
            ->whereHas('warehouse', fn ($query) => $query->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId)))
            ->latest()
            ->paginate($perPage);
    }

    public function stockSummary(): Collection
    {
        return InventoryMovement::query()
            ->select('product_id', 'warehouse_id', DB::raw('SUM(quantity) as stock'))
            ->with(['product.category', 'warehouse.branch'])
            ->whereHas('warehouse', fn ($query) => $query->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId)))
            ->groupBy('product_id', 'warehouse_id')
            ->havingRaw('SUM(quantity) <> 0')
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();
    }

    public function stockByWarehouse(int $warehouseId): Collection
    {
        return InventoryMovement::query()
            ->select('product_id', DB::raw('SUM(quantity) as stock'))
            ->where('warehouse_id', $warehouseId)
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');
    }

    public function productStock(int $productId, int $warehouseId): int
    {
        return (int) InventoryMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity');
    }

    public function productBaseStock(int $productId, int $warehouseId): int
    {
        return (int) InventoryMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereNull('presentation_id')
            ->sum('quantity');
    }

    public function productPresentationPackages(int $productId, int $warehouseId, int $presentationId): int
    {
        return (int) InventoryMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('presentation_id', $presentationId)
            ->sum('package_quantity');
    }

    public function availablePresentationsForDefragmentation(int $productId, int $warehouseId): Collection
    {
        return InventoryMovement::query()
            ->select([
                'inventory_movements.presentation_id',
                DB::raw('COALESCE(inventory_movements.presentation_name, presentations.name) as presentation_name'),
                'inventory_movements.units_per_package',
                DB::raw('SUM(inventory_movements.package_quantity) as packages'),
                DB::raw('SUM(inventory_movements.quantity) as units'),
            ])
            ->join('presentations', 'presentations.id', '=', 'inventory_movements.presentation_id')
            ->where('inventory_movements.product_id', $productId)
            ->where('inventory_movements.warehouse_id', $warehouseId)
            ->where('inventory_movements.units_per_package', '>', 1)
            ->groupBy('inventory_movements.presentation_id', 'inventory_movements.presentation_name', 'presentations.name', 'inventory_movements.units_per_package')
            ->havingRaw('SUM(inventory_movements.package_quantity) > 0')
            ->orderBy('presentation_name')
            ->get();
    }

    public function availablePresentationsForAdjustment(int $productId, int $warehouseId): Collection
    {
        return InventoryMovement::query()
            ->select([
                'inventory_movements.presentation_id',
                DB::raw('COALESCE(inventory_movements.presentation_name, presentations.name) as presentation_name'),
                'inventory_movements.units_per_package',
                DB::raw('SUM(inventory_movements.package_quantity) as packages'),
                DB::raw('SUM(inventory_movements.quantity) as units'),
            ])
            ->join('presentations', 'presentations.id', '=', 'inventory_movements.presentation_id')
            ->where('inventory_movements.product_id', $productId)
            ->where('inventory_movements.warehouse_id', $warehouseId)
            ->groupBy('inventory_movements.presentation_id', 'inventory_movements.presentation_name', 'presentations.name', 'inventory_movements.units_per_package')
            ->havingRaw('SUM(inventory_movements.package_quantity) <> 0')
            ->orderBy('inventory_movements.units_per_package')
            ->orderBy('presentation_name')
            ->get();
    }

    public function create(array $data): InventoryMovement
    {
        return InventoryMovement::create($data);
    }
}
