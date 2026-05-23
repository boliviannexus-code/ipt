<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function create(array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $userId): Purchase {
            $warehouse = Warehouse::query()
                ->with('branch')
                ->lockForUpdate()
                ->findOrFail((int) $data['warehouse_id']);

            if (! empty($data['supplier_id'])) {
                $supplierBelongsToWarehouseCompany = Supplier::query()
                    ->whereKey((int) $data['supplier_id'])
                    ->when($warehouse->company_id, fn ($query) => $query->where('company_id', $warehouse->company_id))
                    ->exists();

                if (! $supplierBelongsToWarehouseCompany) {
                    throw ValidationException::withMessages([
                        'supplier_id' => 'Selecciona un proveedor de la misma empresa.',
                    ]);
                }
            }

            $items = $this->normalizeItems($data['items'], $warehouse->company_id);
            $subtotal = collect($items)->sum('subtotal');
            $sequence = $this->nextSequence($warehouse->id);
            $reference = $this->referenceFor($warehouse, $sequence);

            $purchase = Purchase::query()->create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'warehouse_id' => $warehouse->id,
                'user_id' => $userId,
                'reference' => $reference,
                'sequence_number' => $sequence,
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $subtotal,
                'tax' => 0,
                'total' => $subtotal,
                'status' => 'completed',
            'notes' => $data['notes'] ?? null,
        ]);

            foreach ($items as $item) {
                $purchase->details()->create($item);

                InventoryMovement::query()->create([
                    'product_id' => $item['product_id'],
                    'presentation_id' => $item['presentation_id'],
                    'presentation_name' => $item['presentation_name'],
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $userId,
                    'type' => InventoryMovementType::Purchase,
                    'quantity' => $item['quantity'],
                    'package_quantity' => $item['package_quantity'],
                    'units_per_package' => $item['units_per_package'],
                    'reference_id' => $purchase->id,
                    'reference_type' => 'purchase',
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            return $purchase->load(['supplier', 'warehouse.branch', 'details.product', 'details.presentation']);
        });
    }

    public function void(Purchase $purchase, string $reason, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase, $reason, $userId): Purchase {
            $purchase = Purchase::query()
                ->with(['details', 'warehouse'])
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if ($purchase->status === 'voided') {
                throw ValidationException::withMessages([
                    'purchase' => 'La compra ya fue anulada.',
                ]);
            }

            $warehouse = $purchase->warehouse;

            foreach ($purchase->details as $detail) {
                $currentStock = (int) InventoryMovement::query()
                    ->where('product_id', $detail->product_id)
                    ->where('warehouse_id', $warehouse->id)
                    ->sum('quantity');
                $currentPackages = (int) InventoryMovement::query()
                    ->where('product_id', $detail->product_id)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('presentation_id', $detail->presentation_id)
                    ->sum('package_quantity');

                if ($currentStock < (int) $detail->quantity || $currentPackages < (int) $detail->package_quantity) {
                    throw ValidationException::withMessages([
                        'purchase' => 'No se puede anular: el stock de uno o mas productos ya fue consumido.',
                    ]);
                }
            }

            $notes = trim('Anulacion de compra '.$purchase->reference.'. Motivo: '.$reason);

            foreach ($purchase->details as $detail) {
                InventoryMovement::query()->create([
                    'product_id' => $detail->product_id,
                    'presentation_id' => $detail->presentation_id,
                    'presentation_name' => $detail->presentation_name,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => $userId,
                    'type' => InventoryMovementType::AdjustmentOut,
                    'quantity' => (int) $detail->quantity * -1,
                    'package_quantity' => (int) $detail->package_quantity * -1,
                    'units_per_package' => (int) $detail->units_per_package,
                    'reference_id' => $purchase->id,
                    'reference_type' => 'purchase_void',
                    'notes' => $notes,
                ]);
            }

            $purchase->update([
                'status' => 'voided',
                'notes' => trim(($purchase->notes ? $purchase->notes.' | ' : '').$notes),
            ]);

            return $purchase->refresh()->load(['supplier', 'warehouse.branch', 'details.product', 'details.presentation']);
        });
    }

    public function previewReference(int $warehouseId): string
    {
        $warehouse = Warehouse::query()->with('branch')->findOrFail($warehouseId);

        return $this->referenceFor($warehouse, $this->nextSequence($warehouseId));
    }

    private function normalizeItems(array $items, ?int $companyId): array
    {
        $productIds = collect($items)->pluck('product_id')->filter()->map(fn ($id): int => (int) $id)->unique();
        $presentationIds = collect($items)->pluck('presentation_id')->filter()->map(fn ($id): int => (int) $id)->unique();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $presentations = Presentation::query()
            ->whereIn('id', $presentationIds)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $normalized = [];

        foreach ($items as $index => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $presentationId = (int) ($item['presentation_id'] ?? 0);
            $product = $products->get($productId);
            $presentation = $presentations->get($presentationId);

            if (! $product || ! $presentation) {
                throw ValidationException::withMessages([
                    'items.'.($index).'.product_id' => 'Selecciona un producto y una presentacion activos.',
                ]);
            }

            $packageQuantity = (int) $item['package_quantity'];
            $unitPrice = round((float) $item['unit_price'], 2);
            $unitsPerPackage = (int) $presentation->units_per_package;
            $quantity = $packageQuantity * $unitsPerPackage;

            $normalized[] = [
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'presentation_name' => $presentation->name,
                'package_quantity' => $packageQuantity,
                'units_per_package' => $unitsPerPackage,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => round($packageQuantity * $unitPrice, 2),
            ];
        }

        return $normalized;
    }

    private function nextSequence(int $warehouseId): int
    {
        return ((int) Purchase::query()
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->max('sequence_number')) + 1;
    }

    private function referenceFor(Warehouse $warehouse, int $sequence): string
    {
        return $warehouse->branch_id.'-'.$warehouse->id.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
