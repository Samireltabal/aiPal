<?php

declare(strict_types=1);

namespace Tests\Feature\Weather;

use App\Ai\Tools\GetWeatherTool;
use App\Models\User;
use App\Services\Location\GeocodingService;
use App\Services\Weather\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class GetWeatherToolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_returns_helpful_message_when_no_location_and_no_argument(): void
    {
        $user = User::factory()->create();
        $tool = new GetWeatherTool($user, new WeatherService, new GeocodingService);

        $request = new Request(['location' => null, 'when' => 'now']);
        $response = (string) $tool->handle($request);

        $this->assertStringContainsString("don't know your location", $response);
    }

    public function test_returns_forecast_for_user_saved_location(): void
    {
        Http::fake([
            'api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => [
                    'temperature_2m' => 32,
                    'apparent_temperature' => 35,
                    'relative_humidity_2m' => 40,
                    'wind_speed_10m' => 10,
                    'weather_code' => 0,
                ],
                'daily' => [
                    'temperature_2m_max' => [36],
                    'temperature_2m_min' => [24],
                    'precipitation_probability_max' => [0],
                ],
            ]),
        ]);

        $user = User::factory()->create([
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'location_name' => 'Riyadh',
            'briefing_timezone' => 'Asia/Riyadh',
        ]);

        $tool = new GetWeatherTool($user, new WeatherService, new GeocodingService);
        $request = new Request(['location' => null, 'when' => 'now']);

        $response = (string) $tool->handle($request);

        $this->assertStringContainsString('Riyadh', $response);
        $this->assertStringContainsString('32', $response);
    }

    public function test_returns_forecast_for_explicit_location(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/v1/search*' => Http::response([
                'results' => [[
                    'name' => 'Tokyo',
                    'country' => 'Japan',
                    'latitude' => 35.68,
                    'longitude' => 139.69,
                    'timezone' => 'Asia/Tokyo',
                ]],
            ]),
            'api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => ['temperature_2m' => 18, 'weather_code' => 2, 'relative_humidity_2m' => 70, 'wind_speed_10m' => 5],
                'daily' => ['temperature_2m_max' => [20], 'temperature_2m_min' => [12], 'precipitation_probability_max' => [10]],
            ]),
        ]);

        $user = User::factory()->create();
        $tool = new GetWeatherTool($user, new WeatherService, new GeocodingService);
        $request = new Request(['location' => 'Tokyo', 'when' => 'now']);

        $response = (string) $tool->handle($request);

        $this->assertStringContainsString('Tokyo', $response);
        $this->assertStringContainsString('18', $response);
    }

    public function test_returns_error_for_unknown_location(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/v1/search*' => Http::response([], 200),
        ]);

        $user = User::factory()->create();
        $tool = new GetWeatherTool($user, new WeatherService, new GeocodingService);
        $request = new Request(['location' => 'Nowhereville', 'when' => 'now']);

        $response = (string) $tool->handle($request);

        $this->assertStringContainsString("couldn't find", $response);
    }
}
