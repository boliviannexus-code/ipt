<?php

namespace Tests\Feature\Pos;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\PointOfSale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpenCashRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_cash_register_for_assigned_point_of_sale(): void
    {
        $user = $this->userWithPosAccess();
        [$pointOfSale, $branch] = $this->pointOfSaleFor($user, [
            'code' => 'PV-OPEN-001',
        ]);
        $pointOfSale->users()->sync([$user->id]);

        $this
            ->actingAs($user)
            ->post(route('pos.open'), [
                'point_of_sale_id' => $pointOfSale->id,
                'opening_amount' => 150.25,
            ])
            ->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('cash_registers', [
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'opening_amount' => '150.25',
            'status' => 'open',
        ]);
    }

    public function test_user_cannot_open_cash_register_for_unassigned_point_of_sale(): void
    {
        $user = $this->userWithPosAccess();
        [$pointOfSale] = $this->pointOfSaleFor($user);

        $this
            ->actingAs($user)
            ->post(route('pos.open'), [
                'point_of_sale_id' => $pointOfSale->id,
                'opening_amount' => 10,
            ])
            ->assertSessionHasErrors('point_of_sale_id');
    }

    public function test_user_cannot_open_two_cash_registers(): void
    {
        $user = $this->userWithPosAccess();
        [$pointOfSale] = $this->pointOfSaleFor($user);
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $pointOfSale->branch_id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);

        [$otherPointOfSale] = $this->pointOfSaleFor($user);
        $otherPointOfSale->users()->sync([$user->id]);

        $this
            ->actingAs($user)
            ->post(route('pos.open'), [
                'point_of_sale_id' => $otherPointOfSale->id,
                'opening_amount' => 10,
            ])
            ->assertSessionHasErrors('point_of_sale_id');
    }

    public function test_point_of_sale_cannot_have_two_open_cash_registers(): void
    {
        $firstUser = $this->userWithPosAccess();
        $secondUser = $this->userWithPosAccess((int) $firstUser->company_id);
        [$pointOfSale] = $this->pointOfSaleFor($firstUser);
        $pointOfSale->users()->sync([$firstUser->id, $secondUser->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $pointOfSale->branch_id,
            'user_id' => $firstUser->id,
            'status' => 'open',
        ]);

        $this
            ->actingAs($secondUser)
            ->post(route('pos.open'), [
                'point_of_sale_id' => $pointOfSale->id,
                'opening_amount' => 10,
            ])
            ->assertSessionHasErrors('point_of_sale_id');
    }

    public function test_pos_screen_lists_only_assigned_points_for_regular_user(): void
    {
        $user = $this->userWithPosAccess();
        [$assigned] = $this->pointOfSaleFor($user, ['name' => 'POS asignado']);
        [$unassigned] = $this->pointOfSaleFor($user, ['name' => 'POS no asignado']);
        $assigned->users()->sync([$user->id]);

        $this
            ->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('POS asignado')
            ->assertDontSee('POS no asignado');
    }

    public function test_admin_also_only_sees_assigned_points_of_sale(): void
    {
        $user = $this->userWithPosAccess();
        $user->assignRole('admin');
        [$assigned] = $this->pointOfSaleFor($user, ['name' => 'POS admin asignado']);
        [$unassigned] = $this->pointOfSaleFor($user, ['name' => 'POS admin no asignado']);
        $assigned->users()->sync([$user->id]);

        $this
            ->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('POS admin asignado')
            ->assertDontSee('POS admin no asignado');
    }

    public function test_admin_cannot_open_cash_register_for_unassigned_point_of_sale(): void
    {
        $user = $this->userWithPosAccess();
        $user->assignRole('admin');
        [$pointOfSale] = $this->pointOfSaleFor($user);

        $this
            ->actingAs($user)
            ->post(route('pos.open'), [
                'point_of_sale_id' => $pointOfSale->id,
                'opening_amount' => 10,
            ])
            ->assertSessionHasErrors('point_of_sale_id');
    }

    public function test_open_register_is_hidden_when_user_is_removed_from_point_of_sale(): void
    {
        $user = $this->userWithPosAccess();
        [$pointOfSale] = $this->pointOfSaleFor($user, ['name' => 'Caja que ya no debo ver']);
        $pointOfSale->users()->sync([$user->id]);

        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $pointOfSale->branch_id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);

        $pointOfSale->users()->detach($user->id);

        $this
            ->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertDontSee('Caja que ya no debo ver')
            ->assertSee('Abrir caja');

        $this
            ->actingAs($user)
            ->post(route('pos.close'), [
                'closing_amount' => 0,
            ])
            ->assertSessionHasErrors('closing_amount', null, 'cashClose');
    }

    public function test_open_register_is_hidden_when_user_changes_company(): void
    {
        $oldCompany = Company::factory()->create();
        $newCompany = Company::factory()->create();
        $user = $this->userWithPosAccess($oldCompany->id);
        [$oldPointOfSale] = $this->pointOfSaleFor($user, ['name' => 'Caja empresa anterior']);
        $oldPointOfSale->users()->sync([$user->id]);

        CashRegister::factory()->create([
            'point_of_sale_id' => $oldPointOfSale->id,
            'branch_id' => $oldPointOfSale->branch_id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);

        $user->update(['company_id' => $newCompany->id]);
        [$newPointOfSale] = $this->pointOfSaleFor($user, ['name' => 'Caja empresa nueva']);
        $newPointOfSale->users()->sync([$user->id]);

        $this
            ->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertDontSee('Caja empresa anterior')
            ->assertSee('Caja empresa nueva')
            ->assertSee('Abrir caja');
    }

    private function pointOfSaleFor(User $user, array $attributes = []): array
    {
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create(['company_id' => $user->company_id]);
        $pointOfSale = PointOfSale::factory()->forWarehouse($warehouse->id)->create($attributes);

        return [$pointOfSale, $branch, $warehouse];
    }

    private function userWithPosAccess(?int $companyId = null): User
    {
        Permission::findOrCreate('pos.access');
        Role::findOrCreate('admin');

        $companyId ??= Company::factory()->create()->id;
        $user = User::factory()->create(['company_id' => $companyId]);
        $user->givePermissionTo('pos.access');

        return $user;
    }
}
