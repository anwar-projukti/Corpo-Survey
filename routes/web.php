<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PublicSurveyPageController;
use App\Http\Controllers\SurveyPageController;
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

    // Placeholder until Phase 2 builds the real settings/security UI —
    // required now so RequireTwoFactorForPrivilegedRoles has somewhere
    // to send Org Admin/Super Admin users who haven't confirmed 2FA yet.
    Route::get('/two-factor-setup', function () {
        return view('two-factor-setup');
    })->name('two-factor.setup');
});
