<?php

declare(strict_types=1);

namespace Tests\Feature\Location;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_rejected(): void
    {
        $response = $this->postJson('/api/v1/location', [
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_save_location(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/v1/reverse*' => Http::response([
                'results' => [['name' => 'Riyadh', 'country' => 'Saudi Arabia', 'latitude' => 24.7, 'longitude' => 46.7, 'timezone' => 'Asia/Riyadh']],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/location', [
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $response->assertOk();
        $response->assertJson(['updated' => true]);

        $user->refresh();
        $this->assertEqualsWithDelta(24.7136, (float) $user->latitude, 0.001);
    }

    public function test_invalid_latitude_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/location', [
            'latitude' => 200,
            'longitude' => 0,
        ]);

        $response->assertUnprocessable();
    }

    public function test_delete_clears_location(): void
    {
        $user = User::factory()->create([
            'latitude' => 10.0,
            'longitude' => 20.0,
            'location_name' => 'Somewhere',
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/v1/location');

        $response->assertOk();
        $user->refresh();
        $this->assertNull($user->latitude);
    }
}
