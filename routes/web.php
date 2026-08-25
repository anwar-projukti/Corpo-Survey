<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', function () {
        return view('home');
    })->name('home');

    // Placeholder until Phase 2 builds the real settings/security UI —
    // required now so RequireTwoFactorForPrivilegedRoles has somewhere
    // to send Org Admin/Super Admin users who haven't confirmed 2FA yet.
    Route::get('/two-factor-setup', function () {
        return view('two-factor-setup');
    })->name('two-factor.setup');
});
