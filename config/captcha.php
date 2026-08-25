<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA Driver
    |--------------------------------------------------------------------------
    |
    | "recaptcha" (Google reCAPTCHA v3) or "null" (always passes — local and
    | testing only). Set to "recaptcha" and provide CAPTCHA_SITE_KEY /
    | CAPTCHA_SECRET_KEY before enabling in production (see spec §5.8).
    |
    */

    'driver' => env('CAPTCHA_DRIVER', 'null'),

    'recaptcha' => [
        'site_key' => env('CAPTCHA_SITE_KEY'),
        'secret_key' => env('CAPTCHA_SECRET_KEY'),
        'min_score' => env('CAPTCHA_MIN_SCORE', 0.5),
    ],

];
