<?php

namespace Tests\Feature\Companies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActiveCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_switch_the_active_company(): void
    {
        $origin = Company::factory()->create();
        $destination = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $origin->id]);
        $user->assignRole(Role::findOrCreate('super_admin'));

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->put(route('active-company.update'), ['company_id' => $destination->id])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('active_company_id', $destination->id);
    }

    public function test_regular_user_cannot_switch_the_active_company(): void
    {
        $origin = Company::factory()->create();
        $destination = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $origin->id]);

        $this->actingAs($user)
            ->put(route('active-company.update'), ['company_id' => $destination->id])
            ->assertForbidden();
    }
}
