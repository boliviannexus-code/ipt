<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Support\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $products
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->products->paginate($perPage);
    }

    public function active(): Collection
    {
        return $this->products->active();
    }

    public function create(array $data): Product
    {
        $image = $data['image'] ?? null;
        unset($data['image'], $data['remove_image']);

        $product = $this->products->create(CompanyContext::applyToData($this->normalize($data, true)));

        if ($image instanceof UploadedFile) {
            $this->attachProductImage($product, $image);
            $product->load('media');
        }

        Log::info('Product created', ['product_id' => $product->id]);

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $image = $data['image'] ?? null;
        $removeImage = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image']);

        if ($image instanceof UploadedFile) {
            $this->deleteProductImage($product);
            $this->attachProductImage($product, $image);
            $data['image_path'] = null;
        } elseif ($removeImage) {
            $this->deleteProductImage($product);
            $data['image_path'] = null;
        }

        $product = $this->products->update($product, CompanyContext::applyToData($this->normalize($data)));

        Log::info('Product updated', ['product_id' => $product->id]);

        return $product;
    }

    public function delete(Product $product): bool
    {
        $this->deleteProductImage($product);

        $deleted = $this->products->delete($product);

        Log::warning('Product deleted', ['product_id' => $product->id]);

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

    private function attachProductImage(Product $product, UploadedFile $image): void
    {
        $product
            ->addMedia($image)
            ->usingName($product->name)
            ->toMediaCollection(Product::IMAGE_COLLECTION, 'public');
    }

    private function deleteProductImage(Product $product): void
    {
        $product->clearMediaCollection(Product::IMAGE_COLLECTION);

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
    }
}
