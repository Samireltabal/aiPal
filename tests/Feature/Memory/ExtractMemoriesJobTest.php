<?php

declare(strict_types=1);

namespace Tests\Feature\Memory;

use App\Ai\Agents\Memory\MemoryExtractorAgent;
use App\Jobs\ExtractMemoriesJob;
use App\Models\Memory;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use Tests\TestCase;

class ExtractMemoriesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_does_nothing_when_user_not_found(): void
    {
        Embeddings::fake();
        MemoryExtractorAgent::fake([['facts' => []]]);

        (new ExtractMemoriesJob(999999, 'conv-abc'))->handle(new EmbeddingService);

        $this->assertDatabaseEmpty('memories');
        MemoryExtractorAgent::assertNeverPrompted();
    }

    public function test_job_does_nothing_when_no_messages(): void
    {
        Embeddings::fake();
        MemoryExtractorAgent::fake([['facts' => []]]);

        $user = User::factory()->create();

        (new ExtractMemoriesJob($user->id, 'conv-no-messages'))->handle(new EmbeddingService);

        $this->assertDatabaseEmpty('memories');
        MemoryExtractorAgent::assertNeverPrompted();
    }

    public function test_job_extracts_and_stores_facts(): void
    {
        Embeddings::fake();
        MemoryExtractorAgent::fake([['facts' => ['User is a software engineer.', 'User prefers Laravel.']]]);

        $user = User::factory()->create();
        $conversationId = 'conv-test-123';

        DB::table('agent_conversation_messages')->insert([
            'id' => 'msg-1',
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'agent' => 'ChatAgent',
            'role' => 'user',
            'content' => 'I am a software engineer who loves Laravel.',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new ExtractMemoriesJob($user->id, $conversationId))->handle(new EmbeddingService);

        $this->assertDatabaseCount('memories', 2);
        $this->assertDatabaseHas('memories', [
            'user_id' => $user->id,
            'content' => 'User is a software engineer.',
            'source' => 'conversation:'.$conversationId,
        ]);
        $this->assertDatabaseHas('memories', [
            'user_id' => $user->id,
            'content' => 'User prefers Laravel.',
        ]);
    }

    public function test_job_skips_duplicate_memories(): void
    {
        Embeddings::fake();
        MemoryExtractorAgent::fake([['facts' => ['User likes PHP.']]]);

        $user = User::factory()->create();
        $conversationId = 'conv-dup-test';

        Memory::create([
            'user_id' => $user->id,
            'content' => 'User likes PHP.',
            'embedding' => array_fill(0, 1536, 0.0),
        ]);

        DB::table('agent_conversation_messages')->insert([
            'id' => 'msg-dup',
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'agent' => 'ChatAgent',
            'role' => 'user',
            'content' => 'I like PHP.',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new ExtractMemoriesJob($user->id, $conversationId))->handle(new EmbeddingService);

        $this->assertDatabaseCount('memories', 1);
    }

    public function test_job_ignores_empty_facts(): void
    {
        Embeddings::fake();
        MemoryExtractorAgent::fake([['facts' => ['', '  ', 'Valid fact.']]]);

        $user = User::factory()->create();
        $conversationId = 'conv-empty-facts';

        DB::table('agent_conversation_messages')->insert([
            'id' => 'msg-empty',
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'agent' => 'ChatAgent',
            'role' => 'user',
            'content' => 'Hello.',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new ExtractMemoriesJob($user->id, $conversationId))->handle(new EmbeddingService);

        $this->assertDatabaseCount('memories', 1);
        $this->assertDatabaseHas('memories', ['content' => 'Valid fact.']);
    }
}
