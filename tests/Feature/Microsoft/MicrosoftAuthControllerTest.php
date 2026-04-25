<?php

declare(strict_types=1);

namespace Tests\Feature\Microsoft;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MicrosoftAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.microsoft.client_id', 'test-client');
        config()->set('services.microsoft.client_secret', 'test-secret');
        config()->set('services.microsoft.redirect', 'http://localhost/microsoft/callback');
        config()->set('services.microsoft.tenant', 'common');
        config()->set('services.microsoft.scopes', [
            'offline_access', 'openid', 'email', 'profile', 'Mail.Read', 'Calendars.ReadWrite',
        ]);
    }

    public function test_redirect_sends_user_to_microsoft_consent_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('microsoft.auth'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://login.microsoftonline.com/common/oauth2/v2.0/authorize?', $location);
        $this->assertStringContainsString('client_id=test-client', $location);
        $this->assertStringContainsString('response_type=code', $location);
        $this->assertStringContainsString('scope=offline_access', $location);
    }

    public function test_callback_stores_connection_with_credentials(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'access_token' => 'eyJtest',
                'refresh_token' => 'M.refresh',
                'expires_in' => 3600,
                'scope' => 'Mail.Read Calendars.ReadWrite offline_access',
                'token_type' => 'Bearer',
            ]),
            'graph.microsoft.com/v1.0/me' => Http::response([
                'mail' => 'me@example.com',
                'userPrincipalName' => 'me@example.com',
                'displayName' => 'Sam',
            ]),
        ]);

        $user = User::factory()->withDefaultContext()->create();

        $this->actingAs($user)
            ->withSession(['microsoft_oauth_state' => 'state-token'])
            ->get(route('microsoft.callback', ['code' => 'auth-code', 'state' => 'state-token']))
            ->assertRedirect(route('settings'));

        $this->assertDatabaseHas('connections', [
            'user_id' => $user->id,
            'provider' => Connection::PROVIDER_MICROSOFT,
            'identifier' => 'me@example.com',
            'is_default' => true,
        ]);

        $connection = $user->connections()
            ->where('provider', Connection::PROVIDER_MICROSOFT)
            ->firstOrFail();

        $this->assertSame('eyJtest', $connection->credential('access_token'));
        $this->assertSame('M.refresh', $connection->credential('refresh_token'));
    }

    public function test_callback_rejects_missing_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('microsoft.callback'))
            ->assertSessionHasErrors('code');
    }

    public function test_callback_rejects_state_mismatch(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['microsoft_oauth_state' => 'real-state'])
            ->get(route('microsoft.callback', ['code' => 'x', 'state' => 'forged']))
            ->assertRedirect(route('settings'))
            ->assertSessionHas('error');

        $this->assertSame(0, $user->connections()->where('provider', Connection::PROVIDER_MICROSOFT)->count());
    }

    public function test_callback_handles_microsoft_error_response(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'AADSTS70008',
            ], 400),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['microsoft_oauth_state' => 's'])
            ->get(route('microsoft.callback', ['code' => 'bad', 'state' => 's']))
            ->assertRedirect(route('settings'));

        $this->assertSame(0, $user->connections()->where('provider', Connection::PROVIDER_MICROSOFT)->count());
    }

    public function test_disconnect_deletes_microsoft_connections(): void
    {
        $user = User::factory()->withDefaultContext()->create();

        $user->connections()->create([
            'context_id' => $user->defaultContext()->id,
            'provider' => Connection::PROVIDER_MICROSOFT,
            'capabilities' => [Connection::CAPABILITY_MAIL, Connection::CAPABILITY_CALENDAR],
            'label' => 'Microsoft',
            'identifier' => 'me@example.com',
            'credentials' => ['access_token' => 'tok', 'refresh_token' => 'r', 'scopes' => ''],
            'is_default' => true,
            'enabled' => true,
        ]);

        $this->actingAs($user)
            ->delete(route('microsoft.disconnect'))
            ->assertRedirect(route('settings'));

        $this->assertSame(0, $user->connections()->where('provider', Connection::PROVIDER_MICROSOFT)->count());
    }

    public function test_unauthenticated_user_cannot_access_microsoft_routes(): void
    {
        $this->get(route('microsoft.auth'))->assertRedirect(route('login'));
        $this->get(route('microsoft.callback', ['code' => 'x']))->assertRedirect(route('login'));
        $this->delete(route('microsoft.disconnect'))->assertRedirect(route('login'));
    }
}
