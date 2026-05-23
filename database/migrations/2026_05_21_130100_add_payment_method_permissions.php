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
            'payment-methods.view',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (['admin', 'super_admin', 'manager'] as $roleName) {
            Role::query()->where('name', $roleName)->first()?->givePermissionTo($permissions);
        }

        Role::query()->where('name', 'cashier')->first()?->givePermissionTo('payment-methods.view');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            'payment-methods.view',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
        ] as $permission) {
            Permission::query()->where('name', $permission)->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
