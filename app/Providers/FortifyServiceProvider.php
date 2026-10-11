<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\EnsureLoginIsNotProgressivelyThrottled;
use App\Actions\Fortify\EnsureUserMatchesLoginPortal;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\StashLoginPortalForTwoFactorChallenge;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Listeners\EnforceLoginPortalOnTwoFactorChallenge;
use App\Support\LoginPortal;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::loginView(function () {
            return view('auth.login', ['portal' => LoginPortal::USER]);
        });
        Fortify::registerView('auth.register');
        Fortify::requestPasswordResetLinkView('auth.forgot-password');
        Fortify::resetPasswordView('auth.reset-password');
        Fortify::confirmPasswordView('auth.confirm-password');
        Fortify::twoFactorChallengeView('auth.two-factor-challenge');
        Fortify::verifyEmailView('auth.verify-email');

        Fortify::authenticateThrough(function (LoginRequest $request) {
            return [
                EnsureLoginIsNotProgressivelyThrottled::class,
                config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
                StashLoginPortalForTwoFactorChallenge::class,
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
                AttemptToAuthenticate::class,
                EnsureUserMatchesLoginPortal::class,
                PrepareAuthenticatedSession::class,
            ];
        });

        // The 2FA challenge path (see StashLoginPortalForTwoFactorChallenge
        // and EnforceLoginPortalOnTwoFactorChallenge) authenticates the
        // session from its own controller, bypassing authenticateThrough()
        // entirely once a valid code is submitted — this listener is the
        // only enforcement point left for that path.
        Event::listen(ValidTwoFactorAuthenticationCodeProvided::class, EnforceLoginPortalOnTwoFactorChallenge::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
