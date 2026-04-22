<?php

declare(strict_types=1);

namespace App\Services\Weather;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps the Open-Meteo forecast API (https://open-meteo.com/en/docs).
 * Free, no API key. Results cached per-coordinate for 10 minutes.
 */
class WeatherService
{
    private const FORECAST_URL = 'https://api.open-meteo.com/v1/forecast';

    private const CACHE_TTL_SECONDS = 600;

    /**
     * @return array<string, mixed>|null
     */
    public function forecast(float $latitude, float $longitude, string $timezone = 'UTC'): ?array
    {
        $cacheKey = sprintf('weather:%.3f:%.3f:%s', $latitude, $longitude, $timezone);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($latitude, $longitude, $timezone) {
            try {
                $response = Http::timeout(10)->get(self::FORECAST_URL, [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'timezone' => $timezone,
                    'current' => 'temperature_2m,apparent_temperature,relative_humidity_2m,wind_speed_10m,wind_direction_10m,weather_code,is_day',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,sunrise,sunset',
                    'hourly' => 'temperature_2m,weather_code,precipitation_probability',
                    'forecast_days' => 5,
                ]);

                if (! $response->successful()) {
                    return null;
                }

                return $response->json();
            } catch (\Throwable $e) {
                Log::warning('Weather forecast fetch failed', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Human-readable description for a WMO weather code.
     * Reference: https://open-meteo.com/en/docs (weather_code)
     */
    public function codeToDescription(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear sky',
            $code === 1 => 'Mainly clear',
            $code === 2 => 'Partly cloudy',
            $code === 3 => 'Overcast',
            $code === 45, $code === 48 => 'Foggy',
            $code >= 51 && $code <= 57 => 'Drizzle',
            $code >= 61 && $code <= 67 => 'Rain',
            $code >= 71 && $code <= 77 => 'Snow',
            $code >= 80 && $code <= 82 => 'Rain showers',
            $code >= 85 && $code <= 86 => 'Snow showers',
            $code >= 95 && $code <= 99 => 'Thunderstorm',
            default => 'Unknown conditions',
        };
    }

    public function codeToIcon(int $code, bool $isDay = true): string
    {
        return match (true) {
            $code === 0 => $isDay ? '☀️' : '🌙',
            $code === 1, $code === 2 => $isDay ? '🌤️' : '☁️',
            $code === 3 => '☁️',
            $code === 45, $code === 48 => '🌫️',
            $code >= 51 && $code <= 67 => '🌧️',
            $code >= 71 && $code <= 77 => '❄️',
            $code >= 80 && $code <= 82 => '🌦️',
            $code >= 85 && $code <= 86 => '🌨️',
            $code >= 95 && $code <= 99 => '⛈️',
            default => '🌡️',
        };
    }

    /**
     * Build a compact natural-language summary from a forecast response.
     * Used by the weather tool.
     *
     * @param  array<string, mixed>  $forecast
     */
    public function formatSummary(array $forecast, string $locationName, string $when = 'now'): string
    {
        $current = $forecast['current'] ?? [];
        $daily = $forecast['daily'] ?? [];

        $temp = $current['temperature_2m'] ?? null;
        $feels = $current['apparent_temperature'] ?? null;
        $humidity = $current['relative_humidity_2m'] ?? null;
        $wind = $current['wind_speed_10m'] ?? null;
        $code = (int) ($current['weather_code'] ?? 0);
        $conditions = $this->codeToDescription($code);

        $highs = $daily['temperature_2m_max'] ?? [];
        $lows = $daily['temperature_2m_min'] ?? [];
        $precip = $daily['precipitation_probability_max'] ?? [];
        $codes = $daily['weather_code'] ?? [];

        $lines = [];

        if ($when === 'now' || $when === 'today') {
            $lines[] = "In {$locationName} right now: {$temp}°C, ".strtolower($conditions).
                ($humidity !== null ? ", humidity {$humidity}%" : '').
                ($wind !== null ? ", wind {$wind} km/h" : '').
                ($feels !== null && abs(((float) $feels) - ((float) $temp)) > 1 ? " (feels like {$feels}°C)" : '').
                '.';
        }

        if (($when === 'today' || $when === 'now') && isset($highs[0], $lows[0])) {
            $lines[] = "Today: high {$highs[0]}°C, low {$lows[0]}°C".
                (($precip[0] ?? 0) > 30 ? ", chance of precipitation {$precip[0]}%" : ', no rain expected').'.';
        }

        if ($when === 'tomorrow' && isset($highs[1], $lows[1])) {
            $tomorrowConditions = strtolower($this->codeToDescription((int) ($codes[1] ?? 0)));
            $lines[] = "Tomorrow in {$locationName}: {$tomorrowConditions}, high {$highs[1]}°C, low {$lows[1]}°C".
                (($precip[1] ?? 0) > 30 ? ", {$precip[1]}% chance of precipitation" : '').'.';
        }

        if ($when === 'week') {
            $lines[] = "Next 5 days in {$locationName}:";
            $days = min(5, count($highs));
            for ($i = 0; $i < $days; $i++) {
                $dayLabel = $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('D', strtotime("+{$i} days")));
                $dayConditions = $this->codeToDescription((int) ($codes[$i] ?? 0));
                $lines[] = "  • {$dayLabel}: {$dayConditions}, {$lows[$i]}-{$highs[$i]}°C";
            }
        }

        return implode("\n", $lines);
    }
}
