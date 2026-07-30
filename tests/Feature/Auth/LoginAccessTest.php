<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_log_in_without_a_company(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_from_active_company_can_log_in(): void
    {
        $company = Company::factory()->create(['is_active' => true]);
        $user = User::factory()->for($company)->create(['is_active' => true]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_from_inactive_company_cannot_log_in(): void
    {
        $company = Company::factory()->create(['is_active' => false]);
        $user = User::factory()->for($company)->create(['is_active' => true]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_existing_web_session_is_closed_when_user_becomes_inactive(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->update(['is_active' => false]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_existing_web_session_is_closed_when_company_becomes_inactive(): void
    {
        $company = Company::factory()->create(['is_active' => false]);
        $user = User::factory()->for($company)->create(['is_active' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_company_user_cannot_obtain_an_api_token(): void
    {
        $company = Company::factory()->create(['is_active' => false]);
        $user = User::factory()->for($company)->create(['is_active' => true]);

        $response = $this->postJson(route('api.v1.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_existing_api_access_is_blocked_when_user_becomes_inactive(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $token = $user->createToken('inactive-device');

        $this->withToken($token->plainTextToken)
            ->postJson(route('api.v1.logout'))
            ->assertForbidden()
            ->assertJsonPath('message', 'La cuenta no esta habilitada.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }
}
