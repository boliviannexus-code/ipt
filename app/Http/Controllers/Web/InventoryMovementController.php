<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryMovementRequest;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\StoreStockDefragmentationRequest;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryMovementController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        return view('inventory.index', [
            'categories' => Category::query()
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => Product::query()
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'stockSummary' => $this->inventory->stockSummary(),
            'warehouses' => Warehouse::query()
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function defragment(Request $request): View
    {
        abort_unless(auth()->user()?->can('inventory.movements'), 403);

        $product = Product::query()
            ->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->findOrFail($request->integer('product_id'));
        $warehouse = Warehouse::query()
            ->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->findOrFail($request->integer('warehouse_id'));
        $presentations = $this->inventory->defragmentablePresentations($product->id, $warehouse->id);

        return view('inventory.partials.defragment-form', compact('product', 'warehouse', 'presentations'));
    }

    public function adjustment(Request $request): View
    {
        abort_unless(($request->user()?->can('inventory.movements') ?? false) && CompanyContext::canOperate($request->user()), 403);

        $product = Product::query()
            ->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->findOrFail($request->integer('product_id'));
        $warehouse = Warehouse::query()
            ->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->findOrFail($request->integer('warehouse_id'));
        $baseStock = (int) InventoryMovement::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->whereNull('presentation_id')
            ->sum('quantity');
        $presentations = $this->inventory->adjustablePresentations($product->id, $warehouse->id);
        $unitPresentation = Presentation::query()
            ->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->where('units_per_package', 1)
            ->orderByRaw("CASE WHEN name = 'Unidad' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->first();

        if ($unitPresentation && ! $presentations->contains(fn ($presentation): bool => (int) $presentation->presentation_id === (int) $unitPresentation->id)) {
            $presentations->prepend((object) [
                'presentation_id' => $unitPresentation->id,
                'presentation_name' => $unitPresentation->name,
                'units_per_package' => $unitPresentation->units_per_package,
                'packages' => 0,
                'units' => 0,
            ]);
        }

        return view('inventory.partials.adjustment-form', compact('product', 'warehouse', 'baseStock', 'presentations'));
    }

    public function createTransfer(Request $request): View
    {
        abort_unless(($request->user()?->can('inventory.movements') ?? false) && CompanyContext::canOperate($request->user()), 403);

        $companyId = CompanyContext::id($request->user());

        $products = Product::query()
            ->when($companyId, fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $presentations = Presentation::query()
            ->when($companyId, fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->orderBy('units_per_package')
            ->orderBy('name')
            ->get(['id', 'name', 'units_per_package']);
        $warehouses = Warehouse::query()
            ->when($companyId, fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('inventory.partials.transfer-form', [
            'products' => $products,
            'presentations' => $presentations,
            'warehouses' => $warehouses,
            'stockAvailability' => $this->transferStockAvailability($warehouses->pluck('id')->all()),
        ]);
    }

    public function storeTransfer(StoreInventoryMovementRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['operation'] = 'transfer';

        $this->inventory->register($data, (int) $request->user()->id);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Transferencia registrada correctamente.',
            ]);
        }

        return redirect()->route('inventory.index')->with('success', 'Transferencia registrada correctamente.');
    }

    public function storeAdjustment(StoreStockAdjustmentRequest $request): JsonResponse|RedirectResponse
    {
        $this->inventory->adjustStock($request->validated(), (int) $request->user()->id);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Stock reajustado correctamente.',
            ]);
        }

        return redirect()->route('inventory.index')->with('success', 'Stock reajustado correctamente.');
    }

    public function storeDefragmentation(StoreStockDefragmentationRequest $request): JsonResponse|RedirectResponse
    {
        $this->inventory->defragmentPackage($request->validated(), (int) $request->user()->id);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Empaque desfragmentado correctamente.',
            ]);
        }

        return redirect()->route('inventory.index')->with('success', 'Empaque desfragmentado correctamente.');
    }

    /**
     * @param  array<int, int>  $warehouseIds
     * @return array<int, array<int, array{stock: int, base_units: int, presentations: array<int, array{id: int, name: string, packages: int, units: int, units_per_package: int}>}>>
     */
    private function transferStockAvailability(array $warehouseIds): array
    {
        if ($warehouseIds === []) {
            return [];
        }

        $availability = [];

        InventoryMovement::query()
            ->select('warehouse_id', 'product_id', DB::raw('SUM(quantity) as stock'))
            ->whereIn('warehouse_id', $warehouseIds)
            ->groupBy('warehouse_id', 'product_id')
            ->havingRaw('SUM(quantity) > 0')
            ->get()
            ->each(function (InventoryMovement $movement) use (&$availability): void {
                $availability[(int) $movement->warehouse_id][(int) $movement->product_id] = [
                    'stock' => (int) $movement->stock,
                    'base_units' => 0,
                    'presentations' => [],
                ];
            });

        InventoryMovement::query()
            ->select('warehouse_id', 'product_id', DB::raw('SUM(quantity) as base_units'))
            ->whereIn('warehouse_id', $warehouseIds)
            ->where(fn ($query) => $query
                ->whereNull('presentation_id')
                ->orWhere('units_per_package', 1))
            ->groupBy('warehouse_id', 'product_id')
            ->havingRaw('SUM(quantity) > 0')
            ->get()
            ->each(function (InventoryMovement $movement) use (&$availability): void {
                $warehouseId = (int) $movement->warehouse_id;
                $productId = (int) $movement->product_id;

                $availability[$warehouseId][$productId] ??= [
                    'stock' => 0,
                    'base_units' => 0,
                    'presentations' => [],
                ];

                $availability[$warehouseId][$productId]['base_units'] = (int) $movement->base_units;
            });

        InventoryMovement::query()
            ->select(
                'warehouse_id',
                'product_id',
                'presentation_id',
                'presentation_name',
                'units_per_package',
                DB::raw('SUM(package_quantity) as packages'),
                DB::raw('SUM(quantity) as units')
            )
            ->whereIn('warehouse_id', $warehouseIds)
            ->whereNotNull('presentation_id')
            ->where('units_per_package', '>', 1)
            ->groupBy('warehouse_id', 'product_id', 'presentation_id', 'presentation_name', 'units_per_package')
            ->havingRaw('SUM(package_quantity) > 0')
            ->get()
            ->each(function (InventoryMovement $movement) use (&$availability): void {
                $warehouseId = (int) $movement->warehouse_id;
                $productId = (int) $movement->product_id;

                $availability[$warehouseId][$productId] ??= [
                    'stock' => 0,
                    'base_units' => 0,
                    'presentations' => [],
                ];

                $availability[$warehouseId][$productId]['presentations'][] = [
                    'id' => (int) $movement->presentation_id,
                    'name' => (string) ($movement->presentation_name ?: 'Presentacion'),
                    'packages' => (int) $movement->packages,
                    'units' => (int) $movement->units,
                    'units_per_package' => (int) $movement->units_per_package,
                ];
            });

        return $availability;
    }
}
