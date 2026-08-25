<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Progressive Login Lockout
    |--------------------------------------------------------------------------
    |
    | Layered on top of Fortify's flat per-minute login limiter (see
    | config/fortify.php's 'limiters.login'). After `attempts_before_lockout`
    | consecutive failures for the same (email, ip), the account is locked
    | for an escalating duration — each further failure moves one step down
    | `escalation_seconds` (capped at the last entry).
    |
    | Set 'enabled' to false to disable progressive lockout entirely and
    | rely solely on Fortify's own rate limiter.
    |
    */

    'enabled' => (bool) env('LOGIN_THROTTLE_ENABLED', true),

    'attempts_before_lockout' => (int) env('LOGIN_THROTTLE_ATTEMPTS_BEFORE_LOCKOUT', 5),

    'escalation_seconds' => [30, 60, 300, 900, 3600],

];
