<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Services\LoginThrottle;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Request;

class LogFailedLogin
{
    public function __construct(private readonly LoginThrottle $throttle) {}

    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        // Progressive lockout applies to the web (session) login form only;
        // a future API/token guard would need its own throttle semantics.
        // The audit log itself still records every guard's failures.
        if ($email !== null && $event->guard === 'web') {
            $this->throttle->recordFailure($email, Request::ip());
        }

        AuditLog::create([
            'organization_id' => $event->user?->organization_id,
            'actor_id' => null,
            'actor_email' => $email,
            'action' => 'login.failed',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
