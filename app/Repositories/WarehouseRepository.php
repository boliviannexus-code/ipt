<?php

namespace App\Repositories;

use App\Models\Warehouse;
use App\Support\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class WarehouseRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Warehouse::query()
            ->with(['branch', 'company'])
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->latest()
            ->paginate($perPage);
    }

    public function active(): Collection
    {
        return Warehouse::query()
            ->with(['branch', 'company'])
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->whereHas('branch', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data)->load('branch');
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);

        return $warehouse->refresh()->load('branch');
    }

    public function delete(Warehouse $warehouse): bool
    {
        return (bool) $warehouse->delete();
    }
}
