<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Progressive lockout on top of Fortify's flat per-minute rate limiter.
 * Consecutive failed attempts escalate the lockout window instead of always
 * unlocking after a fixed minute, making brute force meaningfully slower
 * the longer an attacker persists.
 */
class LoginThrottle
{
    public function key(string $email, string $ip): string
    {
        return 'login-throttle:'.Str::transliterate(Str::lower($email)).'|'.$ip;
    }

    public function tooManyAttempts(string $email, string $ip): bool
    {
        if (! config('login-throttle.enabled')) {
            return false;
        }

        return Cache::get($this->lockKey($email, $ip)) !== null;
    }

    public function availableIn(string $email, string $ip): int
    {
        $lockedUntil = Cache::get($this->lockKey($email, $ip));

        return $lockedUntil ? max(0, $lockedUntil - now()->timestamp) : 0;
    }

    public function recordFailure(string $email, string $ip): void
    {
        if (! config('login-throttle.enabled')) {
            return;
        }

        $attemptsBeforeLockout = (int) config('login-throttle.attempts_before_lockout');
        $escalationSeconds = config('login-throttle.escalation_seconds');

        $attemptsKey = $this->key($email, $ip);
        $attempts = (int) Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addHours(24));

        if ($attempts >= $attemptsBeforeLockout) {
            $escalationIndex = min(
                $attempts - $attemptsBeforeLockout,
                count($escalationSeconds) - 1
            );
            $lockSeconds = $escalationSeconds[$escalationIndex];

            Cache::put($this->lockKey($email, $ip), now()->addSeconds($lockSeconds)->timestamp, $lockSeconds);
        }
    }

    public function clear(string $email, string $ip): void
    {
        Cache::forget($this->key($email, $ip));
        Cache::forget($this->lockKey($email, $ip));
    }

    private function lockKey(string $email, string $ip): string
    {
        return $this->key($email, $ip).':locked';
    }
}
