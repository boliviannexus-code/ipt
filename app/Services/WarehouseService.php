<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\Branch;
use App\Repositories\WarehouseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepository $warehouses
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->warehouses->paginate($perPage);
    }

    public function active(): Collection
    {
        return $this->warehouses->active();
    }

    public function create(array $data): Warehouse
    {
        $branch = Branch::query()->findOrFail((int) $data['branch_id']);
        $data['company_id'] = $branch->company_id;

        $warehouse = $this->warehouses->create($this->normalize($data, true));

        Log::info('Warehouse created', ['warehouse_id' => $warehouse->id]);

        return $warehouse;
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $branch = Branch::query()->findOrFail((int) $data['branch_id']);
        $data['company_id'] = $branch->company_id;

        $warehouse = $this->warehouses->update($warehouse, $this->normalize($data));

        Log::info('Warehouse updated', ['warehouse_id' => $warehouse->id]);

        return $warehouse;
    }

    public function delete(Warehouse $warehouse): bool
    {
        $deleted = $this->warehouses->delete($warehouse);

        Log::warning('Warehouse deleted', ['warehouse_id' => $warehouse->id]);

        return $deleted;
    }

    private function normalize(array $data, ?bool $defaultActive = null): array
    {
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        } elseif ($defaultActive !== null) {
            $data['is_active'] = $defaultActive;
        }

        return $data;
    }
}
