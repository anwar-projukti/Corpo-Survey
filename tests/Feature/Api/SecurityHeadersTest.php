<?php

/**
 * Spec §5.12's baseline security headers — applied globally (app/Http/Middleware/SecurityHeaders.php),
 * so a plain guest request to any route is enough to verify them.
 */
test('every response carries the baseline security headers', function () {
    $response = $this->get('/login');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Content-Security-Policy');
    expect($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'none'");
});

test('the CSP allows Google Fonts, required for the spec §4 Bangla typography', function () {
    $response = $this->get('/login');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain('https://fonts.googleapis.com');
    expect($csp)->toContain('https://fonts.gstatic.com');
});

test('the CSP allows reverse-geocoding a field responses captured location', function () {
    $response = $this->get('/login');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain('https://nominatim.openstreetmap.org');
});

test('HSTS is only sent over an actual HTTPS request', function () {
    $plain = $this->get('/login');
    $plain->assertHeaderMissing('Strict-Transport-Security');

    // TrustProxies is configured to trust every proxy (bootstrap/app.php)
    // for real deployments behind an nginx/ALB doing TLS termination —
    // which means Symfony resolves isSecure() from the X-Forwarded-Proto
    // header, not the raw HTTPS server variable a direct HTTPS connection
    // would set.
    $secure = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get('/login');
    $secure->assertHeader('Strict-Transport-Security');
});

test('security headers are present on JSON API responses too', function () {
    $response = $this->getJson('/api/v1/surveys');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});
