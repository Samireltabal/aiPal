<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Livewire\Settings;
use App\Models\Persona;
use App\Models\User;
use App\Models\UserToolSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToolSettingsTest extends TestCase
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

    public function test_settings_page_shows_tool_toggles(): void
    {
        $user = $this->userWithPersona();

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('AI Tools')
            ->assertSee('Create Note')
            ->assertSee('List Tasks');
    }

    public function test_toggle_tool_disables_an_enabled_tool(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('toggleTool', 'create_note');

        $this->assertDatabaseHas('user_tool_settings', [
            'user_id' => $user->id,
            'tool' => 'create_note',
            'enabled' => false,
        ]);
    }

    public function test_toggle_tool_re_enables_a_disabled_tool(): void
    {
        $user = $this->userWithPersona();

        UserToolSetting::create([
            'user_id' => $user->id,
            'tool' => 'create_task',
            'enabled' => false,
        ]);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('toggleTool', 'create_task');

        $this->assertDatabaseHas('user_tool_settings', [
            'user_id' => $user->id,
            'tool' => 'create_task',
            'enabled' => true,
        ]);
    }

    public function test_toggle_sets_tool_saved_flag(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('toggleTool', 'search_notes')
            ->assertSet('toolSaved', true);
    }

    public function test_unauthenticated_user_cannot_access_settings(): void
    {
        $this->get(route('settings'))->assertRedirect(route('login'));
    }
}
