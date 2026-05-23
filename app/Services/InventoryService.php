<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Warehouse;
use App\Repositories\InventoryMovementRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private readonly InventoryMovementRepository $movements
    ) {}

    public function stockSummary(): Collection
    {
        return $this->movements->stockSummary();
    }

    public function latestMovements(int $perPage = 20): LengthAwarePaginator
    {
        return $this->movements->latest($perPage);
    }

    public function register(array $data, int $userId): void
    {
        $operation = $data['operation'];
        $notes = $data['notes'] ?? null;

        DB::transaction(function () use ($data, $operation, $notes, $userId): void {
            match ($operation) {
                'in' => $this->registerAdjustmentIn(
                    (int) $data['warehouse_id'],
                    $this->normalizeItemsForWarehouse($data['items'] ?? [], (int) $data['warehouse_id']),
                    $notes,
                    $userId
                ),
                'out' => $this->registerAdjustmentOut(
                    (int) $data['warehouse_id'],
                    $this->normalizeItemsForWarehouse($data['items'] ?? [], (int) $data['warehouse_id']),
                    $notes,
                    $userId
                ),
                'transfer' => $this->registerTransfer(
                    (int) $data['source_warehouse_id'],
                    (int) $data['target_warehouse_id'],
                    $data['items'] ?? [],
                    $notes,
                    $userId
                ),
            };
        });

        Log::info('Inventory movement registered', [
            'operation' => $operation,
            'items' => count($data['items'] ?? []),
            'user_id' => $userId,
        ]);
    }

    public function stockMap(): array
    {
        $map = [];

        $this->stockSummary()->each(function ($row) use (&$map): void {
            $map[$row->warehouse_id][$row->product_id] = (int) $row->stock;
        });

        return $map;
    }

    public function defragmentPackage(array $data, int $userId): void
    {
        $productId = (int) $data['product_id'];
        $warehouseId = (int) $data['warehouse_id'];
        $presentationId = (int) $data['presentation_id'];
        $packageQuantity = (int) $data['package_quantity'];
        $notes = $data['notes'] ?? null;

        if ($packageQuantity < 1) {
            throw ValidationException::withMessages([
                'package_quantity' => 'La cantidad debe ser al menos 1.',
            ]);
        }

        DB::transaction(function () use ($productId, $warehouseId, $presentationId, $packageQuantity, $notes, $userId): void {
            $warehouse = Warehouse::query()->findOrFail($warehouseId);
            $product = Product::query()
                ->when($warehouse->company_id, fn ($query) => $query->where('company_id', $warehouse->company_id))
                ->findOrFail($productId);
            $presentation = Presentation::query()
                ->when($warehouse->company_id, fn ($query) => $query->where('company_id', $warehouse->company_id))
                ->findOrFail($presentationId);

            if ($presentation->units_per_package <= 1) {
                throw ValidationException::withMessages([
                    'presentation_id' => 'Selecciona una presentacion mayor a una unidad.',
                ]);
            }

            $this->ensurePresentationStock([
                'product_id' => $productId,
                'presentation_id' => $presentation->id,
                'package_quantity' => $packageQuantity,
            ], $warehouseId);

            $unitPresentation = $this->unitPresentation($warehouse->company_id);
            $units = $packageQuantity * $presentation->units_per_package;
            $referenceId = now()->format('ymdHis').random_int(100, 999);
            $productName = $product->name ?? 'Producto';
            $warehouseName = $warehouse->name ?? 'almacen';
            $movementNotes = trim(($notes ? $notes.' | ' : '')."Desfragmentacion controlada: {$packageQuantity} {$presentation->name} de {$productName} en {$warehouseName}.");

            $this->movements->create([
                'product_id' => $productId,
                'presentation_id' => $presentation->id,
                'presentation_name' => $presentation->name,
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'type' => InventoryMovementType::DefragmentOut,
                'quantity' => $units * -1,
                'package_quantity' => $packageQuantity * -1,
                'units_per_package' => $presentation->units_per_package,
                'reference_id' => $referenceId,
                'reference_type' => 'stock_defragmentation',
                'notes' => $movementNotes,
            ]);

            $this->movements->create([
                'product_id' => $productId,
                'presentation_id' => $unitPresentation->id,
                'presentation_name' => $unitPresentation->name,
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'type' => InventoryMovementType::DefragmentIn,
                'quantity' => $units,
                'package_quantity' => $units,
                'units_per_package' => 1,
                'reference_id' => $referenceId,
                'reference_type' => 'stock_defragmentation',
                'notes' => $movementNotes,
            ]);
        });
    }

    public function defragmentablePresentations(int $productId, int $warehouseId): Collection
    {
        return $this->movements->availablePresentationsForDefragmentation($productId, $warehouseId);
    }

    public function adjustablePresentations(int $productId, int $warehouseId): Collection
    {
        return $this->movements->availablePresentationsForAdjustment($productId, $warehouseId);
    }

    public function adjustStock(array $data, int $userId): void
    {
        $productId = (int) $data['product_id'];
        $warehouseId = (int) $data['warehouse_id'];
        $presentationId = isset($data['presentation_id']) ? (int) $data['presentation_id'] : 0;
        $countedQuantity = (int) $data['counted_quantity'];
        $reason = (string) ($data['reason'] ?? 'conteo_fisico');
        $notes = $data['notes'] ?? null;

        DB::transaction(function () use ($productId, $warehouseId, $presentationId, $countedQuantity, $reason, $notes, $userId): void {
            $warehouse = Warehouse::query()->findOrFail($warehouseId);
            $product = Product::query()
                ->when($warehouse->company_id, fn ($query) => $query->where('company_id', $warehouse->company_id))
                ->findOrFail($productId);
            $presentation = $presentationId > 0
                ? Presentation::query()
                    ->when($warehouse->company_id, fn ($query) => $query->where('company_id', $warehouse->company_id))
                    ->findOrFail($presentationId)
                : null;

            $currentQuantity = $presentation
                ? $this->movements->productPresentationPackages($product->id, $warehouse->id, $presentation->id)
                : $this->movements->productBaseStock($product->id, $warehouse->id);
            $difference = $countedQuantity - $currentQuantity;

            if ($difference === 0) {
                return;
            }

            $unitsPerPackage = $presentation?->units_per_package ?? 1;
            $quantity = abs($difference) * $unitsPerPackage;
            $reasonLabel = match ($reason) {
                'perdida' => 'Perdida',
                'robo' => 'Robo',
                'otros' => 'Otros',
                default => 'Conteo fisico',
            };
            $movementNotes = trim('Motivo: '.$reasonLabel.'. '.($notes ? $notes.' | ' : '').'Reajuste de stock: conteo fisico '.number_format($countedQuantity).' frente a sistema '.number_format($currentQuantity).'.');

            $this->movements->create([
                'product_id' => $product->id,
                'presentation_id' => $presentation?->id,
                'presentation_name' => $presentation?->name,
                'warehouse_id' => $warehouse->id,
                'user_id' => $userId,
                'type' => $difference > 0 ? InventoryMovementType::AdjustmentIn : InventoryMovementType::AdjustmentOut,
                'quantity' => $difference > 0 ? $quantity : $quantity * -1,
                'package_quantity' => $presentation ? $difference : null,
                'units_per_package' => $unitsPerPackage,
                'reference_type' => 'stock_recount',
                'notes' => $movementNotes,
            ]);
        });
    }

    private function registerAdjustmentIn(int $warehouseId, array $items, ?string $notes, int $userId): void
    {
        foreach ($items as $item) {
            $this->movements->create([
                'product_id' => $item['product_id'],
                'presentation_id' => $item['presentation_id'],
                'presentation_name' => $item['presentation_name'],
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'type' => InventoryMovementType::AdjustmentIn,
                'quantity' => $item['quantity'],
                'package_quantity' => $item['package_quantity'],
                'units_per_package' => $item['units_per_package'],
                'reference_type' => 'manual_adjustment',
                'notes' => $notes,
            ]);
        }
    }

    private function registerAdjustmentOut(int $warehouseId, array $items, ?string $notes, int $userId): void
    {
        foreach ($items as $item) {
            $this->ensureStock($item['product_id'], $warehouseId, $item['quantity']);
            $this->ensurePresentationStock($item, $warehouseId);

            $this->movements->create([
                'product_id' => $item['product_id'],
                'presentation_id' => $item['presentation_id'],
                'presentation_name' => $item['presentation_name'],
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'type' => InventoryMovementType::AdjustmentOut,
                'quantity' => $item['quantity'] * -1,
                'package_quantity' => $item['package_quantity'] !== null ? $item['package_quantity'] * -1 : null,
                'units_per_package' => $item['units_per_package'],
                'reference_type' => 'manual_adjustment',
                'notes' => $notes,
            ]);
        }
    }

    private function registerTransfer(int $sourceWarehouseId, int $targetWarehouseId, array $rawItems, ?string $notes, int $userId): void
    {
        if ($sourceWarehouseId === $targetWarehouseId) {
            throw ValidationException::withMessages([
                'target_warehouse_id' => 'El almacen origen y destino deben ser diferentes.',
            ]);
        }

        $sourceWarehouse = Warehouse::query()->findOrFail($sourceWarehouseId);
        $targetWarehouse = Warehouse::query()->findOrFail($targetWarehouseId);

        if ((int) $sourceWarehouse->company_id !== (int) $targetWarehouse->company_id) {
            throw ValidationException::withMessages([
                'target_warehouse_id' => 'Los almacenes deben pertenecer a la misma empresa.',
            ]);
        }

        $items = $this->expandBaseUnitItemsForTransfer(
            $this->normalizeItems($rawItems, $sourceWarehouse->company_id),
            $sourceWarehouseId
        );
        $referenceId = now()->format('ymdHis').random_int(100, 999);

        foreach ($items as $item) {
            $this->ensureStock($item['product_id'], $sourceWarehouseId, $item['quantity']);
            $this->ensurePresentationStock($item, $sourceWarehouseId);

            $this->movements->create([
                'product_id' => $item['product_id'],
                'presentation_id' => $item['presentation_id'],
                'presentation_name' => $item['presentation_name'],
                'warehouse_id' => $sourceWarehouseId,
                'user_id' => $userId,
                'type' => InventoryMovementType::TransferOut,
                'quantity' => $item['quantity'] * -1,
                'package_quantity' => $item['package_quantity'] !== null ? $item['package_quantity'] * -1 : null,
                'units_per_package' => $item['units_per_package'],
                'reference_id' => $referenceId,
                'reference_type' => 'warehouse_transfer',
                'notes' => $notes,
            ]);

            $this->movements->create([
                'product_id' => $item['product_id'],
                'presentation_id' => $item['presentation_id'],
                'presentation_name' => $item['presentation_name'],
                'warehouse_id' => $targetWarehouseId,
                'user_id' => $userId,
                'type' => InventoryMovementType::TransferIn,
                'quantity' => $item['quantity'],
                'package_quantity' => $item['package_quantity'],
                'units_per_package' => $item['units_per_package'],
                'reference_id' => $referenceId,
                'reference_type' => 'warehouse_transfer',
                'notes' => $notes,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function expandBaseUnitItemsForTransfer(array $items, int $sourceWarehouseId): array
    {
        $expanded = [];

        foreach ($items as $item) {
            if ($item['presentation_id'] !== null) {
                $expanded[] = $item;
                continue;
            }

            $remaining = (int) $item['quantity'];
            $baseStock = $this->movements->productBaseStock((int) $item['product_id'], $sourceWarehouseId);
            $baseQuantity = min($remaining, max(0, $baseStock));

            if ($baseQuantity > 0) {
                $expanded[] = [
                    ...$item,
                    'quantity' => $baseQuantity,
                ];
                $remaining -= $baseQuantity;
            }

            if ($remaining > 0) {
                InventoryMovement::query()
                    ->select([
                        'inventory_movements.presentation_id',
                        DB::raw('COALESCE(inventory_movements.presentation_name, presentations.name) as presentation_name'),
                        'inventory_movements.units_per_package',
                        DB::raw('SUM(inventory_movements.package_quantity) as packages'),
                    ])
                    ->join('presentations', 'presentations.id', '=', 'inventory_movements.presentation_id')
                    ->where('inventory_movements.product_id', $item['product_id'])
                    ->where('inventory_movements.warehouse_id', $sourceWarehouseId)
                    ->where('inventory_movements.units_per_package', 1)
                    ->groupBy('inventory_movements.presentation_id', 'inventory_movements.presentation_name', 'presentations.name', 'inventory_movements.units_per_package')
                    ->havingRaw('SUM(inventory_movements.package_quantity) > 0')
                    ->orderByRaw("CASE WHEN COALESCE(inventory_movements.presentation_name, presentations.name) = 'Unidad' THEN 0 ELSE 1 END")
                    ->orderBy('presentation_name')
                    ->get()
                    ->each(function ($presentationStock) use (&$expanded, &$remaining, $item): void {
                        if ($remaining <= 0) {
                            return;
                        }

                        $quantity = min($remaining, (int) $presentationStock->packages);

                        if ($quantity <= 0) {
                            return;
                        }

                        $expanded[] = [
                            'product_id' => $item['product_id'],
                            'presentation_id' => (int) $presentationStock->presentation_id,
                            'presentation_name' => (string) $presentationStock->presentation_name,
                            'package_quantity' => $quantity,
                            'units_per_package' => 1,
                            'quantity' => $quantity,
                        ];
                        $remaining -= $quantity;
                    });
            }

            if ($remaining > 0) {
                throw ValidationException::withMessages([
                    'items' => 'Stock insuficiente para uno o mas productos seleccionados.',
                ]);
            }
        }

        return $expanded;
    }

    private function ensureStock(int $productId, int $warehouseId, int $quantity): void
    {
        $currentStock = $this->movements->productStock($productId, $warehouseId);

        if ($currentStock < $quantity) {
            throw ValidationException::withMessages([
                'items' => 'Stock insuficiente para uno o mas productos seleccionados.',
            ]);
        }
    }

    private function ensurePresentationStock(array $item, int $warehouseId): void
    {
        if ($item['presentation_id'] === null || $item['package_quantity'] === null) {
            return;
        }

        $currentPackages = $this->movements->productPresentationPackages(
            $item['product_id'],
            $warehouseId,
            $item['presentation_id']
        );

        if ($currentPackages < $item['package_quantity']) {
            throw ValidationException::withMessages([
                'items' => 'Stock insuficiente para la presentacion seleccionada.',
            ]);
        }
    }

    private function normalizeItemsForWarehouse(array $items, int $warehouseId): array
    {
        $warehouse = Warehouse::query()->findOrFail($warehouseId);

        return $this->normalizeItems($items, $warehouse->company_id);
    }

    private function normalizeItems(array $items, ?int $companyId): array
    {
        $normalized = [];
        $presentationIds = collect($items)
            ->pluck('presentation_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $presentations = Presentation::query()
            ->whereIn('id', $presentationIds)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->get()
            ->keyBy('id');
        $products = Product::query()
            ->whereIn('id', collect($items)->pluck('product_id')->filter()->map(fn ($id): int => (int) $id)->unique())
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $presentationId = isset($item['presentation_id']) ? (int) $item['presentation_id'] : (int) ($item['product_presentation_id'] ?? 0);
            $product = $products->get($productId);
            $presentation = $presentationId > 0 ? $presentations->get($presentationId) : null;
            $packageQuantity = $presentation ? (int) ($item['package_quantity'] ?? $item['quantity'] ?? 0) : null;
            $unitsPerPackage = $presentation?->units_per_package ?? 1;
            $quantity = $presentation ? $packageQuantity * $unitsPerPackage : (int) ($item['quantity'] ?? 0);

            if (! $product || $quantity <= 0) {
                continue;
            }

            $key = $productId.'-'.($presentation?->id ?? 'base').'-'.$unitsPerPackage;
            $normalized[$key] = [
                'product_id' => $productId,
                'presentation_id' => $presentation?->id,
                'presentation_name' => $presentation?->name,
                'package_quantity' => $presentation ? ($normalized[$key]['package_quantity'] ?? 0) + $packageQuantity : null,
                'units_per_package' => $unitsPerPackage,
                'quantity' => ($normalized[$key]['quantity'] ?? 0) + $quantity,
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'items' => 'Agrega al menos un producto al movimiento.',
            ]);
        }

        return array_values($normalized);
    }

    private function unitPresentation(?int $companyId): Presentation
    {
        $unit = Presentation::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->where('units_per_package', 1)
            ->where('name', 'Unidad')
            ->first();

        if ($unit) {
            return $unit;
        }

        return Presentation::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->where('units_per_package', 1)
            ->orderBy('name')
            ->firstOrCreate(
                ['name' => 'Unidad', 'company_id' => $companyId],
                ['units_per_package' => 1, 'is_active' => true]
            );
    }
}
