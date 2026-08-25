<?php

namespace App\Services\Captcha;

use App\Contracts\CaptchaVerifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google reCAPTCHA v3 (score-based, no user challenge). A token below
 * captcha.recaptcha.min_score is treated the same as a missing/invalid
 * token — this is spec §5.8's bot-mitigation layer on public submission,
 * so failures must fail closed.
 */
class RecaptchaV3Verifier implements CaptchaVerifier
{
    public function verify(?string $token, ?string $ip): bool
    {
        if (! $token) {
            return false;
        }

        $secret = config('captcha.recaptcha.secret_key');

        if (! $secret) {
            Log::warning('reCAPTCHA verification attempted with no secret key configured.');

            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]);
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verification request failed.', ['exception' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $body = $response->json();

        if (! ($body['success'] ?? false)) {
            return false;
        }

        $minScore = (float) config('captcha.recaptcha.min_score', 0.5);

        return (float) ($body['score'] ?? 0) >= $minScore;
    }
}
