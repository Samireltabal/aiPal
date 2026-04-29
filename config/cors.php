<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * Browser extension origins use the chrome-extension:// scheme.
     * Listing them in `allowed_origins_patterns` lets the extension call
     * /api/v1/extension/* without a wildcard that also opens the API to
     * arbitrary websites.
     */
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [
        '#^chrome-extension://[a-p]{32}$#',
        '#^moz-extension://[0-9a-f-]{36}$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
