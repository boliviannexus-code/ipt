<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use App\Support\CompanyContext;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('branches.view') && CompanyContext::canOperate($user);
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->can('branches.view') && CompanyContext::belongsToUser($branch->company_id, $user);
    }

    public function create(User $user): bool
    {
        return $user->can('branches.create') && CompanyContext::canOperate($user);
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('branches.update') && CompanyContext::belongsToUser($branch->company_id, $user);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->can('branches.delete') && CompanyContext::belongsToUser($branch->company_id, $user);
    }
}
