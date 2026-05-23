<?php

namespace Tests\Feature\Purchases;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PurchaseListingFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_index_shows_date_supplier_and_user_filters(): void
    {
        $user = $this->userWithPurchaseView();

        $this
            ->actingAs($user)
            ->get(route('purchases.index'))
            ->assertOk()
            ->assertSee('purchase-filters')
            ->assertSee('Desde')
            ->assertSee('Hasta')
            ->assertSee('Proveedor')
            ->assertSee('Usuario')
            ->assertSee(now()->toDateString());
    }

    public function test_purchase_datatable_defaults_to_today_and_can_filter_other_days_supplier_and_user(): void
    {
        $viewer = $this->userWithPurchaseView();
        $supplier = Supplier::factory()->create(['company_id' => $viewer->company_id, 'name' => 'Proveedor Uno']);
        $otherSupplier = Supplier::factory()->create(['company_id' => $viewer->company_id, 'name' => 'Proveedor Dos']);
        $buyer = User::factory()->create(['company_id' => $viewer->company_id, 'name' => 'Comprador Uno']);
        $otherBuyer = User::factory()->create(['company_id' => $viewer->company_id, 'name' => 'Comprador Dos']);
        $branch = Branch::factory()->create(['company_id' => $viewer->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => $viewer->company_id]);

        Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $buyer->id,
            'reference' => 'TODAY-001',
            'purchase_date' => now()->toDateString(),
            'total' => 100,
        ]);
        Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $buyer->id,
            'reference' => 'OLD-001',
            'purchase_date' => now()->subDay()->toDateString(),
            'total' => 50,
        ]);
        Purchase::factory()->create([
            'supplier_id' => $otherSupplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $otherBuyer->id,
            'reference' => 'OLD-002',
            'purchase_date' => now()->subDay()->toDateString(),
            'total' => 75,
        ]);

        $this
            ->actingAs($viewer)
            ->getJson(route('datatables.purchases', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertOk()
            ->assertJsonFragment(['reference' => 'TODAY-001'])
            ->assertJsonMissing(['reference' => 'OLD-001']);

        $this
            ->actingAs($viewer)
            ->getJson(route('datatables.purchases', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'date_from' => now()->subDay()->toDateString(),
                'date_to' => now()->subDay()->toDateString(),
                'supplier_id' => $supplier->id,
                'user_id' => $buyer->id,
            ]))
            ->assertOk()
            ->assertJsonFragment(['reference' => 'OLD-001'])
            ->assertJsonMissing(['reference' => 'TODAY-001'])
            ->assertJsonMissing(['reference' => 'OLD-002']);
    }

    private function userWithPurchaseView(): User
    {
        Permission::findOrCreate('purchases.view');

        $user = User::factory()->create(['company_id' => Company::factory()]);
        $user->givePermissionTo('purchases.view');

        return $user;
    }
}
