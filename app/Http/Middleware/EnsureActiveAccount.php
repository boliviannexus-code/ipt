<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasActiveAccess()) {
            return $next($request);
        }

        $accessToken = $user?->currentAccessToken();

        if ($accessToken && method_exists($accessToken, 'delete')) {
            $accessToken->delete();
        }
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'La cuenta no esta habilitada.',
                'data' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        return Redirect::route('login')
            ->withErrors(['email' => 'La cuenta no esta habilitada.']);
    }
}
