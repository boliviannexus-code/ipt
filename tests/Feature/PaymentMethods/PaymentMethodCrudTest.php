<?php

namespace Tests\Feature\PaymentMethods;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentMethodCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_payment_methods(): void
    {
        $user = User::factory()->create();
        $permissions = [
            'payment-methods.view',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        $this
            ->actingAs($user)
            ->get(route('payment-methods.index'))
            ->assertOk();

        $this
            ->actingAs($user)
            ->postJson(route('payment-methods.store'), [
                'name' => 'Cheque',
                'is_active' => true,
            ])
            ->assertCreated();

        $paymentMethod = PaymentMethod::query()->where('name', 'Cheque')->firstOrFail();

        $this
            ->actingAs($user)
            ->putJson(route('payment-methods.update', $paymentMethod), [
                'name' => 'Cheque bancario',
                'is_active' => false,
            ])
            ->assertOk();

        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'name' => 'Cheque bancario',
            'is_active' => false,
        ]);

        $this
            ->actingAs($user)
            ->delete(route('payment-methods.destroy', $paymentMethod))
            ->assertRedirect(route('payment-methods.index'));

        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }
}
