<?php

namespace App\Repositories;

use App\Models\Product;
use App\Support\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'measurementUnit', 'media'])
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->latest()
            ->paginate($perPage);
    }

    public function active(): Collection
    {
        return Product::query()
            ->with('media')
            ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Product
    {
        return Product::create($data)->load(['category', 'measurementUnit', 'media']);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh()->load(['category', 'measurementUnit', 'media']);
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }
}
