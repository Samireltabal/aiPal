<?php

declare(strict_types=1);

namespace Tests\Feature\Connections;

use App\Models\Connection;
use App\Models\User;
use App\Services\GoogleConnectionAuth;
use App\Services\MicrosoftConnectionAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class RefreshConnectionTokensTest extends TestCase
{
    use RefreshDatabase;

    public function test_refreshes_only_connections_expiring_within_buffer(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $expiringSoon = $this->makeConnection($user, Connection::PROVIDER_GOOGLE, expiresIn: 60);
        $stillFresh = $this->makeConnection($user, Connection::PROVIDER_GOOGLE, expiresIn: 7200);

        $google = Mockery::mock(GoogleConnectionAuth::class);
        $google->shouldReceive('refreshConnection')
            ->once()
            ->with(Mockery::on(fn (Connection $c) => $c->id === $expiringSoon->id))
            ->andReturnTrue();
        $google->shouldNotReceive('refreshConnection')->withArgs(fn (Connection $c) => $c->id === $stillFresh->id);
        $this->app->instance(GoogleConnectionAuth::class, $google);

        $microsoft = Mockery::mock(MicrosoftConnectionAuth::class);
        $microsoft->shouldNotReceive('refreshConnection');
        $this->app->instance(MicrosoftConnectionAuth::class, $microsoft);

        $this->artisan('connections:refresh-tokens')
            ->expectsOutputToContain('Found 1 expiring connection')
            ->expectsOutputToContain('Refreshed: 1, failed: 0.')
            ->assertSuccessful();
    }

    public function test_processes_microsoft_and_google_connections(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $google = $this->makeConnection($user, Connection::PROVIDER_GOOGLE, expiresIn: 60);
        $microsoft = $this->makeConnection($user, Connection::PROVIDER_MICROSOFT, expiresIn: 60);

        $googleAuth = Mockery::mock(GoogleConnectionAuth::class);
        $googleAuth->shouldReceive('refreshConnection')
            ->once()
            ->with(Mockery::on(fn (Connection $c) => $c->id === $google->id))
            ->andReturnTrue();
        $this->app->instance(GoogleConnectionAuth::class, $googleAuth);

        $microsoftAuth = Mockery::mock(MicrosoftConnectionAuth::class);
        $microsoftAuth->shouldReceive('refreshConnection')
            ->once()
            ->with(Mockery::on(fn (Connection $c) => $c->id === $microsoft->id))
            ->andReturnTrue();
        $this->app->instance(MicrosoftConnectionAuth::class, $microsoftAuth);

        $this->artisan('connections:refresh-tokens')
            ->expectsOutputToContain('Refreshed: 2, failed: 0.')
            ->assertSuccessful();
    }

    public function test_counts_failures_when_refresh_returns_false(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $this->makeConnection($user, Connection::PROVIDER_MICROSOFT, expiresIn: 60);

        $microsoftAuth = Mockery::mock(MicrosoftConnectionAuth::class);
        $microsoftAuth->shouldReceive('refreshConnection')->once()->andReturnFalse();
        $this->app->instance(MicrosoftConnectionAuth::class, $microsoftAuth);

        $this->artisan('connections:refresh-tokens')
            ->expectsOutputToContain('Refreshed: 0, failed: 1.')
            ->assertSuccessful();
    }

    public function test_skips_disabled_connections(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $this->makeConnection($user, Connection::PROVIDER_GOOGLE, expiresIn: 60, enabled: false);

        $googleAuth = Mockery::mock(GoogleConnectionAuth::class);
        $googleAuth->shouldNotReceive('refreshConnection');
        $this->app->instance(GoogleConnectionAuth::class, $googleAuth);

        $this->artisan('connections:refresh-tokens')
            ->expectsOutputToContain('Found 0 expiring connection')
            ->assertSuccessful();
    }

    public function test_swallows_thrown_exceptions(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $this->makeConnection($user, Connection::PROVIDER_MICROSOFT, expiresIn: 60);

        $microsoftAuth = Mockery::mock(MicrosoftConnectionAuth::class);
        $microsoftAuth->shouldReceive('refreshConnection')
            ->once()
            ->andThrow(new \RuntimeException('network down'));
        $this->app->instance(MicrosoftConnectionAuth::class, $microsoftAuth);

        $this->artisan('connections:refresh-tokens')
            ->expectsOutputToContain('Refreshed: 0, failed: 1.')
            ->assertSuccessful();
    }

    private function makeConnection(User $user, string $provider, int $expiresIn, bool $enabled = true): Connection
    {
        return $user->connections()->create([
            'context_id' => $user->defaultContext()->id,
            'provider' => $provider,
            'capabilities' => [Connection::CAPABILITY_MAIL],
            'label' => $provider,
            'identifier' => $provider.'-'.uniqid(),
            'credentials' => [
                'access_token' => 'tok',
                'refresh_token' => 'r',
                'expires_at' => Carbon::now()->addSeconds($expiresIn)->toIso8601String(),
                'scopes' => '',
            ],
            'is_default' => true,
            'enabled' => $enabled,
        ]);
    }
}
