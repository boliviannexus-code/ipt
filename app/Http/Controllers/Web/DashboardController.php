<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('dashboard.view');

        $company = CompanyContext::activeCompany();

        return view('dashboard.index', [
            'dashboardCompany' => $company,
            'totalUsers' => User::query()->count(),
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'totalRoles' => Role::query()->count(),
            'totalPermissions' => Permission::query()->count(),
            'totalCompanies' => Company::query()->count(),
            'recentAudits' => Audit::query()
                ->with('user')
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
