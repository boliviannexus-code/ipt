<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\AcademicModule;
use App\Models\Personnel;
use App\Support\CompanyContext;
use Illuminate\Http\Request;

final class ModuleAccessService
{
    public function authorize(Request $request, AcademicModule $module): void
    {
        abort_unless((int) $module->company_id === CompanyContext::id($request->user()), 403);
        if ($request->routeIs('all-modules.*')) {
            abort_unless($request->user()->can('all-modules.manage'), 403);

            return;
        }

        $personnel = $request->user()->personnel;
        abort_unless($personnel instanceof Personnel, 403, 'Tu cuenta no está vinculada a un docente de la empresa activa.');
        abort_unless($module->currentTeacherAssignment()->where('personnel_id', $personnel->id)->exists(), 403);
    }

    public function teacher(Request $request, AcademicModule $module): ?Personnel
    {
        $this->authorize($request, $module);

        return $request->routeIs('all-modules.*')
            ? $module->currentTeacherAssignment?->personnel
            : $request->user()->personnel;
    }
}
