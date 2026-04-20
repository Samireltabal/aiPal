<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Models\GoogleToken;
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
        $mockClient->shouldReceive('addScope')->times(3);
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

    public function test_callback_stores_google_token(): void
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

        $mockFactory = Mockery::mock(GoogleClientFactory::class);
        $mockFactory->shouldReceive('make')->once()->andReturn($mockClient);

        $this->app->instance(GoogleClientFactory::class, $mockFactory);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('google.callback', ['code' => 'test_code']))
            ->assertRedirect(route('settings'));

        $this->assertDatabaseHas('google_tokens', [
            'user_id' => $user->id,
            'access_token' => 'ya29.test',
            'refresh_token' => '1//refresh',
        ]);
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

        $this->assertDatabaseEmpty('google_tokens');
    }

    public function test_disconnect_deletes_token(): void
    {
        $user = User::factory()->create();

        GoogleToken::create([
            'user_id' => $user->id,
            'access_token' => 'ya29.old',
            'scopes' => '',
        ]);

        $this->actingAs($user)
            ->delete(route('google.disconnect'))
            ->assertRedirect(route('settings'));

        $this->assertDatabaseEmpty('google_tokens');
    }

    public function test_unauthenticated_user_cannot_access_google_routes(): void
    {
        $this->get(route('google.auth'))->assertRedirect(route('login'));
        $this->get(route('google.callback', ['code' => 'x']))->assertRedirect(route('login'));
        $this->delete(route('google.disconnect'))->assertRedirect(route('login'));
    }
}
