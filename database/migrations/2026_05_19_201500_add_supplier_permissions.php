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
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (['admin', 'super_admin'] as $roleName) {
            Role::query()->where('name', $roleName)->first()?->givePermissionTo($permissions);
        }

        foreach (['manager', 'warehouse', 'inventory_manager'] as $roleName) {
            Role::query()->where('name', $roleName)->first()?->givePermissionTo('suppliers.view');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::query()->whereIn('name', [
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
