<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PremiumBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPersona(): User
    {
        $user = User::factory()->create();

        Persona::create([
            'user_id' => $user->id,
            'assistant_name' => 'Pal',
            'tone' => 'friendly',
            'formality' => 'casual',
            'humor_level' => 'none',
            'system_prompt' => 'You are helpful.',
        ]);

        return $user;
    }

    public function test_badge_is_hidden_when_premium_disabled(): void
    {
        config()->set('aipal.premium_enabled', false);

        $response = $this->actingAs($this->userWithPersona())->get('/');

        $response->assertOk();
        $response->assertDontSee('>Premium<', false);
    }

    public function test_badge_is_shown_when_premium_enabled(): void
    {
        config()->set('aipal.premium_enabled', true);

        $response = $this->actingAs($this->userWithPersona())->get('/');

        $response->assertOk();
        $response->assertSee('Premium');
    }
}
