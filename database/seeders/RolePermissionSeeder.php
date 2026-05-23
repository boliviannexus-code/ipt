<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.restore',
            'users.change-password',
            'users.assign-roles',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.assign-permissions',
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
            'companies.view',
            'companies.create',
            'companies.update',
            'companies.delete',
            'audits.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        Permission::query()
            ->whereNotIn('name', $permissions)
            ->delete();

        Role::findOrCreate('super_admin')->syncPermissions($permissions);
        Role::findOrCreate('admin')->syncPermissions($permissions);

        Role::findOrCreate('manager')->syncPermissions([
            'dashboard.view',
            'users.view',
            'roles.view',
            'permissions.view',
            'companies.view',
            'companies.create',
            'companies.update',
            'audits.view',
        ]);

        Role::findOrCreate('viewer')->syncPermissions([
            'dashboard.view',
            'users.view',
            'roles.view',
            'permissions.view',
            'companies.view',
            'audits.view',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
