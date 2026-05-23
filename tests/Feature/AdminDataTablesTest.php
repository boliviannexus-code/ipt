<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminDataTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_datatable_endpoints_return_server_side_payloads(): void
    {
        $user = User::factory()->create();
        $permissions = [
            'products.view',
            'product-presentations.view',
            'categories.view',
            'measurement-units.view',
            'payment-methods.view',
            'suppliers.view',
            'purchases.view',
            'sales.view',
            'inventory.view',
            'inventory.movements',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        Product::factory()
            ->for(Category::factory())
            ->for(MeasurementUnit::factory())
            ->create();

        foreach ([
            'datatables.products',
            'datatables.product-presentations',
            'datatables.categories',
            'datatables.measurement-units',
            'datatables.payment-methods',
            'datatables.suppliers',
            'datatables.purchases',
            'datatables.sales',
            'datatables.stock',
            'datatables.kardex',
        ] as $route) {
            $this
                ->actingAs($user)
                ->getJson(route($route, [
                    'draw' => 1,
                    'start' => 0,
                    'length' => 10,
                ]))
                ->assertOk()
                ->assertJsonStructure([
                    'draw',
                    'recordsTotal',
                    'recordsFiltered',
                    'data',
                ]);
        }
    }
}
