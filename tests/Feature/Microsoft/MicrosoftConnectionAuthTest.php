<?php

declare(strict_types=1);

namespace Tests\Feature\Microsoft;

use App\Models\Connection;
use App\Models\User;
use App\Services\MicrosoftConnectionAuth;
use App\Services\MicrosoftOAuthClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class MicrosoftConnectionAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_existing_token_when_not_expired(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $connection = $this->makeConnection($user, [
            'access_token' => 'fresh-token',
            'refresh_token' => 'refresh',
            'expires_at' => Carbon::now()->addMinutes(30)->toIso8601String(),
        ]);

        $oauth = Mockery::mock(MicrosoftOAuthClient::class);
        $oauth->shouldNotReceive('refresh');

        $auth = new MicrosoftConnectionAuth($oauth);

        $this->assertSame('fresh-token', $auth->accessTokenFor($connection));
    }

    public function test_refreshes_token_when_expired_and_persists_new_credentials(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $connection = $this->makeConnection($user, [
            'access_token' => 'stale-token',
            'refresh_token' => 'old-refresh',
            'expires_at' => Carbon::now()->subMinutes(5)->toIso8601String(),
        ]);

        $oauth = Mockery::mock(MicrosoftOAuthClient::class);
        $oauth->shouldReceive('refresh')
            ->once()
            ->with('old-refresh')
            ->andReturn([
                'access_token' => 'rotated-token',
                'refresh_token' => 'new-refresh',
                'expires_in' => 3600,
            ]);

        $auth = new MicrosoftConnectionAuth($oauth);

        $this->assertSame('rotated-token', $auth->accessTokenFor($connection));

        $connection->refresh();
        $this->assertSame('rotated-token', $connection->credential('access_token'));
        $this->assertSame('new-refresh', $connection->credential('refresh_token'));
    }

    public function test_falls_back_to_existing_token_when_refresh_fails(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $connection = $this->makeConnection($user, [
            'access_token' => 'stale-token',
            'refresh_token' => 'bad-refresh',
            'expires_at' => Carbon::now()->subMinutes(5)->toIso8601String(),
        ]);

        $oauth = Mockery::mock(MicrosoftOAuthClient::class);
        $oauth->shouldReceive('refresh')
            ->once()
            ->andReturn(['error' => 'invalid_grant']);

        $auth = new MicrosoftConnectionAuth($oauth);

        $this->assertSame('stale-token', $auth->accessTokenFor($connection));
    }

    public function test_throws_when_connection_has_no_access_token(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $connection = $this->makeConnection($user, [
            'access_token' => null,
            'refresh_token' => 'r',
        ]);

        $auth = new MicrosoftConnectionAuth(Mockery::mock(MicrosoftOAuthClient::class));

        $this->expectExceptionMessage('Microsoft connection has no access token');
        $auth->accessTokenFor($connection);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function makeConnection(User $user, array $credentials): Connection
    {
        return $user->connections()->create([
            'context_id' => $user->defaultContext()->id,
            'provider' => Connection::PROVIDER_MICROSOFT,
            'capabilities' => [Connection::CAPABILITY_MAIL, Connection::CAPABILITY_CALENDAR],
            'label' => 'Microsoft',
            'identifier' => 'me@example.com',
            'credentials' => array_merge(['scopes' => ''], $credentials),
            'is_default' => true,
            'enabled' => true,
        ]);
    }
}
