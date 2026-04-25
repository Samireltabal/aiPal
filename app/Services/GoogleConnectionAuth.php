<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Connection;
use App\Models\User;
use Google\Client;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Centralised Google OAuth credential management for `connections` rows.
 * Refreshes expired access tokens on the fly and persists the new credentials
 * back into the encrypted JSON column.
 */
class GoogleConnectionAuth
{
    public function __construct(private readonly GoogleClientFactory $clientFactory) {}

    public function authenticatedClient(Connection $connection): Client
    {
        $this->ensureGoogleProvider($connection);

        $access = $connection->credential('access_token');
        $refresh = $connection->credential('refresh_token');
        $expiresAt = $connection->credential('expires_at');

        if ($access === null) {
            throw new RuntimeException('Google connection has no access token. Please reconnect in Settings.');
        }

        $expired = $expiresAt !== null && Carbon::parse($expiresAt)->isPast();

        if ($expired && $refresh !== null) {
            $this->refresh($connection, $refresh);
        }

        $client = $this->clientFactory->make();
        $client->setAccessToken($this->toGoogleArray($connection));

        return $client;
    }

    public function pickConnection(User $user): ?Connection
    {
        return $user->pickConnection(Connection::PROVIDER_GOOGLE);
    }

    /**
     * Refresh credentials on the given connection. Returns true when a new
     * access token was obtained and persisted, false when the refresh token
     * was rejected (in which case the connection is disabled so tools show
     * 'not connected' rather than breaking).
     */
    public function refreshConnection(Connection $connection): bool
    {
        $this->ensureGoogleProvider($connection);

        $refreshToken = $connection->credential('refresh_token');
        if ($refreshToken === null) {
            return false;
        }

        $client = $this->clientFactory->make();
        $client->setAccessToken($this->toGoogleArray($connection));

        $new = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (! isset($new['access_token'])) {
            $connection->update(['enabled' => false]);

            return false;
        }

        $expiresAt = isset($new['expires_in'])
            ? Carbon::now()->addSeconds((int) $new['expires_in'])->toIso8601String()
            : null;

        $connection->mergeCredentials([
            'access_token' => $new['access_token'],
            'expires_at' => $expiresAt,
        ]);

        return true;
    }

    public function hasScope(Connection $connection, string $scope): bool
    {
        $scopes = (string) ($connection->credential('scopes') ?? '');

        return in_array($scope, explode(' ', $scopes), true);
    }

    private function refresh(Connection $connection, string $refreshToken): void
    {
        $client = $this->clientFactory->make();
        $client->setAccessToken($this->toGoogleArray($connection));

        $new = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (! isset($new['access_token'])) {
            return;
        }

        $expiresAt = isset($new['expires_in'])
            ? Carbon::now()->addSeconds((int) $new['expires_in'])->toIso8601String()
            : null;

        $connection->mergeCredentials([
            'access_token' => $new['access_token'],
            'expires_at' => $expiresAt,
        ]);
    }

    private function ensureGoogleProvider(Connection $connection): void
    {
        if ($connection->provider !== Connection::PROVIDER_GOOGLE) {
            throw new RuntimeException("Connection {$connection->id} is not a Google connection.");
        }
    }

    private function toGoogleArray(Connection $connection): array
    {
        $expiresAt = $connection->credential('expires_at');
        $expiresIn = $expiresAt !== null
            ? max(0, (int) Carbon::now()->diffInSeconds(Carbon::parse($expiresAt), false))
            : null;

        return array_filter([
            'access_token' => $connection->credential('access_token'),
            'refresh_token' => $connection->credential('refresh_token'),
            'expires_in' => $expiresIn,
            'token_type' => 'Bearer',
        ]);
    }
}
