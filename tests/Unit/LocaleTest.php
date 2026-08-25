<?php

use App\Support\Locale;

test('supported returns the configured locale list', function () {
    config(['app.supported_locales' => ['en', 'bn']]);

    expect(Locale::supported())->toBe(['en', 'bn']);
});

test('direction is ltr for a locale not in the rtl list', function () {
    config(['app.rtl_locales' => []]);

    expect(Locale::direction('bn'))->toBe('ltr')
        ->and(Locale::direction('en'))->toBe('ltr');
});

test('direction is rtl for a locale in the rtl list', function () {
    config(['app.rtl_locales' => ['ar']]);

    expect(Locale::direction('ar'))->toBe('rtl');
});

test('direction defaults to the current app locale when none is given', function () {
    config(['app.rtl_locales' => ['ar']]);
    app()->setLocale('ar');

    expect(Locale::direction())->toBe('rtl');
});
