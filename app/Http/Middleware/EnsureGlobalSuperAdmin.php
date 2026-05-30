<?php

namespace App\Http\Middleware;

use App\Support\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGlobalSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(CompanyContext::isGlobalAdmin($request->user()), 403);

        return $next($request);
    }
}
