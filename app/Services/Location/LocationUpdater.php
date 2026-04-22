<?php

declare(strict_types=1);

namespace App\Services\Location;

use App\Models\User;

class LocationUpdater
{
    public const THROTTLE_MINUTES = 10;

    public function __construct(
        private readonly GeocodingService $geocoder,
    ) {}

    /**
     * Save a new location for the user, with reverse-geocoding and throttling.
     *
     * @param  string  $source  'browser' | 'whatsapp' | 'telegram' | 'manual' | 'maps_url'
     * @param  bool  $force  Bypass the 10-min throttle (e.g. for manual saves)
     * @return array{updated: bool, name: ?string, timezone: ?string}
     */
    public function updateFromCoordinates(
        User $user,
        float $latitude,
        float $longitude,
        string $source,
        bool $force = false,
    ): array {
        if (! $this->isValidCoordinate($latitude, $longitude)) {
            return ['updated' => false, 'name' => null, 'timezone' => null];
        }

        if (! $force && $user->location_updated_at !== null) {
            $throttleCutoff = now()->subMinutes(self::THROTTLE_MINUTES);
            if ($user->location_updated_at->greaterThan($throttleCutoff)) {
                return [
                    'updated' => false,
                    'name' => $user->location_name,
                    'timezone' => $user->briefing_timezone,
                ];
            }
        }

        $geo = $this->geocoder->reverse($latitude, $longitude);

        $name = $geo['name'] ?? sprintf('%.4f, %.4f', $latitude, $longitude);
        $timezone = $geo['timezone'] ?? $user->briefing_timezone ?? 'UTC';

        $user->update([
            'latitude' => round($latitude, 6),
            'longitude' => round($longitude, 6),
            'location_name' => $name,
            'location_source' => $source,
            'location_updated_at' => now(),
            'briefing_timezone' => $timezone,
        ]);

        return ['updated' => true, 'name' => $name, 'timezone' => $timezone];
    }

    /**
     * Save a new location by looking up a city name.
     *
     * @return array{updated: bool, name: ?string, timezone: ?string}
     */
    public function updateFromCityName(User $user, string $cityName, string $source = 'manual'): array
    {
        $geo = $this->geocoder->forward($cityName);

        if ($geo === null) {
            return ['updated' => false, 'name' => null, 'timezone' => null];
        }

        return $this->updateFromCoordinates(
            $user,
            $geo['latitude'],
            $geo['longitude'],
            $source,
            force: true,
        );
    }

    public function clear(User $user): void
    {
        $user->update([
            'latitude' => null,
            'longitude' => null,
            'location_name' => null,
            'location_source' => null,
            'location_updated_at' => null,
        ]);
    }

    private function isValidCoordinate(float $lat, float $lon): bool
    {
        return $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180
            && ! ($lat === 0.0 && $lon === 0.0);
    }
}
