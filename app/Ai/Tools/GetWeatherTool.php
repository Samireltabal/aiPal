<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\Location\GeocodingService;
use App\Services\Weather\WeatherService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetWeatherTool extends AiTool
{
    public function __construct(
        private readonly User $user,
        private readonly WeatherService $weather,
        private readonly GeocodingService $geocoder,
    ) {}

    public static function toolName(): string
    {
        return 'get_weather';
    }

    public static function toolLabel(): string
    {
        return 'Get Weather';
    }

    public static function toolCategory(): string
    {
        return 'utilities';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Get current weather and short-range forecast. Defaults to the user\'s saved location, or accepts an explicit city name. Use when the user asks about the weather, temperature, rain, forecast, "will it rain tomorrow", "how hot is it", etc.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()
                ->description('Optional city name (e.g. "Riyadh" or "Paris, France"). If omitted, uses the user\'s saved location.')
                ->nullable()
                ->required(),
            'when' => $schema->string()
                ->description('Time range for the forecast: "now", "today", "tomorrow", or "week".')
                ->enum(['now', 'today', 'tomorrow', 'week'])
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $location = $request['location'] ?? null;
        $when = $request['when'] ?? 'now';

        if ($location !== null && trim((string) $location) !== '') {
            $geo = $this->geocoder->forward((string) $location);
            if ($geo === null) {
                return "I couldn't find a place called \"{$location}\". Try a more specific name (e.g. \"Riyadh, Saudi Arabia\").";
            }

            $forecast = $this->weather->forecast($geo['latitude'], $geo['longitude'], $geo['timezone']);
            if ($forecast === null) {
                return 'Weather service is temporarily unavailable. Please try again in a moment.';
            }

            return $this->weather->formatSummary($forecast, $geo['name'], (string) $when);
        }

        if (! $this->user->hasLocation()) {
            return "I don't know your location yet. Open aiPal in a browser to set it (you can allow location access when prompted), share your location via WhatsApp or Telegram, or paste a Google Maps link. You can also specify a city in your question, e.g. \"what's the weather in Paris?\".";
        }

        $timezone = $this->user->briefing_timezone ?: 'UTC';
        $forecast = $this->weather->forecast((float) $this->user->latitude, (float) $this->user->longitude, $timezone);

        if ($forecast === null) {
            return 'Weather service is temporarily unavailable. Please try again in a moment.';
        }

        $name = $this->user->location_name ?? sprintf('%.2f, %.2f', $this->user->latitude, $this->user->longitude);

        return $this->weather->formatSummary($forecast, $name, (string) $when);
    }
}
