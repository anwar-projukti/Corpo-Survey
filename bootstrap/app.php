<?php

use App\Http\Middleware\EnsureSessionIsNotExpired;
use App\Http\Middleware\RequireTwoFactorForPrivilegedRoles;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api/v1.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Applied to every response, web and API alike (spec §5.12) —
        // security headers cost nothing to send everywhere.
        $middleware->append(SecurityHeaders::class);

        $middleware->appendToGroup('web', [
            SetLocale::class,
            EnsureSessionIsNotExpired::class,
            RequireTwoFactorForPrivilegedRoles::class,
        ]);

        // Lets the same-origin Blade/Alpine builder UI call /api/v1/...
        // using the ordinary session cookie (Sanctum SPA auth) instead of
        // needing a separate bearer token.
        $middleware->statefulApi();

        // Trusts every proxy in front of the app (spec §5.12) — required
        // once deployed behind an nginx/ALB doing TLS termination, or
        // $request->ip()/isSecure() reflect the proxy instead of the real
        // client, which would silently break IP-keyed rate limiting
        // (spec §5.7), the audit log's IP capture (§5.10/§5.11), and the
        // HSTS-over-HTTPS check in SecurityHeaders above. '*' is safe here
        // specifically because the app itself is never exposed directly
        // to the internet in any real deployment (only through the
        // load balancer/reverse proxy) — if that ever changes, this must
        // be narrowed to the proxy's actual CIDR.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Spec §5.6/§11.2's consistent API error shape —
        // {"success": false, "message": "...", "errors": {...}} — applied
        // to every JSON-expecting request rather than Laravel's own
        // per-exception default shapes (which vary: ValidationException
        // omits "success" entirely, AuthenticationException/AuthorizationException
        // only ever return "message"). "errors" is included only for
        // ValidationException; every other exception's response omits it
        // rather than sending an empty object, since "no errors object" is
        // a real signal (see resources/js's callers, all of which only
        // ever read err.response.data.errors after a 422).
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'An error occurred.',
                ], $e->getStatusCode());
            }

            // Anything else is an unhandled server error — never leak the
            // real message/stack trace to the client (spec §5.6/§15),
            // regardless of environment, since a JSON API consumer is
            // never the place for debug-mode HTML/detail either way. The
            // real detail still reaches Laravel's own logs untouched,
            // since render() doesn't suppress that reporting path.
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        });
    })->create();
