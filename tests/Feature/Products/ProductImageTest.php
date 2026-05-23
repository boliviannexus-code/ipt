<?php

namespace Tests\Feature\Products;

use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_image_is_stored_as_optimized_webp(): void
    {
        Storage::fake('public');

        $user = $this->userWithPermissions(['products.view', 'products.create']);
        $category = Category::factory()->create();
        $unit = MeasurementUnit::factory()->create();

        $this
            ->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('products.store'), [
                'name' => 'Producto con imagen',
                'barcode' => '1234567890123',
                'category_id' => $category->id,
                'measurement_unit_id' => $unit->id,
                'description' => 'Imagen principal optimizada.',
                'image' => UploadedFile::fake()->image('producto.jpg', 900, 700)->size(500),
                'purchase_price' => 10,
                'sale_price' => 15,
                'minimum_stock' => 2,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $product = Product::query()->where('name', 'Producto con imagen')->firstOrFail();
        $media = $product->getFirstMedia(Product::IMAGE_COLLECTION);

        $this->assertNull($product->image_path);
        $this->assertNotNull($media);
        $this->assertSame(Product::IMAGE_COLLECTION, $media->collection_name);
        $this->assertSame('public', $media->disk);
        $this->assertStringEndsWith('.webp', $media->getPath(Product::IMAGE_CONVERSION));
        $this->assertFileExists($media->getPath(Product::IMAGE_CONVERSION));

        $size = getimagesize($media->getPath(Product::IMAGE_CONVERSION));
        $this->assertSame([600, 600], [$size[0], $size[1]]);
    }

    public function test_product_image_can_be_replaced_and_removed(): void
    {
        Storage::fake('public');

        $user = $this->userWithPermissions(['products.update']);
        $product = Product::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('products.update', $product), [
                '_method' => 'PUT',
                'name' => $product->name,
                'barcode' => $product->barcode,
                'category_id' => $product->category_id,
                'measurement_unit_id' => $product->measurement_unit_id,
                'description' => $product->description,
                'image' => UploadedFile::fake()->image('producto.png', 400, 400)->size(250),
                'purchase_price' => $product->purchase_price,
                'sale_price' => $product->sale_price,
                'minimum_stock' => $product->minimum_stock,
                'is_active' => true,
            ])
            ->assertRedirect(route('products.index'));

        $firstMedia = $product->refresh()->getFirstMedia(Product::IMAGE_COLLECTION);
        $this->assertNotNull($firstMedia);
        $firstPath = $firstMedia->getPath();
        $firstConversionPath = $firstMedia->getPath(Product::IMAGE_CONVERSION);
        $this->assertFileExists($firstPath);
        $this->assertFileExists($firstConversionPath);

        $this
            ->actingAs($user)
            ->post(route('products.update', $product), [
                '_method' => 'PUT',
                'name' => $product->name,
                'barcode' => $product->barcode,
                'category_id' => $product->category_id,
                'measurement_unit_id' => $product->measurement_unit_id,
                'description' => $product->description,
                'remove_image' => true,
                'purchase_price' => $product->purchase_price,
                'sale_price' => $product->sale_price,
                'minimum_stock' => $product->minimum_stock,
                'is_active' => true,
            ])
            ->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertFileDoesNotExist($firstPath);
        $this->assertFileDoesNotExist($firstConversionPath);
        $this->assertNull($product->image_path);
        $this->assertFalse($product->hasMedia(Product::IMAGE_COLLECTION));
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }
}
