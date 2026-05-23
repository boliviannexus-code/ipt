<?php

namespace Tests\Feature\Products;

use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductMeasurementUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_with_measurement_unit(): void
    {
        $user = User::factory()->create();
        $permissions = ['products.view', 'products.create'];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        $category = Category::factory()->create();
        $unit = MeasurementUnit::factory()->create([
            'name' => 'Caja',
            'abbreviation' => 'cja',
        ]);

        $this
            ->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('products.store'), [
                'name' => 'Producto por caja',
                'barcode' => '9998887776661',
                'category_id' => $category->id,
                'measurement_unit_id' => $unit->id,
                'description' => 'Producto vendido por caja.',
                'purchase_price' => 10,
                'sale_price' => 15,
                'minimum_stock' => 2,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('products', [
            'name' => 'Producto por caja',
            'measurement_unit_id' => $unit->id,
        ]);

        $product = Product::query()->where('name', 'Producto por caja')->firstOrFail();

        $this->assertTrue($product->measurementUnit->is($unit));
    }
}
