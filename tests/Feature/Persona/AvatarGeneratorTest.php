<?php

declare(strict_types=1);

namespace Tests\Feature\Persona;

use App\Jobs\GenerateAvatarJob;
use App\Livewire\Settings;
use App\Models\Persona;
use App\Models\User;
use App\Services\AvatarGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;
use Livewire\Livewire;
use Tests\TestCase;

class AvatarGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPersona(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        Persona::create([
            'user_id' => $user->id,
            'assistant_name' => 'Aria',
            'tone' => 'friendly',
            'formality' => 'casual',
            'humor_level' => 'light',
            'backstory' => 'A helpful coding assistant.',
            'system_prompt' => 'You are Aria.',
        ]);

        return $user;
    }

    public function test_generate_avatar_job_stores_image_and_updates_persona(): void
    {
        Image::fake();
        Storage::fake('public');

        $user = $this->userWithPersona();
        $persona = $user->persona;

        (new GenerateAvatarJob($persona->id))->handle(new AvatarGenerator);

        Storage::disk('public')->assertExists("avatars/persona-{$user->id}.png");

        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
            'avatar_path' => "avatars/persona-{$user->id}.png",
        ]);
    }

    public function test_generate_avatar_job_does_nothing_when_persona_not_found(): void
    {
        Image::fake();

        (new GenerateAvatarJob(999999))->handle(new AvatarGenerator);

        Image::assertNothingGenerated();
    }

    public function test_avatar_generation_prompt_contains_persona_name(): void
    {
        Image::fake();
        Storage::fake('public');

        $user = $this->userWithPersona();

        (new GenerateAvatarJob($user->persona->id))->handle(new AvatarGenerator);

        Image::assertGenerated(fn ($prompt) => $prompt->contains('Aria'));
    }

    public function test_settings_generate_avatar_button_dispatches_job(): void
    {
        Queue::fake();

        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('generateAvatar')
            ->assertSet('avatarQueued', true);

        Queue::assertPushed(GenerateAvatarJob::class, function ($job) use ($user) {
            return $job->personaId === $user->persona->id;
        });
    }

    public function test_settings_page_shows_avatar_url_when_set(): void
    {
        $user = $this->userWithPersona();
        $user->persona->update(['avatar_path' => 'avatars/persona-1.png']);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->assertSee('avatars/persona-1.png');
    }
}
