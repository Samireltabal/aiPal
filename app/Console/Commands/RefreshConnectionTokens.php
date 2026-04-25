<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Connection;
use App\Services\GoogleConnectionAuth;
use App\Services\MicrosoftConnectionAuth;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Proactively refresh OAuth access tokens that are nearing expiry. Runs on a
 * 5-minute cadence so the first user request after a token would have lapsed
 * never pays the refresh latency, and the user sees consistent integration
 * uptime even when the app is mostly idle.
 *
 * Connections whose refresh token is rejected are disabled so downstream
 * tools cleanly report 'not connected' rather than throwing.
 */
#[Signature('connections:refresh-tokens {--buffer=600 : Refresh tokens that expire within this many seconds.}')]
#[Description('Refresh OAuth access tokens (Google + Microsoft) before they expire.')]
class RefreshConnectionTokens extends Command
{
    public function __construct(
        private readonly GoogleConnectionAuth $googleAuth,
        private readonly MicrosoftConnectionAuth $microsoftAuth,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $buffer = (int) $this->option('buffer');
        $thresholdIso = Carbon::now()->addSeconds($buffer)->toIso8601String();

        $providers = [Connection::PROVIDER_GOOGLE, Connection::PROVIDER_MICROSOFT];

        $candidates = Connection::query()
            ->whereIn('provider', $providers)
            ->where('enabled', true)
            ->get()
            ->filter(fn (Connection $c) => $this->isExpiringSoon($c, $thresholdIso));

        $this->info("Found {$candidates->count()} expiring connection(s).");

        $refreshed = 0;
        $failed = 0;

        foreach ($candidates as $connection) {
            $ok = match ($connection->provider) {
                Connection::PROVIDER_GOOGLE => $this->safeRefresh(fn () => $this->googleAuth->refreshConnection($connection)),
                Connection::PROVIDER_MICROSOFT => $this->safeRefresh(fn () => $this->microsoftAuth->refreshConnection($connection)),
                default => false,
            };

            if ($ok) {
                $refreshed++;

                continue;
            }

            $failed++;
            Log::warning('Connection token refresh failed', [
                'connection_id' => $connection->id,
                'user_id' => $connection->user_id,
                'provider' => $connection->provider,
                'identifier' => $connection->identifier,
            ]);
        }

        $this->info("Refreshed: {$refreshed}, failed: {$failed}.");

        return self::SUCCESS;
    }

    private function isExpiringSoon(Connection $connection, string $thresholdIso): bool
    {
        $expiresAt = $connection->credential('expires_at');
        if ($expiresAt === null) {
            return false;
        }

        return $expiresAt <= $thresholdIso;
    }

    private function safeRefresh(callable $fn): bool
    {
        try {
            return (bool) $fn();
        } catch (\Throwable $e) {
            Log::error('Connection refresh threw', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
