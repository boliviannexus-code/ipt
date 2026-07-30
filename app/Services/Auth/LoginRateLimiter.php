<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginRateLimiter
{
    public const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function tooManyAttempts(Request $request): bool
    {
        return RateLimiter::tooManyAttempts($this->key($request), self::MAX_ATTEMPTS);
    }

    public function hit(Request $request): void
    {
        RateLimiter::hit($this->key($request), self::DECAY_SECONDS);
    }

    public function clear(Request $request): void
    {
        RateLimiter::clear($this->key($request));
    }

    public function availableIn(Request $request): int
    {
        return RateLimiter::availableIn($this->key($request));
    }

    public function key(Request $request): string
    {
        $email = Str::transliterate(Str::lower(trim((string) $request->input('email'))));

        return $email.'|'.$request->ip();
    }
}
