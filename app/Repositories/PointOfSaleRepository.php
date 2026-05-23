<?php

namespace App\Repositories;

use App\Models\PointOfSale;
use App\Support\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PointOfSaleRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return PointOfSale::query()
            ->with(['branch', 'warehouse', 'company', 'users'])
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): PointOfSale
    {
        return PointOfSale::query()->create($data)->load(['branch', 'warehouse', 'company', 'users']);
    }

    public function update(PointOfSale $pointOfSale, array $data): PointOfSale
    {
        $pointOfSale->update($data);

        return $pointOfSale->refresh()->load(['branch', 'warehouse', 'company', 'users']);
    }

    public function delete(PointOfSale $pointOfSale): bool
    {
        return (bool) $pointOfSale->delete();
    }
}
