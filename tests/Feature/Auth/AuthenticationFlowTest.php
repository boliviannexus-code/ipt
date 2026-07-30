<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\LoginRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_contains_accessible_and_helpful_controls(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertSee('Inicia sesión')
            ->assertSee('Correo electrónico')
            ->assertSee('Contraseña')
            ->assertSee('Mantener mi sesión')
            ->assertSee('data-password-toggle', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_user_can_log_in_and_session_identifier_is_regenerated(): void
    {
        $user = User::factory()->create();
        $this->withSession(['login-marker' => true]);
        $previousSessionId = session()->getId();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSessionId, session()->getId());
    }

    public function test_login_redirects_to_originally_requested_page(): void
    {
        $user = User::factory()->create();

        $this->get(route('users.index'))->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('users.index'));
    }

    public function test_remember_option_creates_recaller_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertCookie(Auth::guard('web')->getRecallerName());
    }

    public function test_invalid_credentials_are_rejected_without_forgetting_email(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
            'remember' => '1',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', $user->email)
            ->assertSessionHasInput('remember', '1');
        $this->assertNull(session()->getOldInput('password'));
        $this->assertGuest();
    }

    public function test_login_requires_valid_email_and_password(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'not-an-email',
                'password' => '',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_authenticated_user_cannot_return_to_login_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_web_login_is_temporarily_limited_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= LoginRateLimiter::MAX_ATTEMPTS; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertRedirect();
        }

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('email')
            ->assertHeader('X-Login-RateLimit-Limit', (string) LoginRateLimiter::MAX_ATTEMPTS);
        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    public function test_successful_login_clears_previous_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 1; $attempt < LoginRateLimiter::MAX_ATTEMPTS; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ]);
        }

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $request = request()->merge(['email' => $user->email]);
        $this->assertEquals(0, RateLimiter::attempts(app(LoginRateLimiter::class)->key($request)));
    }

    public function test_api_login_returns_token_for_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('api.v1.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email']]]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_api_login_returns_validation_errors_for_missing_fields(): void
    {
        $this->postJson(route('api.v1.login'), [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Los datos enviados no son validos.')
            ->assertJsonValidationErrors(['email', 'password'], 'data');
    }

    public function test_api_login_is_temporarily_limited_after_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= LoginRateLimiter::MAX_ATTEMPTS; $attempt++) {
            $this->postJson(route('api.v1.login'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertUnprocessable();
        }

        $this->postJson(route('api.v1.login'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])
            ->assertTooManyRequests()
            ->assertHeader('X-Login-RateLimit-Limit', (string) LoginRateLimiter::MAX_ATTEMPTS)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['data' => ['retry_after']]);
    }

    public function test_api_logout_revokes_only_current_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('current-device');
        $otherToken = $user->createToken('other-device');

        $this->withToken($currentToken->plainTextToken)
            ->postJson(route('api.v1.logout'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    }
}
