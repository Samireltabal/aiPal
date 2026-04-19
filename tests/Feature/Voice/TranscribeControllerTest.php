<?php

declare(strict_types=1);

namespace Tests\Feature\Voice;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Ai\Transcription;
use Tests\TestCase;

class TranscribeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPersona(): User
    {
        $user = User::factory()->create();
        Persona::create([
            'user_id' => $user->id,
            'assistant_name' => 'Aria',
            'tone' => 'friendly',
            'formality' => 'casual',
            'humor_level' => 'light',
            'backstory' => null,
            'system_prompt' => 'You are Aria.',
        ]);

        return $user;
    }

    public function test_unauthenticated_request_redirects(): void
    {
        $response = $this->post(route('voice.transcribe'));

        $response->assertRedirect(route('login'));
    }

    public function test_audio_field_is_required(): void
    {
        $user = $this->userWithPersona();

        $response = $this->actingAs($user)->post(route('voice.transcribe'));

        $response->assertSessionHasErrors(['audio']);
    }

    public function test_transcribes_uploaded_audio(): void
    {
        Transcription::fake(['Hello from voice input.']);

        $user = $this->userWithPersona();
        $file = UploadedFile::fake()->create('recording.webm', 100, 'audio/webm');

        $response = $this->actingAs($user)->post(route('voice.transcribe'), [
            'audio' => $file,
        ]);

        $response->assertOk();
        $response->assertJson(['transcript' => 'Hello from voice input.']);
    }
}
