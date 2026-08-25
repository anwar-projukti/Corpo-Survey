<?php

use App\Services\LoginThrottle;

test('locks out after the configured number of attempts', function () {
    config(['login-throttle.attempts_before_lockout' => 3]);
    $throttle = new LoginThrottle;

    $throttle->recordFailure('a@example.com', '127.0.0.1');
    $throttle->recordFailure('a@example.com', '127.0.0.1');
    expect($throttle->tooManyAttempts('a@example.com', '127.0.0.1'))->toBeFalse();

    $throttle->recordFailure('a@example.com', '127.0.0.1');
    expect($throttle->tooManyAttempts('a@example.com', '127.0.0.1'))->toBeTrue();
});

test('progressive throttle can be disabled via config', function () {
    config(['login-throttle.enabled' => false]);
    $throttle = new LoginThrottle;

    for ($i = 0; $i < 20; $i++) {
        $throttle->recordFailure('a@example.com', '127.0.0.1');
    }

    expect($throttle->tooManyAttempts('a@example.com', '127.0.0.1'))->toBeFalse();
});

test('clearing removes both the attempt count and any active lock', function () {
    config(['login-throttle.attempts_before_lockout' => 1]);
    $throttle = new LoginThrottle;

    $throttle->recordFailure('a@example.com', '127.0.0.1');
    expect($throttle->tooManyAttempts('a@example.com', '127.0.0.1'))->toBeTrue();

    $throttle->clear('a@example.com', '127.0.0.1');
    expect($throttle->tooManyAttempts('a@example.com', '127.0.0.1'))->toBeFalse();
});
