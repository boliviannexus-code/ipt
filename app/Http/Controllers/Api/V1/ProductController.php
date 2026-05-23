<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductService $products
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        return $this->successResponse(ProductResource::collection($this->products->paginate()));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->validated());

        return $this->successResponse(ProductResource::make($product), 'Producto creado correctamente.', 201);
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this->successResponse(ProductResource::make($product->load(['category', 'measurementUnit'])));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->products->update($product, $request->validated());

        return $this->successResponse(ProductResource::make($product), 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->products->delete($product);

        return $this->successResponse(null, 'Producto eliminado correctamente.');
    }
}
