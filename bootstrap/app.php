<?php

use App\Http\Middleware\EnsureSessionIsNotExpired;
use App\Http\Middleware\RequireTwoFactorForPrivilegedRoles;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api/v1.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            SetLocale::class,
            EnsureSessionIsNotExpired::class,
            RequireTwoFactorForPrivilegedRoles::class,
        ]);

        // Lets the same-origin Blade/Alpine builder UI call /api/v1/...
        // using the ordinary session cookie (Sanctum SPA auth) instead of
        // needing a separate bearer token.
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
