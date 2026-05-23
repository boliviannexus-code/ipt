<?php

namespace Tests\Feature\MeasurementUnits;

use App\Models\MeasurementUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MeasurementUnitCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_measurement_units(): void
    {
        $user = User::factory()->create();
        $permissions = [
            'measurement-units.view',
            'measurement-units.create',
            'measurement-units.update',
            'measurement-units.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        $this
            ->actingAs($user)
            ->get(route('measurement-units.index'))
            ->assertOk()
            ->assertSee('Unidades de medida');

        $this
            ->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('measurement-units.store'), [
                'name' => 'Docena',
                'abbreviation' => 'doc',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $unit = MeasurementUnit::query()->where('abbreviation', 'doc')->firstOrFail();

        $this
            ->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->putJson(route('measurement-units.update', $unit), [
                'name' => 'Docena actualizada',
                'abbreviation' => 'doca',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('measurement_units', [
            'id' => $unit->id,
            'name' => 'Docena actualizada',
            'abbreviation' => 'doca',
            'is_active' => false,
        ]);

        $this
            ->actingAs($user)
            ->delete(route('measurement-units.destroy', $unit))
            ->assertRedirect(route('measurement-units.index'));

        $this->assertSoftDeleted('measurement_units', [
            'id' => $unit->id,
        ]);
    }
}
