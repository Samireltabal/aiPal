<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Memory;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Embeddings;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_embed_text_returns_array_of_floats(): void
    {
        Embeddings::fake();

        $service = new EmbeddingService;
        $result = $service->embedText('Hello world');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_store_memory_creates_memory_record(): void
    {
        Embeddings::fake();

        $user = User::factory()->create();
        $service = new EmbeddingService;

        $memory = $service->storeMemory($user, 'User is a backend engineer.');

        $this->assertInstanceOf(Memory::class, $memory);
        $this->assertSame($user->id, $memory->user_id);
        $this->assertSame('User is a backend engineer.', $memory->content);
        $this->assertIsArray($memory->embedding);
        $this->assertNull($memory->source);
    }

    public function test_store_memory_with_source(): void
    {
        Embeddings::fake();

        $user = User::factory()->create();
        $service = new EmbeddingService;

        $memory = $service->storeMemory($user, 'User uses Laravel.', source: 'conversation:abc123');

        $this->assertSame('conversation:abc123', $memory->source);
    }

    public function test_generated_embedding_is_called_once_per_text(): void
    {
        Embeddings::fake();

        $service = new EmbeddingService;
        $service->embedText('Some text to embed');

        Embeddings::assertGenerated(
            fn ($prompt) => $prompt->contains('Some text to embed')
        );
    }
}
