<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers (spec §5.12) on every response, web and API
 * alike — clickjacking, MIME-sniffing, and referrer-leak protection cost
 * nothing to apply everywhere, so this isn't scoped to just the
 * authenticated dashboard.
 *
 * The CSP's script-src allows 'unsafe-inline' and 'unsafe-eval': this app
 * uses Alpine.js's standard build (not its dedicated CSP build) with
 * inline x-data/x-show/etc. expressions evaluated via `new Function()`
 * throughout every Blade view — removing either directive would break
 * every page. Tightening this properly means migrating to Alpine's CSP
 * build and moving every inline expression into registered
 * Alpine.data()/x-bind functions, which is a frontend-wide rewrite beyond
 * this hardening pass's scope; documented here as the known trade-off
 * rather than left unstated.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=()');

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            // fonts.googleapis.com serves the stylesheet that @font-faces
            // point at fonts.gstatic.com (resources/views/partials/head.blade.php)
            // — Noto Sans/Serif Bengali is spec §4's required Bangla
            // typography, not an optional third-party dependency to drop.
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data:",
            "font-src 'self' data: https://fonts.gstatic.com",
            // nominatim.openstreetmap.org turns a field response's captured
            // latitude/longitude into a human-readable place name (spec §7)
            // — the free, keyless reverse-geocoding service already backing
            // the "view on map" links in My Responses.
            "connect-src 'self' https://nominatim.openstreetmap.org",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]));

        if ($request->isSecure()) {
            // max-age=1 year, applies to subdomains, eligible for browser
            // preload lists — only sent over an actual HTTPS connection so
            // a local/plain-HTTP dev environment is never told to upgrade.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
