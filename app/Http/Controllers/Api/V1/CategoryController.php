<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CategoryService $categories
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        return $this->successResponse(CategoryResource::collection($this->categories->paginate()));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create($request->validated());

        return $this->successResponse(CategoryResource::make($category), 'Categoria creada correctamente.', 201);
    }

    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return $this->successResponse(CategoryResource::make($category));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $category = $this->categories->update($category, $request->validated());

        return $this->successResponse(CategoryResource::make($category), 'Categoria actualizada correctamente.');
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->categories->delete($category);

        return $this->successResponse(null, 'Categoria eliminada correctamente.');
    }
}
