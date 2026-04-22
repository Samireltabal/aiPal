<?php

declare(strict_types=1);

namespace App\Services\Location;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps Open-Meteo geocoding endpoints (free, no API key).
 * Forward: city name → lat/lon + timezone
 * Reverse: lat/lon → nearest named place + timezone
 *
 * Docs: https://open-meteo.com/en/docs/geocoding-api
 */
class GeocodingService
{
    private const FORWARD_URL = 'https://geocoding-api.open-meteo.com/v1/search';

    private const REVERSE_URL = 'https://geocoding-api.open-meteo.com/v1/reverse';

    /**
     * @return array{name: string, latitude: float, longitude: float, timezone: string, country: ?string}|null
     */
    public function forward(string $query, string $language = 'en'): ?array
    {
        $query = trim($query);
        if ($query === '' || strlen($query) > 120) {
            return null;
        }

        try {
            $response = Http::timeout(8)->get(self::FORWARD_URL, [
                'name' => $query,
                'count' => 1,
                'language' => $language,
                'format' => 'json',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $result = data_get($response->json(), 'results.0');
            if (! is_array($result)) {
                return null;
            }

            return $this->normalize($result);
        } catch (\Throwable $e) {
            Log::warning('Forward geocoding failed', ['query' => $query, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{name: string, latitude: float, longitude: float, timezone: string, country: ?string}|null
     */
    public function reverse(float $latitude, float $longitude, string $language = 'en'): ?array
    {
        try {
            $response = Http::timeout(8)->get(self::REVERSE_URL, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'count' => 1,
                'language' => $language,
                'format' => 'json',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $result = data_get($response->json(), 'results.0');
            if (! is_array($result)) {
                return null;
            }

            return $this->normalize($result);
        } catch (\Throwable $e) {
            Log::warning('Reverse geocoding failed', [
                'lat' => $latitude,
                'lon' => $longitude,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array{name: string, latitude: float, longitude: float, timezone: string, country: ?string}
     */
    private function normalize(array $result): array
    {
        $parts = array_filter([
            $result['name'] ?? null,
            $result['admin1'] ?? null,
            $result['country'] ?? null,
        ]);

        return [
            'name' => mb_strimwidth(implode(', ', $parts), 0, 120, '…'),
            'latitude' => (float) ($result['latitude'] ?? 0),
            'longitude' => (float) ($result['longitude'] ?? 0),
            'timezone' => (string) ($result['timezone'] ?? 'UTC'),
            'country' => isset($result['country']) ? (string) $result['country'] : null,
        ];
    }
}
