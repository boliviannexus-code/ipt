<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Support\CompanyContext;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('categories.view') && CompanyContext::canOperate($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('categories.view') && CompanyContext::belongsToUser($category->company_id, $user);
    }

    public function create(User $user): bool
    {
        return $user->can('categories.create') && CompanyContext::canOperate($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.update') && CompanyContext::belongsToUser($category->company_id, $user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.delete') && CompanyContext::belongsToUser($category->company_id, $user);
    }
}
