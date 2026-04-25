<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\JwtClaims;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around Microsoft Identity Platform v2.0 endpoints used during
 * the OAuth code-exchange flow plus a small Graph `/me` lookup to identify
 * the linked account.
 *
 * Token-refresh and Graph API calls live in dedicated services so this stays
 * focused on the OAuth handshake.
 */
class MicrosoftOAuthClient
{
    /**
     * Build the authorization URL the user is redirected to in order to
     * grant consent.
     */
    public function authorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->clientId(),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri(),
            'response_mode' => 'query',
            'scope' => implode(' ', $this->scopes()),
            'state' => $state,
            'prompt' => 'select_account',
        ];

        return $this->authority().'/oauth2/v2.0/authorize?'.http_build_query($params);
    }

    /**
     * Exchange an authorization code for an access token + refresh token.
     *
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->post($this->authority().'/oauth2/v2.0/token', [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
            'scope' => implode(' ', $this->scopes()),
        ]);

        return $response->json() ?? [];
    }

    /**
     * Exchange a refresh token for a fresh access token.
     *
     * @return array<string, mixed>
     */
    public function refresh(string $refreshToken): array
    {
        $response = Http::asForm()->post($this->authority().'/oauth2/v2.0/token', [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
            'scope' => implode(' ', $this->scopes()),
        ]);

        return $response->json() ?? [];
    }

    /**
     * Look up the signed-in user's profile so we can store an email-based
     * identifier on the Connection row.
     *
     * @return array<string, mixed>
     */
    public function fetchProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/me');

        return $response->successful() ? ($response->json() ?? []) : [];
    }

    /**
     * Decode the OpenID id_token (JWT) returned by the token endpoint. With
     * `openid email profile` scopes the payload includes claims like
     * `email`, `preferred_username`, `upn`, and `unique_name` — we use these
     * to identify the connected account without an extra Graph /me call,
     * which avoids needing the User.Read scope for personal accounts.
     *
     * @return array<string, mixed>
     */
    public function decodeIdToken(string $idToken): array
    {
        return JwtClaims::decode($idToken);
    }

    private function authority(): string
    {
        $tenant = config('services.microsoft.tenant', 'common');

        return 'https://login.microsoftonline.com/'.$tenant;
    }

    private function clientId(): string
    {
        return (string) config('services.microsoft.client_id');
    }

    private function clientSecret(): string
    {
        return (string) config('services.microsoft.client_secret');
    }

    private function redirectUri(): string
    {
        return (string) config('services.microsoft.redirect');
    }

    /**
     * @return array<int, string>
     */
    private function scopes(): array
    {
        $configured = config('services.microsoft.scopes', []);

        return is_array($configured) ? $configured : [];
    }
}
