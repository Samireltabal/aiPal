<?php

namespace App\Http\Controllers\Google;

use App\Models\GoogleToken;
use App\Services\GoogleClientFactory;
use Google\Service\Calendar;
use Google\Service\Gmail;
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

        GoogleToken::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => $expiresAt,
                'scopes' => $token['scope'] ?? '',
            ],
        );

        return redirect()->route('settings')->with('success', 'Google Calendar connected successfully.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->googleToken?->delete();

        return redirect()->route('settings')->with('success', 'Google Calendar disconnected.');
    }
}
