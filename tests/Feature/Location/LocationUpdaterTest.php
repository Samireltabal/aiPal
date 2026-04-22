<?php

declare(strict_types=1);

namespace Tests\Feature\Location;

use App\Models\User;
use App\Services\Location\GeocodingService;
use App\Services\Location\LocationUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationUpdaterTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_from_coordinates_saves_and_reverse_geocodes(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/v1/reverse*' => Http::response([
                'results' => [[
                    'name' => 'Riyadh',
                    'admin1' => 'Riyadh Province',
                    'country' => 'Saudi Arabia',
                    'latitude' => 24.71,
                    'longitude' => 46.68,
                    'timezone' => 'Asia/Riyadh',
                ]],
            ]),
        ]);

        $user = User::factory()->create();
        $updater = new LocationUpdater(new GeocodingService);

        $result = $updater->updateFromCoordinates($user, 24.7136, 46.6753, 'browser');

        $this->assertTrue($result['updated']);
        $this->assertStringContainsString('Riyadh', (string) $result['name']);
        $this->assertSame('Asia/Riyadh', $result['timezone']);

        $user->refresh();
        $this->assertEqualsWithDelta(24.7136, (float) $user->latitude, 0.0001);
        $this->assertSame('browser', $user->location_source);
        $this->assertNotNull($user->location_updated_at);
    }

    public function test_throttle_skips_write_within_10_minutes(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/v1/reverse*' => Http::response([
                'results' => [['name' => 'X', 'country' => 'Y', 'latitude' => 1, 'longitude' => 1, 'timezone' => 'UTC']],
            ]),
        ]);

        $user = User::factory()->create([
            'latitude' => 10.0,
            'longitude' => 20.0,
            'location_updated_at' => now()->subMinutes(2),
        ]);

        $updater = new LocationUpdater(new GeocodingService);
        $result = $updater->updateFromCoordinates($user, 50.0, 60.0, 'browser');

        $this->assertFalse($result['updated']);
        $user->refresh();
        $this->assertEqualsWithDelta(10.0, (float) $user->latitude, 0.0001);
    }

    public function test_force_bypasses_throttle(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/v1/reverse*' => Http::response([
                'results' => [['name' => 'New', 'country' => 'Place', 'latitude' => 50, 'longitude' => 60, 'timezone' => 'UTC']],
            ]),
        ]);

        $user = User::factory()->create([
            'latitude' => 10.0,
            'longitude' => 20.0,
            'location_updated_at' => now()->subMinutes(2),
        ]);

        $updater = new LocationUpdater(new GeocodingService);
        $result = $updater->updateFromCoordinates($user, 50.0, 60.0, 'manual', force: true);

        $this->assertTrue($result['updated']);
    }

    public function test_rejects_invalid_coordinates(): void
    {
        $user = User::factory()->create();
        $updater = new LocationUpdater(new GeocodingService);

        $this->assertFalse($updater->updateFromCoordinates($user, 200, 0, 'browser')['updated']);
        $this->assertFalse($updater->updateFromCoordinates($user, 0, -300, 'browser')['updated']);
        $this->assertFalse($updater->updateFromCoordinates($user, 0, 0, 'browser')['updated']);
    }

    public function test_clear_nulls_all_location_fields(): void
    {
        $user = User::factory()->create([
            'latitude' => 24.0,
            'longitude' => 46.0,
            'location_name' => 'Saved',
            'location_source' => 'browser',
            'location_updated_at' => now(),
        ]);

        (new LocationUpdater(new GeocodingService))->clear($user);

        $user->refresh();
        $this->assertNull($user->latitude);
        $this->assertNull($user->longitude);
        $this->assertNull($user->location_name);
    }

    public function test_update_from_city_name_forward_geocodes(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/v1/search*' => Http::response([
                'results' => [[
                    'name' => 'Paris',
                    'admin1' => 'Île-de-France',
                    'country' => 'France',
                    'latitude' => 48.8566,
                    'longitude' => 2.3522,
                    'timezone' => 'Europe/Paris',
                ]],
            ]),
            'geocoding-api.open-meteo.com/v1/reverse*' => Http::response([
                'results' => [[
                    'name' => 'Paris',
                    'country' => 'France',
                    'latitude' => 48.8566,
                    'longitude' => 2.3522,
                    'timezone' => 'Europe/Paris',
                ]],
            ]),
        ]);

        $user = User::factory()->create();
        $updater = new LocationUpdater(new GeocodingService);

        $result = $updater->updateFromCityName($user, 'Paris');

        $this->assertTrue($result['updated']);
        $user->refresh();
        $this->assertEqualsWithDelta(48.8566, (float) $user->latitude, 0.01);
        $this->assertSame('Europe/Paris', $user->briefing_timezone);
        $this->assertSame('manual', $user->location_source);
    }

    public function test_update_from_unknown_city_returns_not_updated(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/v1/search*' => Http::response([], 200),
        ]);

        $user = User::factory()->create();
        $updater = new LocationUpdater(new GeocodingService);

        $result = $updater->updateFromCityName($user, 'Nonexistent Place XYZ');

        $this->assertFalse($result['updated']);
    }
}
