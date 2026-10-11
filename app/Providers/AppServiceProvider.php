<?php

namespace App\Providers;

use App\Contracts\CaptchaVerifier;
use App\Services\Captcha\NullCaptchaVerifier;
use App\Services\Captcha\RecaptchaV3Verifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // HTTPS enforcement (spec §5.12) — every generated URL (redirects,
        // signed links, asset()) uses https:// in production. Gated by
        // environment rather than always-on so local development (plain
        // HTTP) and the test suite are unaffected; the real edge (nginx/
        // ALB) is expected to already redirect HTTP->HTTPS and terminate
        // TLS in front of the app, this just keeps the app's own URL
        // generation consistent with that.
        if ($this->app->environment('production', 'staging')) {
            URL::forceScheme('https');
        }

        // The general authenticated-API tier (spec §5.7/§11) — applied to
        // the whole auth:sanctum route group in routes/api/v1.php as a
        // baseline ceiling, with exports/imports layering their own
        // tighter "exports" limiter on top for those specific routes.
        // Keyed per-user rather than per-IP since every route in that
        // group requires authentication already, and a shared office IP
        // must not throttle every admin in an org as one bucket.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

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

        // Exports/imports (spec §5.7/§10.4) are expensive — each one spins
        // up a queued job that reads potentially every response in a survey
        // or organization. A much tighter ceiling than ordinary admin API
        // traffic prevents one admin from flooding the queue with
        // duplicate/near-duplicate export requests. Keyed per-user (not
        // per-IP) since these are authenticated endpoints and an org's
        // shared office IP shouldn't throttle every admin as one bucket.
        RateLimiter::for('exports', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
