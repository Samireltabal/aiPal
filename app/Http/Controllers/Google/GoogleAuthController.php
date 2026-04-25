<?php

namespace App\Http\Controllers\Google;

use App\Models\Connection;
use App\Services\GoogleClientFactory;
use Google\Service\Calendar;
use Google\Service\Gmail;
use Google\Service\Oauth2;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GoogleAuthController
{
    public function __construct(private readonly GoogleClientFactory $clientFactory) {}

    public function redirect(Request $request): RedirectResponse
    {
        $client = $this->clientFactory->make();
        $client->addScope(Calendar::CALENDAR_READONLY);
        $client->addScope(Gmail::GMAIL_READONLY);
        $client->addScope(Gmail::GMAIL_COMPOSE);
        $client->addScope('email');
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $client = $this->clientFactory->make();
        $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

        if (isset($token['error'])) {
            return redirect()->route('settings')
                ->with('error', 'Google connection failed: '.$token['error_description']);
        }

        $expiresAt = isset($token['expires_in'])
            ? Carbon::now()->addSeconds($token['expires_in'])
            : null;

        // Identify the linked Google account by email so multi-account works.
        // userinfo lookup runs against the freshly issued token; if it fails we
        // fall back to a per-user placeholder so the connection still saves.
        $client->setAccessToken($token);
        $email = null;
        try {
            $oauth = new Oauth2($client);
            $info = $oauth->userinfo->get();
            $email = $info->getEmail();
        } catch (\Throwable) {
            // ignore — fallback below
        }

        $user = $request->user();
        $identifier = $email ?: "primary-{$user->id}";
        $label = $email ?: 'Google account';

        $connection = $user->connections()
            ->where('provider', Connection::PROVIDER_GOOGLE)
            ->where('identifier', $identifier)
            ->first();

        $credentials = [
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? ($connection?->credential('refresh_token')),
            'expires_at' => $expiresAt?->toIso8601String(),
            'scopes' => $token['scope'] ?? '',
        ];

        if ($connection !== null) {
            $connection->update([
                'label' => $label,
                'credentials' => $credentials,
                'enabled' => true,
            ]);
        } else {
            $user->connections()->create([
                'context_id' => $user->defaultContext()?->id,
                'provider' => Connection::PROVIDER_GOOGLE,
                'capabilities' => [Connection::CAPABILITY_MAIL, Connection::CAPABILITY_CALENDAR],
                'label' => $label,
                'identifier' => $identifier,
                'credentials' => $credentials,
                'is_default' => ! $user->hasConnectionFor(Connection::PROVIDER_GOOGLE),
                'enabled' => true,
            ]);
        }

        // Promote the just-linked account to default if there is no default
        // yet (covers the race where the legacy "primary-{id}" placeholder was
        // promoted by the migration but the user has now reconnected).
        $user->connections()
            ->where('provider', Connection::PROVIDER_GOOGLE)
            ->where('identifier', $identifier)
            ->update(['is_default' => true]);
        $user->connections()
            ->where('provider', Connection::PROVIDER_GOOGLE)
            ->where('identifier', '!=', $identifier)
            ->update(['is_default' => false]);

        return redirect()->route('settings')->with('success', "Google account {$label} connected.");
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->connections()
            ->where('provider', Connection::PROVIDER_GOOGLE)
            ->delete();

        return redirect()->route('settings')->with('success', 'All Google accounts disconnected.');
    }
}
