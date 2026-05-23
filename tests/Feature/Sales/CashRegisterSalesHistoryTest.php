<?php

namespace Tests\Feature\Sales;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashRegisterExpense;
use App\Models\Company;
use App\Models\PointOfSale;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CashRegisterSalesHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_index_groups_registered_sales_by_cash_register(): void
    {
        $user = $this->userWithSalesAccess();
        [$pointOfSale, $branch, $warehouse] = $this->assignedPointOfSale($user);
        $openRegister = CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'opening_amount' => 20,
            'status' => 'open',
        ]);
        $closedRegister = CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'opening_amount' => 50,
            'closing_amount' => 85,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $this->saleFor($openRegister, $branch, $warehouse, $user, 'POS-OPEN', 30);
        $this->saleFor($closedRegister, $branch, $warehouse, $user, 'POS-CLOSED', 35);

        $this
            ->actingAs($user)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertSee('Cajas y ventas')
            ->assertSee('Abierta')
            ->assertSee('Cerrada')
            ->assertSee('30.00')
            ->assertSee('35.00')
            ->assertSee(route('sales.cash-registers.show', $openRegister));
    }

    public function test_cash_register_detail_shows_sales_payments_and_expenses_for_that_register(): void
    {
        $user = $this->userWithSalesAccess();
        [$pointOfSale, $branch, $warehouse] = $this->assignedPointOfSale($user);
        $cashRegister = CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'opening_amount' => 10,
            'closing_amount' => 32,
            'status' => 'closed',
            'closed_at' => now(),
        ]);
        $sale = $this->saleFor($cashRegister, $branch, $warehouse, $user, 'POS-DETAIL', 25);
        $sale->payments()->create([
            'payment_method_name' => 'Efectivo',
            'amount' => 20,
        ]);
        $sale->payments()->create([
            'payment_method_name' => 'QR',
            'amount' => 5,
        ]);
        CashRegisterExpense::query()->create([
            'cash_register_id' => $cashRegister->id,
            'point_of_sale_id' => $pointOfSale->id,
            'user_id' => $user->id,
            'responsible_name' => 'Maria Caja',
            'detail' => 'Compra de bolsas',
            'amount' => 3,
            'spent_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->get(route('sales.cash-registers.show', $cashRegister))
            ->assertOk()
            ->assertSee('POS-DETAIL')
            ->assertSee('Efectivo')
            ->assertSee('QR')
            ->assertSee('Compra de bolsas')
            ->assertSee('27.00');
    }

    private function saleFor(CashRegister $cashRegister, Branch $branch, Warehouse $warehouse, User $user, string $receipt, float $total): Sale
    {
        return Sale::query()->create([
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'cash_register_id' => $cashRegister->id,
            'point_of_sale_id' => $cashRegister->point_of_sale_id,
            'receipt_number' => $receipt,
            'sequence_number' => random_int(1, 999),
            'sale_date' => now(),
            'subtotal' => $total,
            'discount' => 0,
            'tax' => 0,
            'total' => $total,
            'status' => 'completed',
        ]);
    }

    private function assignedPointOfSale(User $user): array
    {
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => $user->company_id]);
        $pointOfSale = PointOfSale::factory()->for($branch)->create([
            'company_id' => $user->company_id,
            'warehouse_id' => $warehouse->id,
        ]);
        $pointOfSale->users()->sync([$user->id]);

        return [$pointOfSale, $branch, $warehouse];
    }

    private function userWithSalesAccess(): User
    {
        Permission::findOrCreate('sales.view');

        $user = User::factory()->create(['company_id' => Company::factory()]);
        $user->givePermissionTo('sales.view');

        return $user;
    }
}
