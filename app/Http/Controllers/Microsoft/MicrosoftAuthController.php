<?php

declare(strict_types=1);

namespace App\Http\Controllers\Microsoft;

use App\Models\Connection;
use App\Services\MicrosoftOAuthClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MicrosoftAuthController
{
    public function __construct(private readonly MicrosoftOAuthClient $oauth) {}

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('microsoft_oauth_state', $state);

        return redirect()->away($this->oauth->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $expectedState = $request->session()->pull('microsoft_oauth_state');
        if ($expectedState !== null && $request->input('state') !== $expectedState) {
            return redirect()->route('settings')
                ->with('error', 'Microsoft connection failed: invalid state.');
        }

        $token = $this->oauth->exchangeCode($request->input('code'));

        if (! isset($token['access_token'])) {
            $description = $token['error_description'] ?? $token['error'] ?? 'unknown error';

            return redirect()->route('settings')
                ->with('error', 'Microsoft connection failed: '.$description);
        }

        $expiresAt = isset($token['expires_in'])
            ? Carbon::now()->addSeconds((int) $token['expires_in'])
            : null;

        $profile = $this->oauth->fetchProfile($token['access_token']);
        $email = $profile['mail']
            ?? $profile['userPrincipalName']
            ?? null;

        $user = $request->user();
        $identifier = $email ?: 'primary-'.$user->id;
        $label = $email ?: 'Microsoft account';

        $connection = $user->connections()
            ->where('provider', Connection::PROVIDER_MICROSOFT)
            ->where('identifier', $identifier)
            ->first();

        $credentials = [
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? $connection?->credential('refresh_token'),
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
                'provider' => Connection::PROVIDER_MICROSOFT,
                'capabilities' => [Connection::CAPABILITY_MAIL, Connection::CAPABILITY_CALENDAR],
                'label' => $label,
                'identifier' => $identifier,
                'credentials' => $credentials,
                'is_default' => ! $user->hasConnectionFor(Connection::PROVIDER_MICROSOFT),
                'enabled' => true,
            ]);
        }

        // Promote the just-linked account to default if there is no default
        // yet. Same pattern as Google.
        $user->connections()
            ->where('provider', Connection::PROVIDER_MICROSOFT)
            ->where('identifier', $identifier)
            ->update(['is_default' => true]);
        $user->connections()
            ->where('provider', Connection::PROVIDER_MICROSOFT)
            ->where('identifier', '!=', $identifier)
            ->update(['is_default' => false]);

        return redirect()->route('settings')
            ->with('success', "Microsoft account {$label} connected.");
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->connections()
            ->where('provider', Connection::PROVIDER_MICROSOFT)
            ->delete();

        return redirect()->route('settings')->with('success', 'All Microsoft accounts disconnected.');
    }
}
