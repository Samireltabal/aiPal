<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Location\LocationUpdater;
use App\Services\Weather\WeatherService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class WeatherCard extends Component
{
    public string $manualCity = '';

    public string $errorMessage = '';

    public function saveCity(): void
    {
        $this->errorMessage = '';
        $city = trim($this->manualCity);

        if ($city === '') {
            $this->errorMessage = 'Please enter a city name.';

            return;
        }

        $updater = app(LocationUpdater::class);
        $result = $updater->updateFromCityName(Auth::user(), $city, 'manual');

        if (! $result['updated']) {
            $this->errorMessage = "Couldn't find \"{$city}\". Try including the country (e.g. \"Paris, France\").";

            return;
        }

        $this->manualCity = '';
    }

    public function render(): View
    {
        $user = Auth::user()->fresh();
        $weather = app(WeatherService::class);

        $data = [
            'hasLocation' => $user->hasLocation(),
            'locationName' => $user->location_name,
            'updatedAt' => $user->location_updated_at,
            'current' => null,
            'today' => null,
            'hourly' => [],
            'daily' => [],
            'icon' => '🌡️',
            'description' => '',
        ];

        if ($user->hasLocation()) {
            $forecast = $weather->forecast(
                (float) $user->latitude,
                (float) $user->longitude,
                $user->briefing_timezone ?: 'UTC',
            );

            if ($forecast !== null) {
                $current = $forecast['current'] ?? [];
                $daily = $forecast['daily'] ?? [];
                $hourly = $forecast['hourly'] ?? [];

                $code = (int) ($current['weather_code'] ?? 0);
                $isDay = (int) ($current['is_day'] ?? 1) === 1;

                $data['current'] = [
                    'temperature' => $current['temperature_2m'] ?? null,
                    'feels_like' => $current['apparent_temperature'] ?? null,
                    'humidity' => $current['relative_humidity_2m'] ?? null,
                    'wind' => $current['wind_speed_10m'] ?? null,
                ];
                $data['icon'] = $weather->codeToIcon($code, $isDay);
                $data['description'] = $weather->codeToDescription($code);

                if (! empty($daily['temperature_2m_max'])) {
                    $data['today'] = [
                        'high' => $daily['temperature_2m_max'][0] ?? null,
                        'low' => $daily['temperature_2m_min'][0] ?? null,
                        'precipitation' => $daily['precipitation_probability_max'][0] ?? 0,
                    ];
                }

                // Next 12 hourly entries, starting from the current hour.
                $times = $hourly['time'] ?? [];
                $temps = $hourly['temperature_2m'] ?? [];
                $codes = $hourly['weather_code'] ?? [];

                $nowHour = now($user->briefing_timezone ?: 'UTC')->format('Y-m-d\TH:00');
                $startIndex = 0;
                foreach ($times as $i => $t) {
                    if ($t >= $nowHour) {
                        $startIndex = $i;
                        break;
                    }
                }

                for ($i = $startIndex; $i < min($startIndex + 12, count($times)); $i++) {
                    $data['hourly'][] = [
                        'time' => substr((string) $times[$i], 11, 5),
                        'temp' => $temps[$i] ?? null,
                        'icon' => $weather->codeToIcon((int) ($codes[$i] ?? 0), true),
                    ];
                }

                // 5-day forecast
                $dailyTimes = $daily['time'] ?? [];
                $dailyHighs = $daily['temperature_2m_max'] ?? [];
                $dailyLows = $daily['temperature_2m_min'] ?? [];
                $dailyCodes = $daily['weather_code'] ?? [];

                for ($i = 0; $i < min(5, count($dailyTimes)); $i++) {
                    $label = $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('D', strtotime((string) $dailyTimes[$i])));
                    $data['daily'][] = [
                        'label' => $label,
                        'high' => $dailyHighs[$i] ?? null,
                        'low' => $dailyLows[$i] ?? null,
                        'icon' => $weather->codeToIcon((int) ($dailyCodes[$i] ?? 0), true),
                    ];
                }
            }
        }

        return view('livewire.weather-card', $data);
    }
}
