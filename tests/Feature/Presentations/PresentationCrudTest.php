<?php

namespace Tests\Feature\Presentations;

use App\Models\Presentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PresentationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_presentations(): void
    {
        $user = User::factory()->create();
        $permissions = [
            'product-presentations.view',
            'product-presentations.create',
            'product-presentations.update',
            'product-presentations.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        $this
            ->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('product-presentations.store'), [
                'name' => 'Caja x 30',
                'units_per_package' => 30,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $presentation = Presentation::query()->where('name', 'Caja x 30')->firstOrFail();

        $this
            ->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->putJson(route('product-presentations.update', $presentation), [
                'name' => 'Caja x 30 actualizada',
                'units_per_package' => 30,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('presentations', [
            'id' => $presentation->id,
            'name' => 'Caja x 30 actualizada',
            'is_active' => false,
        ]);
    }
}
