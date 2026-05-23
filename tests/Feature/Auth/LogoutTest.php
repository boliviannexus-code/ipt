<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_button_posts_to_logout_route(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('dashboard.view');
        $user->givePermissionTo('dashboard.view');

        $response = $this->actingAs($user)->get('/');

        $response
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('Cerrar sesion', false)
            ->assertDontSee('form="logout-form"', false)
            ->assertDontSee('data-admin-sidebar-toggle', false);
    }
}
