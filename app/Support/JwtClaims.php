<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Minimal helper to extract claims from an unsigned JWT payload.
 *
 * Used by OAuth callbacks (Google + Microsoft) where the id_token is a
 * trusted JWT issued in the same exchange — we already trust the issuer
 * because we just made the token request to them, so signature
 * verification is unnecessary at this point.
 */
class JwtClaims
{
    /**
     * @return array<string, mixed>
     */
    public static function decode(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [];
        }

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            return [];
        }

        $claims = json_decode($decoded, true);

        return is_array($claims) ? $claims : [];
    }
}
