<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserAccessRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivating_user_revokes_sessions_tokens_and_remember_token(): void
    {
        $user = User::factory()->create(['is_active' => true, 'remember_token' => 'remember-me']);
        $otherUser = User::factory()->create(['is_active' => true]);
        $targetToken = $user->createToken('target-device');
        $otherToken = $otherUser->createToken('other-device');
        $this->createSession('target-session', $user);
        $this->createSession('other-session', $otherUser);

        app(UserService::class)->toggleStatus($user);

        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $targetToken->accessToken->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
        $this->assertNull($user->refresh()->remember_token);
    }

    public function test_changing_password_revokes_all_existing_access(): void
    {
        $user = User::factory()->create(['remember_token' => 'remember-me']);
        $token = $user->createToken('existing-device');
        $this->createSession('existing-session', $user);

        app(UserService::class)->changePassword($user, 'new-secure-password');

        $this->assertDatabaseMissing('sessions', ['id' => 'existing-session']);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertNull($user->refresh()->remember_token);
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
    }

    public function test_deactivating_user_from_edit_flow_also_revokes_access(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('existing-device');
        $this->createSession('existing-session', $user);

        app(UserService::class)->update($user, ['is_active' => false]);

        $this->assertDatabaseMissing('sessions', ['id' => 'existing-session']);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertFalse($user->refresh()->is_active);
    }

    public function test_user_is_logged_out_when_changing_own_password(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        Permission::findOrCreate('users.change-password');
        $user->givePermissionTo('users.change-password');

        $response = $this
            ->actingAs($user)
            ->patch(route('users.change-password', $user), [
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Contraseña actualizada correctamente.');
        $this->assertGuest();
    }

    private function createSession(string $id, User $user): void
    {
        DB::table(config('session.table', 'sessions'))->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);
    }
}
