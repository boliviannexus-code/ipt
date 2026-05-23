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
            'product-presentations.view',
            'product-presentations.create',
            'product-presentations.update',
            'product-presentations.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (['admin', 'super_admin', 'manager', 'warehouse', 'inventory_manager'] as $roleName) {
            Role::query()->where('name', $roleName)->first()?->givePermissionTo($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::query()->whereIn('name', [
            'product-presentations.view',
            'product-presentations.create',
            'product-presentations.update',
            'product-presentations.delete',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
