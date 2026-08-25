<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LoginThrottle;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class LogSuccessfulLogin
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly LoginThrottle $throttle,
    ) {}

    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => Request::ip(),
        ])->saveQuietly();

        $this->throttle->clear($user->email, Request::ip());

        // Stamped here (actual login time) rather than lazily by
        // EnsureSessionIsNotExpired, whose first-seen fallback would
        // otherwise reset the absolute-lifetime clock for any session that
        // existed before that middleware ran on it.
        if (Session::isStarted()) {
            Session::put('auth.session_started_at', now()->timestamp);
        }

        $this->auditLogger->log('login', actor: $user);
    }
}
