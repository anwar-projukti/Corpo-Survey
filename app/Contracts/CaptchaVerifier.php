<?php

namespace App\Contracts;

interface CaptchaVerifier
{
    /**
     * Verify a client-submitted CAPTCHA token for the given request IP.
     * Returns false for a missing/invalid/low-score token — callers should
     * treat that as a hard rejection, not a soft warning.
     */
    public function verify(?string $token, ?string $ip): bool;
}
