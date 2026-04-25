<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Inbound email forwarding (forward-to-aiPal feature)
    |--------------------------------------------------------------------------
    |
    | A Cloudflare Email Worker receives mail on the configured domain,
    | validates it, and HMAC-signs a POST to /webhooks/email/inbound.
    |
    | - "domain" is the hostname addresses are minted under, e.g.
    |   forward-{token}@inbound.samirai.xyz
    | - "hmac_secret" is a shared secret between the Cloudflare Worker and
    |   this app. Rotate both sides in lockstep.
    | - "max_body_kb" caps the total size of processed email body + headers.
    | - "rate_limit_per_minute" caps classifier calls per user.
    |
    */

    'domain' => env('INBOUND_EMAIL_DOMAIN', 'inbound.samirai.xyz'),

    'hmac_secret' => env('INBOUND_EMAIL_HMAC_SECRET'),

    'max_body_kb' => (int) env('INBOUND_EMAIL_MAX_BODY_KB', 256),

    'rate_limit_per_minute' => (int) env('INBOUND_EMAIL_RATE_LIMIT_PER_MINUTE', 20),
];
