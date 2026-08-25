<?php

namespace App\Services\Captcha;

use App\Contracts\CaptchaVerifier;

/**
 * No-op driver for local/testing environments — always passes, since
 * standing up a real reCAPTCHA site key isn't practical outside production.
 * Bound only when captcha.driver is "null" (config/captcha.php); never
 * reachable in production, where the driver defaults to "recaptcha".
 */
class NullCaptchaVerifier implements CaptchaVerifier
{
    public function verify(?string $token, ?string $ip): bool
    {
        return true;
    }
}
