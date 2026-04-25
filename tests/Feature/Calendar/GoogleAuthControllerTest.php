<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Models\Connection;
use App\Models\User;
use App\Services\GoogleClientFactory;
use Google\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_redirects_to_google_auth_url(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('addScope')->times(4);
        $mockClient->shouldReceive('setAccessType')->once();
        $mockClient->shouldReceive('setPrompt')->once();
        $mockClient->shouldReceive('createAuthUrl')->once()->andReturn('https://accounts.google.com/o/oauth2/auth?fake');

        $mockFactory = Mockery::mock(GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')->once()->andReturn($mockClient);

        $this->app->instance(GoogleClientFactory::class, $mockFactory);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('google.auth'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth?fake');
    }

    public function test_callback_stores_connection_with_credentials(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('fetchAccessTokenWithAuthCode')
            ->once()
            ->with('test_code')
            ->andReturn([
                'access_token' => 'ya29.test',
                'refresh_token' => '1//refresh',
                'expires_in' => 3600,
                'scope' => 'https://www.googleapis.com/auth/calendar.readonly',
            ]);
        // setAccessToken is called for the userinfo lookup; the lookup itself
        // is allowed to throw — fallback identifier is used.
        $mockClient->shouldReceive('setAccessToken')->once();

        $mockFactory = Mockery::mock(GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')->once()->andReturn($mockClient);

        $this->app->instance(GoogleClientFactory::class, $mockFactory);

        $user = User::factory()->withDefaultContext()->create();

        $this->actingAs($user)
            ->get(route('google.callback', ['code' => 'test_code']))
            ->assertRedirect(route('settings'));

        $this->assertDatabaseHas('connections', [
            'user_id' => $user->id,
            'provider' => Connection::PROVIDER_GOOGLE,
            'is_default' => true,
        ]);

        $connection = $user->connections()
            ->where('provider', Connection::PROVIDER_GOOGLE)
            ->firstOrFail();

        $this->assertSame('ya29.test', $connection->credential('access_token'));
        $this->assertSame('1//refresh', $connection->credential('refresh_token'));
    }

    public function test_callback_uses_id_token_email_when_present(): void
    {
        $idToken = $this->makeIdToken(['email' => 'second@gmail.com']);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('fetchAccessTokenWithAuthCode')
            ->once()
            ->andReturn([
                'access_token' => 'ya29.t2',
                'refresh_token' => '1//r2',
                'expires_in' => 3600,
                'id_token' => $idToken,
                'scope' => 'https://www.googleapis.com/auth/gmail.readonly',
            ]);
        // No userinfo fallback because id_token gave us the email.
        $mockClient->shouldNotReceive('setAccessToken');

        $mockFactory = Mockery::mock(GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')->once()->andReturn($mockClient);
        $this->app->instance(GoogleClientFactory::class, $mockFactory);

        $user = User::factory()->withDefaultContext()->create();

        // Pre-existing default Google connection.
        $user->connections()->create([
            'context_id' => $user->defaultContext()->id,
            'provider' => Connection::PROVIDER_GOOGLE,
            'capabilities' => [Connection::CAPABILITY_MAIL],
            'label' => 'first@gmail.com',
            'identifier' => 'first@gmail.com',
            'credentials' => ['access_token' => 'old', 'refresh_token' => 'r', 'scopes' => ''],
            'is_default' => true,
            'enabled' => true,
        ]);

        $this->actingAs($user)
            ->get(route('google.callback', ['code' => 'c']))
            ->assertRedirect(route('settings'));

        $this->assertDatabaseHas('connections', [
            'user_id' => $user->id,
            'provider' => Connection::PROVIDER_GOOGLE,
            'identifier' => 'second@gmail.com',
        ]);

        // Existing default not demoted.
        $first = $user->connections()->where('identifier', 'first@gmail.com')->firstOrFail();
        $this->assertTrue((bool) $first->is_default);

        $second = $user->connections()->where('identifier', 'second@gmail.com')->firstOrFail();
        $this->assertFalse((bool) $second->is_default);
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function makeIdToken(array $claims): string
    {
        $segment = static fn (array $payload): string => rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        return $segment(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$segment($claims).'.signature';
    }

    public function test_callback_rejects_missing_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('google.callback'))
            ->assertSessionHasErrors('code');
    }

    public function test_callback_handles_google_error_response(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('fetchAccessTokenWithAuthCode')
            ->once()
            ->andReturn([
                'error' => 'invalid_grant',
                'error_description' => 'Token expired',
            ]);

        $mockFactory = Mockery::mock(GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')->once()->andReturn($mockClient);

        $this->app->instance(GoogleClientFactory::class, $mockFactory);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('google.callback', ['code' => 'bad_code']))
            ->assertRedirect(route('settings'));

        $this->assertSame(0, $user->connections()->where('provider', Connection::PROVIDER_GOOGLE)->count());
    }

    public function test_disconnect_deletes_google_connections(): void
    {
        $user = User::factory()->withDefaultContext()->create();

        $user->connections()->create([
            'context_id' => $user->defaultContext()->id,
            'provider' => Connection::PROVIDER_GOOGLE,
            'capabilities' => [Connection::CAPABILITY_MAIL],
            'label' => 'Google',
            'identifier' => 'me@example.com',
            'credentials' => ['access_token' => 'ya29.old', 'scopes' => ''],
            'is_default' => true,
            'enabled' => true,
        ]);

        $this->actingAs($user)
            ->delete(route('google.disconnect'))
            ->assertRedirect(route('settings'));

        $this->assertSame(0, $user->connections()->where('provider', Connection::PROVIDER_GOOGLE)->count());
    }

    public function test_unauthenticated_user_cannot_access_google_routes(): void
    {
        $this->get(route('google.auth'))->assertRedirect(route('login'));
        $this->get(route('google.callback', ['code' => 'x']))->assertRedirect(route('login'));
        $this->delete(route('google.disconnect'))->assertRedirect(route('login'));
    }
}
