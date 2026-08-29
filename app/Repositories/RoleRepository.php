<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleRepository
{
    public function availableToCopy(?Role $excludedRole = null): Collection
    {
        return Role::query()
            ->when($excludedRole, fn ($query) => $query->whereKeyNot($excludedRole->id))
            ->with(['permissions:id,name'])
            ->orderBy('name')
            ->get();
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Role::query()
            ->withCount('permissions')
            ->latest()
            ->paginate($perPage)
            ->through(fn (Role $role): Role => $this->withUsersCount($role));
    }

    public function create(array $data): Role
    {
        return $this->withUsersCount(Role::create($data)->load('permissions')->loadCount('permissions'));
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $this->withUsersCount($role->refresh()->load('permissions')->loadCount('permissions'));
    }

    public function delete(Role $role): bool
    {
        return (bool) $role->delete();
    }

    public function withUsersCount(Role $role): Role
    {
        $role->setAttribute('users_count', DB::table(config('permission.table_names.model_has_roles'))
            ->where('role_id', $role->id)
            ->count());

        return $role;
    }
}
