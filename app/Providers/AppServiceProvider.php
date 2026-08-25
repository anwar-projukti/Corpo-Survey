<?php

namespace App\Providers;

use App\Contracts\CaptchaVerifier;
use App\Services\Captcha\NullCaptchaVerifier;
use App\Services\Captcha\RecaptchaV3Verifier;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
