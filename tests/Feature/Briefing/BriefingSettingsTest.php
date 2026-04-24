<?php

declare(strict_types=1);

namespace Tests\Feature\Briefing;

use App\Livewire\Settings;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BriefingSettingsTest extends TestCase
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

    public function test_settings_page_shows_briefing_section(): void
    {
        $user = $this->userWithPersona();

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('Morning Focus Cast')
            ->assertSee('Delivery time')
            ->assertSee('Google Calendar');
    }

    public function test_save_briefing_settings_persists_values(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->set('briefingEnabled', true)
            ->set('briefingTime', '07:30')
            ->set('briefingTimezone', 'Africa/Cairo')
            ->call('saveBriefingSettings');

        $user->refresh();
        $this->assertTrue($user->briefing_enabled);
        $this->assertSame('07:30', $user->briefing_time);
        $this->assertSame('Africa/Cairo', $user->briefing_timezone);
    }

    public function test_save_briefing_sets_briefing_saved_flag(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->set('briefingEnabled', false)
            ->set('briefingTime', '09:00')
            ->set('briefingTimezone', 'UTC')
            ->call('saveBriefingSettings')
            ->assertSet('briefingSaved', true);
    }

    public function test_invalid_timezone_fails_validation(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->set('briefingTimezone', 'Not/ATimezone')
            ->call('saveBriefingSettings')
            ->assertHasErrors(['briefingTimezone']);
    }
}
