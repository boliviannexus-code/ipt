<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Support\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categories
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->categories->paginate($perPage);
    }

    public function active(): Collection
    {
        return $this->categories->active();
    }

    public function create(array $data): Category
    {
        $category = $this->categories->create(CompanyContext::applyToData($this->normalize($data, true)));

        Log::info('Category created', ['category_id' => $category->id]);

        return $category;
    }

    public function update(Category $category, array $data): Category
    {
        $category = $this->categories->update($category, CompanyContext::applyToData($this->normalize($data)));

        Log::info('Category updated', ['category_id' => $category->id]);

        return $category;
    }

    public function delete(Category $category): bool
    {
        $deleted = $this->categories->delete($category);

        Log::warning('Category deleted', ['category_id' => $category->id]);

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
