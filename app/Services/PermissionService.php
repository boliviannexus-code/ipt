<?php

namespace App\Services;

use App\Repositories\PermissionRepository;
use App\Support\PermissionCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionService
{
    private const CRITICAL_PERMISSIONS = [
        'dashboard.view',
        'roles.view',
        'roles.create',
        'roles.edit',
        'roles.delete',
        'roles.assign-permissions',
        'permissions.view',
        'permissions.create',
        'permissions.edit',
        'permissions.delete',
        'users.view',
        'users.create',
        'users.edit',
        'users.delete',
        'users.restore',
        'users.change-password',
        'users.assign-roles',
    ];

    public function __construct(
        private readonly PermissionRepository $permissions
    ) {}

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return $this->permissions->paginate($perPage);
    }

    public function create(array $data): Permission
    {
        $data['guard_name'] = $data['guard_name'] ?? 'web';

        $permission = $this->permissions->create($data);
        $this->forgetCache();

        Log::info('Permission created', ['permission_id' => $permission->id, 'name' => $permission->name]);

        return $permission;
    }

    public function update(Permission $permission, array $data): Permission
    {
        $data['guard_name'] = $data['guard_name'] ?? $permission->guard_name;
        if (in_array($permission->name, PermissionCatalog::NAMES, true)
            && (($data['name'] ?? $permission->name) !== $permission->name || $data['guard_name'] !== $permission->guard_name)) {
            throw ValidationException::withMessages(['name' => 'Los permisos del sistema conservan su nombre y guard. Configura su acceso desde Roles.']);
        }

        $permission = $this->permissions->update($permission, $data);
        $this->forgetCache();

        Log::info('Permission updated', ['permission_id' => $permission->id, 'name' => $permission->name]);

        return $permission;
    }

    public function delete(Permission $permission): bool
    {
        if (in_array($permission->name, self::CRITICAL_PERMISSIONS, true) || in_array($permission->name, PermissionCatalog::NAMES, true)) {
            throw ValidationException::withMessages([
                'permission' => 'No puedes eliminar un permiso del sistema. Configura su acceso desde Roles.',
            ]);
        }

        $deleted = $this->permissions->delete($permission);
        $this->forgetCache();

        Log::warning('Permission deleted', ['permission_id' => $permission->id, 'name' => $permission->name]);

        return $deleted;
    }

    private function forgetCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
