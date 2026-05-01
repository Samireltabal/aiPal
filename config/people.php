<?php

declare(strict_types=1);

/*
 * Personal CRM tunables.
 *
 * `transactional_local_parts` — local-part regex for senders we never want
 * to materialize as Person rows (noreply@github, notifications@stripe, etc).
 * Configurable so users can opt in or extend without a code change.
 */
return [
    'transactional_local_parts' => '/^(no[-_]?reply|do[-_]?not[-_]?reply|notifications?|alerts?|mailer[-_]?daemon|postmaster|bounces?|news(letter)?|info|hello|support|help|donot[-_]?reply|automated)/i',

    'transactional_domains' => [
        'mailgun.org',
        'mailchimp.com',
        'sendgrid.net',
        'amazonses.com',
        'postmarkapp.com',
        'mandrillapp.com',
    ],

    'staleness_days' => 90,

    'summarize' => [
        'enabled' => env('PEOPLE_SUMMARIZE_ENABLED', true),
        'provider' => env('PEOPLE_SUMMARIZE_PROVIDER'),
        'model' => env('PEOPLE_SUMMARIZE_MODEL'),
        'max_input_chars' => 8000,
        'max_summary_chars' => 280,
    ],
];
