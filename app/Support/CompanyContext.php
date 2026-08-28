<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CompanyContext
{
    public static function id(?User $user = null): ?int
    {
        $user ??= auth()->user();

        if ($user === null) {
            return null;
        }

        if (self::isGlobalAdmin($user)) {
            $selectedCompanyId = self::selectedCompanyId();

            return $selectedCompanyId ?? ($user->company_id !== null ? (int) $user->company_id : null);
        }

        return $user->company_id !== null ? (int) $user->company_id : -1;
    }

    public static function canOperate(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null && ($user->company_id !== null || self::isGlobalAdmin($user));
    }

    public static function isGlobalAdmin(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null && $user->hasRole('super_admin');
    }

    public static function canAssignNoCompany(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null && $user->hasRole('super_admin');
    }

    public static function activeCompany(?User $user = null): ?Company
    {
        $user ??= auth()->user();

        if ($user === null) {
            return null;
        }

        $companyId = self::id($user);

        if ($companyId === null || $companyId < 1) {
            return null;
        }

        if (! self::isGlobalAdmin($user) && (int) $user->company_id === $companyId && $user->relationLoaded('company')) {
            return $user->company;
        }

        return Company::query()->whereKey($companyId)->where('is_active', true)->first();
    }

    public static function scope(Builder $query, ?User $user = null, string $column = 'company_id'): Builder
    {
        $companyId = self::id($user);

        return $query->when($companyId, fn (Builder $query): Builder => $query->where($column, $companyId));
    }

    public static function belongsToUser(?int $companyId, User $user): bool
    {
        return self::isGlobalAdmin($user)
            || ($user->company_id !== null && (int) $user->company_id === (int) $companyId);
    }

    public static function applyToData(array $data, ?User $user = null): array
    {
        $companyId = self::id($user);

        if ($companyId !== null && $companyId > 0) {
            $data['company_id'] = $companyId;
        }

        return $data;
    }

    public static function selectCompany(int $companyId): void
    {
        session()->put('active_company_id', $companyId);
    }

    private static function selectedCompanyId(): ?int
    {
        if (! request()->hasSession()) {
            return null;
        }

        $companyId = request()->session()->get('active_company_id');

        return is_numeric($companyId) && (int) $companyId > 0 ? (int) $companyId : null;
    }
}
