<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Enforcement
    |--------------------------------------------------------------------------
    |
    | Whether Org Admin/Super Admin roles are required to have 2FA enabled
    | and confirmed before reaching the dashboard (spec §5.1). Defaults to
    | true — this must stay on in any real deployment. Set
    | TWO_FACTOR_ENFORCED=false locally only, e.g. when no authenticator
    | app is available for testing.
    |
    */

    'enforced' => env('TWO_FACTOR_ENFORCED', true),

];
