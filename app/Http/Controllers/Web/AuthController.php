<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\Auth\LoginRateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginRateLimiter $loginRateLimiter
    ) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if ($this->loginRateLimiter->tooManyAttempts($request)) {
            return $this->throttleFailure($request);
        }

        $credentials = [
            ...$request->validated(),
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->loginRateLimiter->hit($request);

            return $this->authenticationFailure($request);
        }

        if (! $request->user()?->hasActiveAccess()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $this->loginRateLimiter->hit($request);

            return $this->authenticationFailure($request);
        }

        $this->loginRateLimiter->clear($request);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function authenticationFailure(LoginRequest $request): RedirectResponse
    {
        return back()
            ->withErrors(['email' => 'Las credenciales no son validas.'])
            ->withInput($request->only(['email', 'remember']));
    }

    private function throttleFailure(LoginRequest $request): RedirectResponse
    {
        $seconds = max(1, $this->loginRateLimiter->availableIn($request));

        return back()
            ->withErrors(['email' => "Demasiados intentos. Intenta nuevamente en {$seconds} segundos."])
            ->withInput($request->only(['email', 'remember']))
            ->withHeaders([
                'Retry-After' => $seconds,
                'X-Login-RateLimit-Limit' => LoginRateLimiter::MAX_ATTEMPTS,
            ]);
    }
}
