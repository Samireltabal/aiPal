<?php

declare(strict_types=1);

namespace Tests\Feature\Extension;

use App\Livewire\Settings\Extension;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsExtensionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
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

    public function test_page_requires_authentication(): void
    {
        $this->get('/settings/extension')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_page(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get('/settings/extension')->assertOk();
    }

    public function test_generating_creates_extension_token_with_ability(): void
    {
        $user = $this->makeUser();

        Livewire::actingAs($user)
            ->test(Extension::class)
            ->call('generate')
            ->assertSet('generatedToken', fn ($v) => is_string($v) && strlen($v) > 20);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'extension',
        ]);

        $token = $user->tokens()->where('name', 'extension')->first();
        $this->assertContains('extension', $token->abilities ?? []);
    }

    public function test_generating_replaces_previous_extension_token(): void
    {
        $user = $this->makeUser();

        Livewire::actingAs($user)->test(Extension::class)->call('generate');
        Livewire::actingAs($user)->test(Extension::class)->call('generate');

        $this->assertSame(1, $user->tokens()->where('name', 'extension')->count());
    }

    public function test_revoke_removes_token(): void
    {
        $user = $this->makeUser();
        Livewire::actingAs($user)->test(Extension::class)->call('generate');
        $tokenId = $user->tokens()->where('name', 'extension')->value('id');

        Livewire::actingAs($user)->test(Extension::class)->call('revoke', $tokenId);

        $this->assertSame(0, $user->tokens()->where('name', 'extension')->count());
    }

    public function test_revoke_does_not_touch_other_users_tokens(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        Livewire::actingAs($user)->test(Extension::class)->call('generate');
        $otherToken = $other->createToken('extension', ['extension']);

        Livewire::actingAs($user)->test(Extension::class)->call('revoke', $otherToken->accessToken->id);

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    }
}
