<?php

namespace App\Providers;

use App\Contracts\CaptchaVerifier;
use App\Services\Captcha\NullCaptchaVerifier;
use App\Services\Captcha\RecaptchaV3Verifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CaptchaVerifier::class, function () {
            return match (config('captcha.driver')) {
                'recaptcha' => new RecaptchaV3Verifier,
                default => new NullCaptchaVerifier,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Deliberately separate from the admin API's "api" limiter (spec
        // §5.7/§5.8) — a burst of anonymous public-survey traffic must
        // never be able to exhaust the same bucket an org's own admin UI
        // relies on. Keyed by IP since these requests are unauthenticated.
        RateLimiter::for('public-survey', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // A 6-digit OTP (App\Services\SurveyOtpService) needs a much
        // tighter ceiling than ordinary page-load/API traffic — compare
        // FortifyServiceProvider's "two-factor" limiter, 5/min, for the
        // same reasoning applied to an authenticated 2FA code. Keyed by
        // IP+destination (not IP alone) so one attacker can't spread a
        // brute-force of a single destination's code across many IPs, and
        // one IP legitimately requesting codes for several destinations
        // isn't penalized for someone else's target.
        RateLimiter::for('public-survey-otp', function (Request $request) {
            $destination = Str::transliterate(Str::lower((string) $request->input('destination')));

            return Limit::perMinute(5)->by($request->ip().'|'.$destination);
        });
    }
}
