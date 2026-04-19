<?php

declare(strict_types=1);

namespace Tests\Feature\Voice;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Audio;
use Tests\TestCase;

class TtsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPersona(array $personaAttributes = []): User
    {
        $user = User::factory()->create();
        Persona::create(array_merge([
            'user_id' => $user->id,
            'assistant_name' => 'Aria',
            'tone' => 'friendly',
            'formality' => 'casual',
            'humor_level' => 'light',
            'backstory' => null,
            'system_prompt' => 'You are Aria.',
            'tts_voice' => 'alloy',
        ], $personaAttributes));

        return $user;
    }

    public function test_unauthenticated_request_redirects(): void
    {
        $response = $this->post(route('voice.tts'));

        $response->assertRedirect(route('login'));
    }

    public function test_text_field_is_required(): void
    {
        $user = $this->userWithPersona();

        $response = $this->actingAs($user)->postJson(route('voice.tts'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['text']);
    }

    public function test_voice_must_be_valid(): void
    {
        $user = $this->userWithPersona();

        $response = $this->actingAs($user)->postJson(route('voice.tts'), [
            'text' => 'Hello',
            'voice' => 'invalid-voice',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['voice']);
    }

    public function test_generates_audio_for_text(): void
    {
        Audio::fake();

        $user = $this->userWithPersona();

        $response = $this->actingAs($user)->postJson(route('voice.tts'), [
            'text' => 'Hello world',
            'voice' => 'alloy',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'audio/mpeg');

        Audio::assertGenerated(fn ($prompt) => $prompt->contains('Hello world'));
    }

    public function test_uses_persona_voice_when_voice_not_specified(): void
    {
        Audio::fake();

        $user = $this->userWithPersona(['tts_voice' => 'nova']);

        $response = $this->actingAs($user)->postJson(route('voice.tts'), [
            'text' => 'Testing voice',
        ]);

        $response->assertOk();
    }
}
