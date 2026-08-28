<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveCompanyController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless(CompanyContext::isGlobalAdmin($request->user()), 403);

        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $company = Company::query()->whereKey($data['company_id'])->where('is_active', true)->firstOrFail();
        CompanyContext::selectCompany((int) $company->id);

        return back()->with('success', "Empresa activa: {$company->name}.");
    }
}
