<?php

declare(strict_types=1);

namespace Tests\Feature\Location;

use App\Models\User;
use App\Services\Location\GeocodingService;
use App\Services\Location\LocationUpdater;
use App\Services\Location\MapsUrlParser;
use App\Services\Location\MessageLocationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessageLocationHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function makeHandler(): MessageLocationHandler
    {
        return new MessageLocationHandler(
            new LocationUpdater(new GeocodingService),
            new MapsUrlParser,
        );
    }

    public function test_native_share_saves_location_and_returns_confirmation(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/v1/reverse*' => Http::response([
                'results' => [['name' => 'Riyadh', 'country' => 'Saudi Arabia', 'latitude' => 24.7, 'longitude' => 46.7, 'timezone' => 'Asia/Riyadh']],
            ]),
        ]);

        $user = User::factory()->create();
        $msg = $this->makeHandler()->handleNativeShare($user, 24.7136, 46.6753, 'whatsapp');

        $this->assertNotNull($msg);
        $this->assertStringContainsString('Riyadh', $msg);

        $user->refresh();
        $this->assertSame('whatsapp', $user->location_source);
    }

    public function test_maps_url_in_text_saves_location_and_returns_confirmation(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/v1/reverse*' => Http::response([
                'results' => [['name' => 'Paris', 'country' => 'France', 'latitude' => 48.85, 'longitude' => 2.35, 'timezone' => 'Europe/Paris']],
            ]),
        ]);

        $user = User::factory()->create();
        $msg = $this->makeHandler()->handleTextMaybeContainingUrl(
            $user,
            "I'm here: https://maps.google.com/?q=48.8566,2.3522 come by",
            'maps_url',
        );

        $this->assertNotNull($msg);
        $this->assertStringContainsString('Paris', $msg);
    }

    public function test_text_without_url_returns_null(): void
    {
        $user = User::factory()->create();
        $msg = $this->makeHandler()->handleTextMaybeContainingUrl(
            $user,
            'Just a normal chat message with no link in it.',
            'maps_url',
        );

        $this->assertNull($msg);
    }

    public function test_text_with_non_maps_url_returns_null(): void
    {
        $user = User::factory()->create();
        $msg = $this->makeHandler()->handleTextMaybeContainingUrl(
            $user,
            'Check this out: https://example.com/page',
            'maps_url',
        );

        $this->assertNull($msg);
    }
}
