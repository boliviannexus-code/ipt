<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $view = Permission::findOrCreate('academic-control.view');
        $manage = Permission::findOrCreate('academic-control.manage');

        Role::query()->whereIn('name', ['super_admin', 'admin', 'manager'])->each(
            fn (Role $role) => $role->givePermissionTo([$view, $manage])
        );
        Role::query()->where('name', 'viewer')->each(
            fn (Role $role) => $role->givePermissionTo($view)
        );
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()->whereIn('name', ['academic-control.view', 'academic-control.manage'])->get()->each(function (Permission $permission): void {
            $permission->roles()->detach();
            $permission->users()->detach();
            $permission->delete();
        });
    }
};
