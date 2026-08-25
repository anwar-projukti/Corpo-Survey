<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Request;

class LogLockout
{
    public function handle(Lockout $event): void
    {
        AuditLog::create([
            'action' => 'login.lockout',
            'actor_email' => $event->request->input('email'),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
