<?php

namespace App\Actions\Fortify;

use App\Services\LoginThrottle;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LockoutResponse;
use Laravel\Fortify\Fortify;

/**
 * Runs in front of Fortify's own EnsureLoginIsNotThrottled in the login
 * pipeline, adding an escalating lockout (see LoginThrottle) on top of
 * Fortify's flat per-minute limiter.
 */
class EnsureLoginIsNotProgressivelyThrottled
{
    public function __construct(private readonly LoginThrottle $throttle) {}

    public function handle(Request $request, callable $next): mixed
    {
        $email = (string) $request->input(Fortify::username());

        if ($this->throttle->tooManyAttempts($email, $request->ip())) {
            event(new Lockout($request));

            return app(LockoutResponse::class);
        }

        return $next($request);
    }
}
