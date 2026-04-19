<?php

declare(strict_types=1);

namespace Tests\Feature\Memory;

use App\Livewire\Memories;
use App\Models\Memory;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Ai\Embeddings;
use Livewire\Livewire;
use Tests\TestCase;

class MemoriesExportImportTest extends TestCase
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
            'backstory' => null,
            'system_prompt' => 'You are Aria.',
        ]);

        return $user;
    }

    public function test_unauthenticated_user_cannot_export_memories(): void
    {
        $this->get(route('memories.export'))->assertRedirect(route('login'));
    }

    public function test_export_returns_json_with_memories(): void
    {
        $user = $this->userWithPersona();

        Memory::create([
            'user_id' => $user->id,
            'content' => 'User is a backend engineer.',
            'embedding' => array_fill(0, 1536, 0.0),
        ]);

        Memory::create([
            'user_id' => $user->id,
            'content' => 'User prefers working at night.',
            'embedding' => array_fill(0, 1536, 0.0),
        ]);

        $response = $this->actingAs($user)->get(route('memories.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('memories', $data);
        $this->assertCount(2, $data['memories']);
        $this->assertSame('User is a backend engineer.', $data['memories'][0]['content']);
    }

    public function test_export_does_not_include_other_users_memories(): void
    {
        $user = $this->userWithPersona();
        $other = User::factory()->create();

        Memory::create([
            'user_id' => $user->id,
            'content' => 'My memory.',
            'embedding' => array_fill(0, 1536, 0.0),
        ]);

        Memory::create([
            'user_id' => $other->id,
            'content' => 'Other user memory.',
            'embedding' => array_fill(0, 1536, 0.0),
        ]);

        $response = $this->actingAs($user)->get(route('memories.export'));
        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['memories']);
        $this->assertSame('My memory.', $data['memories'][0]['content']);
    }

    public function test_memories_page_requires_auth(): void
    {
        $this->get(route('memories'))->assertRedirect(route('login'));
    }

    public function test_memories_page_renders_for_authenticated_user(): void
    {
        $user = $this->userWithPersona();

        $this->actingAs($user)->get(route('memories'))->assertOk();
    }

    public function test_import_adds_new_memories(): void
    {
        Embeddings::fake();

        $user = $this->userWithPersona();

        $json = json_encode([
            'memories' => [
                ['content' => 'User loves hiking.'],
                ['content' => 'User has a dog named Max.'],
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('memories.json', $json);

        Livewire::actingAs($user)->test(Memories::class)
            ->set('importFile', $file)
            ->call('import')
            ->assertSet('importSuccess', true)
            ->assertSet('importedCount', 2);

        $this->assertDatabaseCount('memories', 2);
    }

    public function test_import_skips_duplicates(): void
    {
        Embeddings::fake();

        $user = $this->userWithPersona();

        Memory::create([
            'user_id' => $user->id,
            'content' => 'User loves hiking.',
            'embedding' => array_fill(0, 1536, 0.0),
        ]);

        $json = json_encode([
            'memories' => [
                ['content' => 'User loves hiking.'],
                ['content' => 'User has a dog named Max.'],
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('memories.json', $json);

        Livewire::actingAs($user)->test(Memories::class)
            ->set('importFile', $file)
            ->call('import')
            ->assertSet('importedCount', 1);

        $this->assertDatabaseCount('memories', 2);
    }

    public function test_import_rejects_invalid_json(): void
    {
        $user = $this->userWithPersona();

        $file = UploadedFile::fake()->createWithContent('memories.json', 'not valid json');

        Livewire::actingAs($user)->test(Memories::class)
            ->set('importFile', $file)
            ->call('import')
            ->assertSet('importSuccess', false)
            ->assertSet('importError', 'Invalid format — expected {"memories": [...]} JSON.');
    }
}
