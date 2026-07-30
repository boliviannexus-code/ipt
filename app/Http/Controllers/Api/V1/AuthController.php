<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\Auth\LoginRateLimiter;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LoginRateLimiter $loginRateLimiter
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        if ($this->loginRateLimiter->tooManyAttempts($request)) {
            $seconds = max(1, $this->loginRateLimiter->availableIn($request));

            return $this->errorResponse(
                "Demasiados intentos. Intenta nuevamente en {$seconds} segundos.",
                ['retry_after' => $seconds],
                429
            )->withHeaders([
                'Retry-After' => $seconds,
                'X-Login-RateLimit-Limit' => LoginRateLimiter::MAX_ATTEMPTS,
            ]);
        }

        $credentials = [
            ...$request->validated(),
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials)) {
            $this->loginRateLimiter->hit($request);

            return $this->errorResponse('Las credenciales no son validas.', null, 422);
        }

        $user = $request->user();

        if (! $user?->hasActiveAccess()) {
            Auth::guard('web')->logout();
            $this->loginRateLimiter->hit($request);

            return $this->errorResponse('Las credenciales no son validas.', null, 422);
        }

        $this->loginRateLimiter->clear($request);
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 'Sesion iniciada correctamente.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Sesion cerrada correctamente.');
    }
}
