<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Premium pack
    |--------------------------------------------------------------------------
    |
    | Enables the optional `samireltabal/aipal-premium` Composer package when
    | it is installed. Has no effect when the package is absent. Premium
    | features must implement App\Support\PremiumExtensionPoint and bind into
    | the container under that contract.
    |
    */

    'premium_enabled' => filter_var(env('AIPAL_PREMIUM_ENABLED', false), FILTER_VALIDATE_BOOL),
];
