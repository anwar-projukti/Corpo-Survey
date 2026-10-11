<?php

use App\Http\Controllers\Api\V1\Exports\ExportJobController;
use App\Http\Controllers\ConfirmPasswordForTwoFactorController;
use App\Http\Controllers\ExportHistoryPageController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LandingContentPageController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PricingPlanPageController;
use App\Http\Controllers\MyFieldSurveyPageController;
use App\Http\Controllers\OrganizationManagementPageController;
use App\Http\Controllers\PublicSurveyPageController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RespondentImportPageController;
use App\Http\Controllers\SurveyAnalyticsPageController;
use App\Http\Controllers\SurveyPageController;
use App\Http\Controllers\SurveyResponsePageController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserManagementPageController;
use App\Http\Controllers\UserStatsPageController;
use App\Http\Middleware\GrantsPublicSurveyAccess;
use App\Support\LoginPortal;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/locale', LocaleController::class)->name('locale.update');

// Separate login URLs per audience: Super Admin and Org Admin each get
// their own entry point distinct from the ordinary /login Fortify already
// registers (Laravel\Fortify\Http\Controllers\AuthenticatedSessionController).
// All three submit to Fortify's single POST /login (login.store) — the
// portal a form was rendered for travels along as a hidden field, and
// App\Actions\Fortify\EnsureUserMatchesLoginPortal (plus, for 2FA-enabled
// accounts, App\Listeners\EnforceLoginPortalOnTwoFactorChallenge) reject
// the attempt if the authenticated account's role doesn't belong on that
// portal — a correct password on the wrong URL still fails.
Route::get('/admin/login', function () {
    return view('auth.login', ['portal' => LoginPortal::ADMIN]);
})->middleware('guest:'.config('fortify.guard'))->name('admin.login');

Route::get('/org/login', function () {
    return view('auth.login', ['portal' => LoginPortal::ORG]);
})->middleware('guest:'.config('fortify.guard'))->name('org.login');

// Registration — deliberately not Fortify's own (Features::registration()
// is disabled in config/fortify.php): Fortify's RegisteredUserController
// logs the new user in immediately after creating their account, which
// skips the sign-in step this app wants. RegisterController reuses the
// same CreateNewUser action Fortify would have, so validation and
// organization creation stay identical — only the auto-login is removed.
Route::middleware('guest:'.config('fortify.guard'))->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

// The public respondent-facing survey link (spec §5.8) — access is gated
// by the URL's own signature, not by auth, so this sits outside every
// auth/verified/2FA middleware group above.
Route::get('/s/{survey}', [PublicSurveyPageController::class, 'show'])
    ->middleware(['signed', GrantsPublicSurveyAccess::class])
    ->name('public.surveys.show');

// Export download links (spec §10.4) — authorized purely by the URL's own
// signature, the same reasoning as the public survey link above: the
// signature itself is the credential, generated fresh per-notification
// with its own 24-hour expiry (App\Notifications\ExportReadyNotification),
// so it works from an email client with no Sanctum session at all.
Route::get('/exports/{exportJob}/download', [ExportJobController::class, 'download'])
    ->middleware('signed')
    ->name('api.exports.download');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', [HomePageController::class, 'index'])->name('home');

    Route::post('/theme', ThemeController::class)->name('theme.update');

    Route::get('/surveys', [SurveyPageController::class, 'index'])->name('surveys.index');
    Route::get('/surveys/{survey}', [SurveyPageController::class, 'show'])->name('surveys.show');

    // Response management (spec §8) — one survey's response table/detail
    // view at a time, matching how the backend itself is scoped.
    Route::get('/surveys/{survey}/responses', [SurveyResponsePageController::class, 'index'])->name('responses.index');
    Route::get('/surveys/{survey}/responses/{response}', [SurveyResponsePageController::class, 'show'])->name('responses.show');

    // Analytics dashboard (spec §9) — one survey's overview metrics,
    // trend, drop-off, and per-question distributions at a time.
    Route::get('/surveys/{survey}/analytics', [SurveyAnalyticsPageController::class, 'show'])->name('analytics.show');

    // Door-to-door field collection portal (spec §7).
    Route::get('/my/surveys', [MyFieldSurveyPageController::class, 'index'])->name('my.surveys.index');
    Route::get('/my/surveys/{survey}', [MyFieldSurveyPageController::class, 'show'])->name('my.surveys.show');

    // Excel import/export & user productivity reporting (spec §10). Named
    // distinctly from the JSON API's own exports.index/imports.index/imports.show
    // (routes/api/v1.php) — those live in a completely separate route-name
    // namespace since they're registered from a different file, but distinct
    // names still avoid any confusion when reading route:list output.
    Route::get('/exports', [ExportHistoryPageController::class, 'index'])->name('export-history.index');
    Route::get('/respondents/import', [RespondentImportPageController::class, 'index'])->name('respondent-imports.index');
    Route::get('/reports/user-stats', [UserStatsPageController::class, 'index'])->name('user-stats.index');
    Route::get('/users', [UserManagementPageController::class, 'index'])->name('users.index');

    // Static in-app walkthrough of the survey builder for a first-time
    // Org Admin/Editor — no controller needed, same pattern as
    // two-factor-setup below (a plain Blade render behind auth).
    Route::get('/help/survey-builder', function () {
        return view('help.survey-builder');
    })->name('help.survey-builder');

    // System Admin-only: manage every organization (spec §13 "System
    // Admin"). OrganizationManagementPageController re-authorizes via
    // OrganizationPolicy, which only Super Admin ever passes.
    Route::get('/organizations', [OrganizationManagementPageController::class, 'index'])->name('organizations.index');

    // System Admin-only: edit the public landing page's content (hero,
    // features, pricing, about, contact). LandingContentPageController
    // re-authorizes via LandingContentPolicy, which only Super Admin ever
    // passes.
    Route::get('/landing-content', [LandingContentPageController::class, 'index'])->name('landing-content.index');

    // System Admin-only: manage the pricing tiers shown on the public
    // landing page. PricingPlanPageController re-authorizes via
    // PricingPlanPolicy, which only Super Admin ever passes.
    Route::get('/pricing-plans', [PricingPlanPageController::class, 'index'])->name('pricing-plans.index');

    // System Admin logging in as an organization's own Org Admin for
    // support purposes (spec §13). destroy() has no Super-Admin-only gate
    // of its own — it's reachable by whoever is currently impersonated,
    // and simply 409s if the session has no impersonator_id to restore.
    Route::post('/organizations/{organization}/impersonate', [ImpersonationController::class, 'store'])->name('impersonation.store');
    Route::delete('/impersonate', [ImpersonationController::class, 'destroy'])->name('impersonation.destroy');

    // The screen RequireTwoFactorForPrivilegedRoles sends Org Admin/Super
    // Admin users to until they've enabled and confirmed 2FA (spec §5.1).
    Route::get('/two-factor-setup', function () {
        return view('two-factor-setup');
    })->name('two-factor.setup');

    Route::get('/two-factor-setup/confirm-password', ConfirmPasswordForTwoFactorController::class)
        ->name('two-factor.setup.confirm-password');
});
