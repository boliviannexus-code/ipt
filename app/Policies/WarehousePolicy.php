<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;
use App\Support\CompanyContext;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('warehouses.view') && CompanyContext::canOperate($user);
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.view') && CompanyContext::belongsToUser($warehouse->company_id, $user);
    }

    public function create(User $user): bool
    {
        return $user->can('warehouses.create') && CompanyContext::canOperate($user);
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.update') && CompanyContext::belongsToUser($warehouse->company_id, $user);
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.delete') && CompanyContext::belongsToUser($warehouse->company_id, $user);
    }
}
