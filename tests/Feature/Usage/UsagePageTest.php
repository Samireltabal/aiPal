<?php

declare(strict_types=1);

namespace Tests\Feature\Usage;

use App\Livewire\Usage;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsagePageTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPersona(bool $admin = false): User
    {
        $user = User::factory()->create(['is_admin' => $admin]);

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

    public function test_authenticated_user_can_view_usage_page(): void
    {
        $this->actingAs($this->userWithPersona())
            ->get(route('usage'))
            ->assertOk()
            ->assertSee('Usage')
            ->assertSee('Active AI model configuration');
    }

    public function test_settings_page_no_longer_shows_ai_model_config_section(): void
    {
        $this->actingAs($this->userWithPersona())
            ->get(route('settings'))
            ->assertOk()
            ->assertDontSee('AI Model Configuration')
            ->assertSee('Usage &amp; Models', false);
    }

    public function test_non_admin_cannot_switch_to_global_scope(): void
    {
        $user = $this->userWithPersona(admin: false);

        Livewire::actingAs($user)
            ->test(Usage::class)
            ->call('setScope', 'global')
            ->assertSet('scope', 'me');
    }

    public function test_admin_can_switch_to_global_scope(): void
    {
        $user = $this->userWithPersona(admin: true);

        Livewire::actingAs($user)
            ->test(Usage::class)
            ->call('setScope', 'global')
            ->assertSet('scope', 'global');
    }

    public function test_range_is_clamped_to_allowed_values(): void
    {
        Livewire::actingAs($this->userWithPersona())
            ->test(Usage::class)
            ->call('setRange', 9999)
            ->assertSet('days', 30);
    }
}
