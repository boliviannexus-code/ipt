<?php

namespace App\Policies;

use App\Models\PointOfSale;
use App\Models\User;
use App\Support\CompanyContext;

class PointOfSalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('point-of-sales.view') && CompanyContext::canOperate($user);
    }

    public function view(User $user, PointOfSale $pointOfSale): bool
    {
        return $user->can('point-of-sales.view') && CompanyContext::belongsToUser($pointOfSale->company_id, $user);
    }

    public function create(User $user): bool
    {
        return $user->can('point-of-sales.create') && CompanyContext::canOperate($user);
    }

    public function update(User $user, PointOfSale $pointOfSale): bool
    {
        return $user->can('point-of-sales.update') && CompanyContext::belongsToUser($pointOfSale->company_id, $user);
    }

    public function delete(User $user, PointOfSale $pointOfSale): bool
    {
        return $user->can('point-of-sales.delete') && CompanyContext::belongsToUser($pointOfSale->company_id, $user);
    }
}
