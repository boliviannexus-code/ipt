<?php

namespace Tests\Feature\Users;

use App\Models\Area;
use App\Models\Company;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use App\Notifications\TemporaryUserPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_created_from_personnel_with_temporary_password_notification(): void
    {
        Notification::fake();
        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('users.create');
        $actor->givePermissionTo('users.create');
        $area = Area::create(['company_id' => $company->id, 'name' => 'Tecnología', 'is_active' => true]);
        $position = Position::create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Director', 'is_active' => true]);
        $personnel = Personnel::create(['company_id' => $company->id, 'position_id' => $position->id, 'first_name' => 'Álvaro', 'paternal_surname' => 'Pacheco', 'identity_document' => '8324984', 'birth_date' => '1990-01-01', 'email' => 'alvaro@example.com', 'is_active' => true]);

        $this->actingAs($actor)->post(route('users.store'), [
            'personnel_id' => $personnel->id,
            'is_active' => '1',
        ])->assertRedirect(route('users.index'));

        $user = User::query()->where('personnel_id', $personnel->id)->firstOrFail();
        $this->assertSame('Álvaro Pacheco', $user->name);
        $this->assertSame('alvaro@example.com', $user->email);
        $this->assertTrue($user->must_change_password);
        Notification::assertSentTo($user, TemporaryUserPasswordNotification::class);
    }

    public function test_admin_can_reset_user_password_and_send_new_temporary_credentials(): void
    {
        Notification::fake();
        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        $target = User::factory()->create(['company_id' => $company->id, 'must_change_password' => false]);
        $previousPassword = $target->password;
        Permission::findOrCreate('users.change-password');
        $actor->givePermissionTo('users.change-password');

        $this->actingAs($actor)
            ->patchJson(route('users.reset-password', $target))
            ->assertOk()
            ->assertJsonPath('success', true);

        $target->refresh();
        $this->assertTrue($target->must_change_password);
        $this->assertNotSame($previousPassword, $target->password);
        $this->assertFalse(Hash::check('password', $target->password));
        Notification::assertSentTo($target, TemporaryUserPasswordNotification::class);
    }
}
