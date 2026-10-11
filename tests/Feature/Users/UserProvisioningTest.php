<?php

namespace Tests\Feature\Users;

use App\Models\Area;
use App\Models\Company;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_created_from_personnel_with_manually_assigned_temporary_password(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('users.create');
        $actor->givePermissionTo('users.create');
        $area = Area::create(['company_id' => $company->id, 'name' => 'Tecnología', 'is_active' => true]);
        $position = Position::create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Director', 'is_active' => true]);
        $personnel = Personnel::create(['company_id' => $company->id, 'position_id' => $position->id, 'first_name' => 'Álvaro', 'paternal_surname' => 'Pacheco', 'identity_document' => '8324984', 'birth_date' => '1990-01-01', 'email' => 'alvaro@example.com', 'is_active' => true]);

        $this->actingAs($actor)->post(route('users.store'), [
            'personnel_id' => $personnel->id,
            'password' => 'ClaveTemporal123!',
            'password_confirmation' => 'ClaveTemporal123!',
            'is_active' => '1',
        ])->assertRedirect(route('users.index'));

        $user = User::query()->where('personnel_id', $personnel->id)->firstOrFail();
        $this->assertSame('Álvaro Pacheco', $user->name);
        $this->assertSame('alvaro@example.com', $user->email);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('ClaveTemporal123!', $user->password));
    }

    public function test_admin_can_reset_user_password_manually(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        $target = User::factory()->create(['company_id' => $company->id, 'must_change_password' => false]);
        $previousPassword = $target->password;
        Permission::findOrCreate('users.change-password');
        $actor->givePermissionTo('users.change-password');

        $this->actingAs($actor)
            ->patchJson(route('users.reset-password', $target), [
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $target->refresh();
        $this->assertTrue($target->must_change_password);
        $this->assertNotSame($previousPassword, $target->password);
        $this->assertTrue(Hash::check('NuevaClave123!', $target->password));
    }

    public function test_manual_password_and_confirmation_are_required_when_creating_a_user(): void
    {
        $company = Company::factory()->create();
        $actor = User::factory()->create(['company_id' => $company->id]);
        Permission::findOrCreate('users.create');
        $actor->givePermissionTo('users.create');
        $area = Area::create(['company_id' => $company->id, 'name' => 'Tecnología', 'is_active' => true]);
        $position = Position::create(['company_id' => $company->id, 'area_id' => $area->id, 'name' => 'Director', 'is_active' => true]);
        $personnel = Personnel::create(['company_id' => $company->id, 'position_id' => $position->id, 'first_name' => 'Ana', 'paternal_surname' => 'Pérez', 'identity_document' => '1234567', 'birth_date' => '1990-01-01', 'email' => 'ana@example.com', 'is_active' => true]);

        $this->actingAs($actor)->post(route('users.store'), [
            'personnel_id' => $personnel->id,
        ])->assertSessionHasErrors(['password']);

        $this->assertDatabaseMissing('users', ['personnel_id' => $personnel->id]);
    }
}
