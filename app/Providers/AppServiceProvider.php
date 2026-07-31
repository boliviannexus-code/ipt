<?php

namespace App\Providers;

use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SinApiToken;
use App\Models\SinAuthorization;
use App\Policies\CashRegisterPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ProductCategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\RolePolicy;
use App\Policies\SinApiTokenPolicy;
use App\Policies\SinAuthorizationPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(CashRegister::class, CashRegisterPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductCategory::class, ProductCategoryPolicy::class);
        Gate::policy(SinApiToken::class, SinApiTokenPolicy::class);
        Gate::policy(SinAuthorization::class, SinAuthorizationPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
