<?php

declare(strict_types=1);

namespace App\Modules\People\Services;

/**
 * Tiny value object for a parsed RFC-5322 mailbox.
 *
 * Why an object: we need both the bare address (for lookup/uniqueness) and
 * the display name (for seeding a new Person.display_name) from the same
 * input string. Returning a tuple from a free function would be uglier
 * everywhere it's used.
 */
final readonly class EmailAddress
{
    public function __construct(
        public string $address,
        public ?string $displayName,
    ) {}

    public static function parse(?string $raw): ?self
    {
        if ($raw === null) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // "Sara Bryant" <sara@example.com>
        if (preg_match('/^(.*?)<([^>]+)>\s*$/', $raw, $m)) {
            $name = trim($m[1], " \t\"'");
            $address = strtolower(trim($m[2]));
        } else {
            $name = null;
            $address = strtolower($raw);
        }

        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return new self($address, $name !== '' && $name !== null ? $name : null);
    }

    public function domain(): string
    {
        return strtolower((string) substr(strrchr($this->address, '@') ?: '', 1));
    }

    public function localPart(): string
    {
        $at = strrpos($this->address, '@');

        return $at === false ? $this->address : substr($this->address, 0, $at);
    }

    public function isTransactional(): bool
    {
        $localPattern = (string) config('people.transactional_local_parts', '');
        if ($localPattern !== '' && @preg_match($localPattern, $this->localPart())) {
            return true;
        }

        $blockedDomains = (array) config('people.transactional_domains', []);

        return in_array($this->domain(), array_map('strtolower', $blockedDomains), true);
    }

    /**
     * Best-effort fallback display name when the From header had no friendly
     * portion: turn `sara.bryant@example.com` → "Sara Bryant".
     */
    public function fallbackDisplayName(): string
    {
        $local = $this->localPart();
        $local = preg_replace('/\+.*$/', '', $local) ?? $local; // strip plus-addressing
        $local = str_replace(['.', '_', '-'], ' ', $local);

        return trim((string) preg_replace('/\s+/', ' ', ucwords($local)));
    }
}
