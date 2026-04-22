<?php

declare(strict_types=1);

namespace App\Services\Location;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extracts {latitude, longitude} from a maps URL found in user input.
 *
 * Supported hosts (allowlisted — SSRF protection):
 *   google.com / www.google.com / maps.google.com
 *   goo.gl / maps.app.goo.gl (short links, redirect-resolved)
 *   maps.apple.com
 *
 * Short links are resolved via HTTP redirect with strict hop-by-hop host
 * allowlisting and private-IP blocking at every stage.
 */
class MapsUrlParser
{
    private const ALLOWED_HOSTS = [
        'google.com',
        'www.google.com',
        'maps.google.com',
        'goo.gl',
        'maps.app.goo.gl',
        'maps.apple.com',
    ];

    private const SHORTLINK_HOSTS = [
        'goo.gl',
        'maps.app.goo.gl',
    ];

    private const MAX_REDIRECTS = 3;

    /**
     * Parse the first recognizable maps URL from a free-text message.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function parseFromText(string $text): ?array
    {
        if (! preg_match('#https?://[^\s<>"\']+#i', $text, $matches)) {
            return null;
        }

        return $this->parseUrl($matches[0]);
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function parseUrl(string $url): ?array
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);

        if (! in_array($host, self::ALLOWED_HOSTS, true)) {
            return null;
        }

        if (in_array($host, self::SHORTLINK_HOSTS, true)) {
            $resolvedUrl = $this->resolveShortLink($url);
            if ($resolvedUrl === null) {
                return null;
            }

            return $this->extractFromKnownHost($resolvedUrl);
        }

        return $this->extractFromKnownHost($url);
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function extractFromKnownHost(string $url): ?array
    {
        $patterns = [
            '#/maps/(?:place/[^/]*/)?@(-?\d+\.\d+),(-?\d+\.\d+)#',
            '#[?&](?:q|ll|query)=(-?\d+\.\d+)(?:,| |%20|%2C)(-?\d+\.\d+)#i',
            '#[?&]center=(-?\d+\.\d+)(?:,|%2C)(-?\d+\.\d+)#i',
            '#[?&]daddr=(-?\d+\.\d+)(?:,|%2C)(-?\d+\.\d+)#i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $lat = (float) $matches[1];
                $lon = (float) $matches[2];

                if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180 && ! ($lat === 0.0 && $lon === 0.0)) {
                    return ['latitude' => $lat, 'longitude' => $lon];
                }
            }
        }

        return null;
    }

    /**
     * Follow redirects manually, validating every hop against the allowlist
     * AND rejecting any private/loopback IP target.
     */
    private function resolveShortLink(string $url): ?string
    {
        $current = $url;

        for ($hop = 0; $hop < self::MAX_REDIRECTS; $hop++) {
            $parts = parse_url($current);
            if ($parts === false || empty($parts['host'])) {
                return null;
            }

            $host = strtolower($parts['host']);
            if (! in_array($host, self::ALLOWED_HOSTS, true)) {
                Log::info('MapsUrlParser rejected redirect to non-allowlisted host', ['host' => $host]);

                return null;
            }

            if ($this->hostResolvesToPrivateIp($host)) {
                Log::warning('MapsUrlParser rejected redirect to private IP', ['host' => $host]);

                return null;
            }

            try {
                $response = Http::timeout(5)
                    ->withoutRedirecting()
                    ->withHeaders(['User-Agent' => 'aiPal/1.0 MapsUrlResolver'])
                    ->get($current);
            } catch (\Throwable $e) {
                Log::info('MapsUrlParser HTTP error during redirect follow', ['error' => $e->getMessage()]);

                return null;
            }

            $status = $response->status();

            // Non-redirect final response — URL is stable; if we've been hopping, return it.
            if ($status < 300 || $status >= 400) {
                return $current;
            }

            $location = $response->header('Location');
            if (! $location) {
                return null;
            }

            // Resolve relative redirects against the current URL
            $current = $this->resolveRelativeUrl($current, $location);
        }

        Log::info('MapsUrlParser hit max redirects', ['start' => $url]);

        return null;
    }

    private function resolveRelativeUrl(string $base, string $location): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $baseParts = parse_url($base);
        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';

        if (str_starts_with($location, '/')) {
            return "{$scheme}://{$host}{$location}";
        }

        return "{$scheme}://{$host}/{$location}";
    }

    private function hostResolvesToPrivateIp(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPrivateOrLoopbackIp($host);
        }

        $ips = @gethostbynamel($host);
        if ($ips === false) {
            return false;
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateOrLoopbackIp($ip)) {
                return true;
            }
        }

        return false;
    }

    private function isPrivateOrLoopbackIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }
}
