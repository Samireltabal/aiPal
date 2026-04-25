<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Centralised Microsoft OAuth credential management for `connections` rows.
 * Refreshes expired access tokens on the fly and persists the new credentials
 * back into the encrypted JSON column.
 */
class MicrosoftConnectionAuth
{
    public function __construct(private readonly MicrosoftOAuthClient $oauth) {}

    /**
     * Return a valid access token for the given Microsoft connection,
     * refreshing transparently when expired.
     */
    public function accessTokenFor(Connection $connection): string
    {
        $this->ensureMicrosoftProvider($connection);

        $access = $connection->credential('access_token');
        $refresh = $connection->credential('refresh_token');
        $expiresAt = $connection->credential('expires_at');

        if ($access === null) {
            throw new RuntimeException('Microsoft connection has no access token. Please reconnect in Settings.');
        }

        $expired = $expiresAt !== null && Carbon::parse($expiresAt)->isPast();

        if ($expired && $refresh !== null) {
            $access = $this->refresh($connection, $refresh) ?? $access;
        }

        return $access;
    }

    public function pickConnection(User $user): ?Connection
    {
        return $user->pickConnection(Connection::PROVIDER_MICROSOFT);
    }

    /**
     * Return the new access token after refresh, or null if refresh failed.
     */
    private function refresh(Connection $connection, string $refreshToken): ?string
    {
        $new = $this->oauth->refresh($refreshToken);

        if (! isset($new['access_token'])) {
            return null;
        }

        $expiresAt = isset($new['expires_in'])
            ? Carbon::now()->addSeconds((int) $new['expires_in'])->toIso8601String()
            : null;

        $merged = [
            'access_token' => $new['access_token'],
            'expires_at' => $expiresAt,
        ];

        // Microsoft rotates refresh tokens — persist the new one when present.
        if (isset($new['refresh_token'])) {
            $merged['refresh_token'] = $new['refresh_token'];
        }

        $connection->mergeCredentials($merged);

        return $new['access_token'];
    }

    private function ensureMicrosoftProvider(Connection $connection): void
    {
        if ($connection->provider !== Connection::PROVIDER_MICROSOFT) {
            throw new RuntimeException("Connection {$connection->id} is not a Microsoft connection.");
        }
    }
}
