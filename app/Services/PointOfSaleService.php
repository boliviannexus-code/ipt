<?php

namespace App\Services;

use App\Models\PointOfSale;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\PointOfSaleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PointOfSaleService
{
    public function __construct(
        private readonly PointOfSaleRepository $pointOfSales
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->pointOfSales->paginate($perPage);
    }

    public function create(array $data): PointOfSale
    {
        $pointOfSale = DB::transaction(function () use ($data): PointOfSale {
            $users = $data['users'] ?? [];
            $data = $this->normalize($data, true);
            $warehouse = Warehouse::query()->with('branch')->lockForUpdate()->findOrFail((int) $data['warehouse_id']);
            $sequence = $this->nextSequence($warehouse->id);
            $data['company_id'] = $warehouse->company_id;
            $data['branch_id'] = $warehouse->branch_id;
            $data['sequence_number'] = $sequence;
            $data['code'] = $this->referenceFor($warehouse, $sequence);
            $data['receipt_prefix'] = $this->receiptPrefix($data, $data['code']);
            $data['receipt_next_number'] = max(1, (int) ($data['receipt_next_number'] ?? 1));
            $data['receipt_digits'] = max(1, (int) ($data['receipt_digits'] ?? 6));

            $pointOfSale = $this->pointOfSales->create($data);
            $this->ensureUsersBelongToCompany($users, $pointOfSale->company_id);
            $this->removeUserAssignmentsOutsideCompany($users, $pointOfSale->company_id);
            $pointOfSale->users()->sync($users);

            return $pointOfSale->refresh()->load(['branch', 'warehouse', 'users']);
        });

        Log::info('Point of sale created', ['point_of_sale_id' => $pointOfSale->id]);

        return $pointOfSale;
    }

    public function update(PointOfSale $pointOfSale, array $data): PointOfSale
    {
        $pointOfSale = DB::transaction(function () use ($pointOfSale, $data): PointOfSale {
            $users = $data['users'] ?? [];
            $data = $this->normalize($data);
            $warehouse = Warehouse::query()->with('branch')->lockForUpdate()->findOrFail((int) $data['warehouse_id']);
            $data['company_id'] = $warehouse->company_id;
            $data['branch_id'] = $warehouse->branch_id;

            if ((int) $pointOfSale->warehouse_id !== $warehouse->id) {
                $sequence = $this->nextSequence($warehouse->id);
                $data['sequence_number'] = $sequence;
                $data['code'] = $this->referenceFor($warehouse, $sequence);
            } else {
                unset($data['code'], $data['sequence_number']);
            }

            $data['receipt_prefix'] = $this->receiptPrefix($data, $pointOfSale->receipt_prefix ?: $pointOfSale->code);
            $data['receipt_next_number'] = max(1, (int) ($data['receipt_next_number'] ?? $pointOfSale->receipt_next_number));
            $data['receipt_digits'] = max(1, (int) ($data['receipt_digits'] ?? $pointOfSale->receipt_digits));
            $this->ensureReceiptSequenceCanContinue($pointOfSale, $data['receipt_next_number']);

            $pointOfSale = $this->pointOfSales->update($pointOfSale, $data);
            $this->ensureUsersBelongToCompany($users, $pointOfSale->company_id);
            $this->removeUserAssignmentsOutsideCompany($users, $pointOfSale->company_id);
            $pointOfSale->users()->sync($users);

            return $pointOfSale->refresh()->load(['branch', 'warehouse', 'users']);
        });

        Log::info('Point of sale updated', ['point_of_sale_id' => $pointOfSale->id]);

        return $pointOfSale;
    }

    public function delete(PointOfSale $pointOfSale): bool
    {
        $deleted = $this->pointOfSales->delete($pointOfSale);

        Log::warning('Point of sale deleted', ['point_of_sale_id' => $pointOfSale->id]);

        return $deleted;
    }

    private function normalize(array $data, ?bool $defaultActive = null): array
    {
        unset($data['code'], $data['sequence_number'], $data['users']);

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        } elseif ($defaultActive !== null) {
            $data['is_active'] = $defaultActive;
        }

        return $data;
    }

    private function ensureUsersBelongToCompany(array $userIds, ?int $companyId): void
    {
        if ($companyId === null || $userIds === []) {
            return;
        }

        $invalidUsers = User::query()
            ->whereIn('id', $userIds)
            ->where(fn ($query) => $query
                ->where('company_id', '<>', $companyId)
                ->orWhereNull('company_id'))
            ->exists();

        if ($invalidUsers) {
            throw ValidationException::withMessages([
                'users' => 'Todos los usuarios asignados deben pertenecer a la misma empresa del punto de venta.',
            ]);
        }
    }

    private function removeUserAssignmentsOutsideCompany(array $userIds, ?int $companyId): void
    {
        if ($companyId === null || $userIds === []) {
            return;
        }

        $invalidPointOfSaleIds = PointOfSale::query()
            ->select('id')
            ->where('company_id', '<>', $companyId);

        DB::table('point_of_sale_user')
            ->whereIn('user_id', $userIds)
            ->whereIn('point_of_sale_id', $invalidPointOfSaleIds)
            ->delete();
    }

    private function ensureReceiptSequenceCanContinue(PointOfSale $pointOfSale, int $nextNumber): void
    {
        $minimum = ((int) Sale::query()
            ->where('point_of_sale_id', $pointOfSale->id)
            ->max('sequence_number')) + 1;

        if ($nextNumber < $minimum) {
            throw ValidationException::withMessages([
                'receipt_next_number' => 'El siguiente numero no puede ser menor a '.$minimum.' porque ya existen ventas registradas.',
            ]);
        }
    }

    private function nextSequence(int $warehouseId): int
    {
        return ((int) PointOfSale::query()
            ->withTrashed()
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->max('sequence_number')) + 1;
    }

    private function referenceFor(Warehouse $warehouse, int $sequence): string
    {
        $branchCode = trim((string) ($warehouse->branch?->code ?: $warehouse->branch_id));
        $warehouseCode = trim((string) ($warehouse->code ?: $warehouse->id));

        return $branchCode.'-'.$warehouseCode.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    private function receiptPrefix(array $data, string $fallback): string
    {
        $prefix = trim((string) ($data['receipt_prefix'] ?? ''));

        return $prefix !== '' ? $prefix : $fallback;
    }
}
