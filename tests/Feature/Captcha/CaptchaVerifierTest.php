<?php

use App\Contracts\CaptchaVerifier;
use App\Services\Captcha\NullCaptchaVerifier;
use App\Services\Captcha\RecaptchaV3Verifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('the null driver always passes regardless of token', function () {
    $verifier = new NullCaptchaVerifier;

    expect($verifier->verify(null, '127.0.0.1'))->toBeTrue()
        ->and($verifier->verify('anything', '127.0.0.1'))->toBeTrue();
});

test('the container resolves the null driver by default', function () {
    config(['captcha.driver' => 'null']);

    expect(app(CaptchaVerifier::class))->toBeInstanceOf(NullCaptchaVerifier::class);
});

test('the container resolves the recaptcha driver when configured', function () {
    config(['captcha.driver' => 'recaptcha']);

    expect(app(CaptchaVerifier::class))->toBeInstanceOf(RecaptchaV3Verifier::class);
});

test('recaptcha verification fails closed with no token', function () {
    config(['captcha.recaptcha.secret_key' => 'test-secret']);

    $verifier = new RecaptchaV3Verifier;

    expect($verifier->verify(null, '127.0.0.1'))->toBeFalse();
});

test('recaptcha verification fails closed with no secret key configured', function () {
    config(['captcha.recaptcha.secret_key' => null]);

    $verifier = new RecaptchaV3Verifier;

    expect($verifier->verify('some-token', '127.0.0.1'))->toBeFalse();
});

test('recaptcha verification passes when googles response is successful and above the score threshold', function () {
    config(['captcha.recaptcha.secret_key' => 'test-secret', 'captcha.recaptcha.min_score' => 0.5]);

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
            'score' => 0.9,
        ]),
    ]);

    $verifier = new RecaptchaV3Verifier;

    expect($verifier->verify('valid-token', '203.0.113.5'))->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['secret'] === 'test-secret'
            && $request['response'] === 'valid-token'
            && $request['remoteip'] === '203.0.113.5';
    });
});

test('recaptcha verification fails when the score is below the configured threshold', function () {
    config(['captcha.recaptcha.secret_key' => 'test-secret', 'captcha.recaptcha.min_score' => 0.5]);

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
            'score' => 0.2,
        ]),
    ]);

    $verifier = new RecaptchaV3Verifier;

    expect($verifier->verify('low-score-token', '203.0.113.5'))->toBeFalse();
});

test('recaptcha verification fails when google reports success false', function () {
    config(['captcha.recaptcha.secret_key' => 'test-secret']);

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]),
    ]);

    $verifier = new RecaptchaV3Verifier;

    expect($verifier->verify('bad-token', '203.0.113.5'))->toBeFalse();
});

test('recaptcha verification fails closed when the http call itself fails', function () {
    config(['captcha.recaptcha.secret_key' => 'test-secret']);

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(null, 500),
    ]);

    $verifier = new RecaptchaV3Verifier;

    expect($verifier->verify('some-token', '203.0.113.5'))->toBeFalse();
});

test('recaptcha verification fails closed when the http call throws', function () {
    config(['captcha.recaptcha.secret_key' => 'test-secret']);

    Http::fake(function () {
        throw new ConnectionException('timed out');
    });

    $verifier = new RecaptchaV3Verifier;

    expect($verifier->verify('some-token', '203.0.113.5'))->toBeFalse();
});
