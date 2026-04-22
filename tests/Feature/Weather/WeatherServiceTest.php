<?php

declare(strict_types=1);

namespace Tests\Feature\Weather;

use App\Services\Weather\WeatherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_forecast_returns_parsed_body_on_success(): void
    {
        Http::fake([
            'api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => ['temperature_2m' => 30, 'weather_code' => 1],
                'daily' => ['temperature_2m_max' => [35], 'temperature_2m_min' => [22]],
            ]),
        ]);

        $result = (new WeatherService)->forecast(24.7, 46.7, 'Asia/Riyadh');

        $this->assertNotNull($result);
        $this->assertSame(30, $result['current']['temperature_2m']);
    }

    public function test_forecast_returns_null_on_http_error(): void
    {
        Http::fake([
            'api.open-meteo.com/v1/forecast*' => Http::response('', 500),
        ]);

        $this->assertNull((new WeatherService)->forecast(24.7, 46.7));
    }

    public function test_forecast_is_cached_per_coord(): void
    {
        Http::fake([
            'api.open-meteo.com/v1/forecast*' => Http::sequence()
                ->push(['current' => ['temperature_2m' => 30]])
                ->push(['current' => ['temperature_2m' => 999]]),
        ]);

        $service = new WeatherService;

        $first = $service->forecast(24.7, 46.7, 'UTC');
        $second = $service->forecast(24.7, 46.7, 'UTC');

        $this->assertSame(30, $first['current']['temperature_2m']);
        $this->assertSame(30, $second['current']['temperature_2m']); // from cache, not 999
    }

    public function test_code_to_description_returns_known_strings(): void
    {
        $service = new WeatherService;

        $this->assertSame('Clear sky', $service->codeToDescription(0));
        $this->assertSame('Partly cloudy', $service->codeToDescription(2));
        $this->assertSame('Rain', $service->codeToDescription(63));
        $this->assertSame('Thunderstorm', $service->codeToDescription(95));
    }

    public function test_format_summary_includes_location_and_temperature(): void
    {
        $service = new WeatherService;
        $forecast = [
            'current' => [
                'temperature_2m' => 28,
                'apparent_temperature' => 30,
                'relative_humidity_2m' => 65,
                'wind_speed_10m' => 12,
                'weather_code' => 1,
            ],
            'daily' => [
                'temperature_2m_max' => [34],
                'temperature_2m_min' => [24],
                'precipitation_probability_max' => [10],
            ],
        ];

        $summary = $service->formatSummary($forecast, 'Riyadh', 'now');

        $this->assertStringContainsString('Riyadh', $summary);
        $this->assertStringContainsString('28', $summary);
        $this->assertStringContainsString('mainly clear', $summary);
    }
}
