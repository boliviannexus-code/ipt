<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'branches.view',
            'branches.create',
            'branches.update',
            'branches.delete',
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (['admin', 'super_admin'] as $roleName) {
            Role::query()
                ->where('name', $roleName)
                ->first()
                ?->givePermissionTo($permissions);
        }

        Role::query()->where('name', 'manager')->first()?->givePermissionTo([
            'branches.view',
            'branches.create',
            'branches.update',
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
        ]);

        Role::query()->where('name', 'warehouse')->first()?->givePermissionTo([
            'branches.view',
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
        ]);

        Role::query()->where('name', 'inventory_manager')->first()?->givePermissionTo([
            'branches.view',
            'branches.create',
            'branches.update',
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
        ]);

        Role::query()->where('name', 'cashier')->first()?->givePermissionTo([
            'branches.view',
            'warehouses.view',
        ]);

        Role::query()->where('name', 'viewer')->first()?->givePermissionTo([
            'branches.view',
            'warehouses.view',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::query()
            ->whereIn('name', [
                'branches.view',
                'branches.create',
                'branches.update',
                'branches.delete',
                'warehouses.view',
                'warehouses.create',
                'warehouses.update',
                'warehouses.delete',
            ])
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
