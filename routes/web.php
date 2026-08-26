<?php

use App\Http\Controllers\ConfirmPasswordForTwoFactorController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MyFieldSurveyPageController;
use App\Http\Controllers\PublicSurveyPageController;
use App\Http\Controllers\SurveyAnalyticsPageController;
use App\Http\Controllers\SurveyPageController;
use App\Http\Controllers\SurveyResponsePageController;
use App\Http\Controllers\ThemeController;
use App\Http\Middleware\GrantsPublicSurveyAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/locale', LocaleController::class)->name('locale.update');

// The public respondent-facing survey link (spec §5.8) — access is gated
// by the URL's own signature, not by auth, so this sits outside every
// auth/verified/2FA middleware group above.
Route::get('/s/{survey}', [PublicSurveyPageController::class, 'show'])
    ->middleware(['signed', GrantsPublicSurveyAccess::class])
    ->name('public.surveys.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', function () {
        return view('home');
    })->name('home');

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

    // The screen RequireTwoFactorForPrivilegedRoles sends Org Admin/Super
    // Admin users to until they've enabled and confirmed 2FA (spec §5.1).
    Route::get('/two-factor-setup', function () {
        return view('two-factor-setup');
    })->name('two-factor.setup');

    Route::get('/two-factor-setup/confirm-password', ConfirmPasswordForTwoFactorController::class)
        ->name('two-factor.setup.confirm-password');
});
