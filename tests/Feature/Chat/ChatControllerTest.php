<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use App\Ai\Agents\Chat\ChatAgent;
use App\Models\User;
use App\Services\MemoryRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $mock = Mockery::mock(MemoryRetriever::class);
        $mock->allows('buildContextBlock')->andReturn('');
        $this->app->instance(MemoryRetriever::class, $mock);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/chat', ['message' => 'hello']);

        $response->assertUnauthorized();
    }

    public function test_message_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/chat', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_message_must_not_exceed_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/chat', [
            'message' => str_repeat('a', 32769),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_invalid_provider_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/chat', [
            'message' => 'hello',
            'provider' => 'invalid-provider',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['provider']);
    }

    public function test_invalid_conversation_id_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/chat', [
            'message' => 'hello',
            'conversation_id' => 'not-a-uuid',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['conversation_id']);
    }

    public function test_new_conversation_streams_response(): void
    {
        ChatAgent::fake(['Hello there!']);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/chat', [
            'message' => 'hello',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
    }

    public function test_existing_conversation_is_continued(): void
    {
        ChatAgent::fake(['Continuing...']);

        $user = User::factory()->create();

        // Create a conversation first
        $conversationId = $this->createConversation($user);

        $response = $this->actingAs($user)->postJson('/api/v1/chat', [
            'message' => 'continue the chat',
            'conversation_id' => $conversationId,
        ]);

        $response->assertOk();
    }

    public function test_conversations_are_listed(): void
    {
        ChatAgent::fake(['Hi!']);

        $user = User::factory()->create();

        // Create a conversation
        $this->actingAs($user)->postJson('/api/v1/chat', ['message' => 'first message']);

        $response = $this->actingAs($user)->getJson('/api/v1/conversations');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'total', 'current_page']);
    }

    public function test_user_cannot_list_other_users_conversations(): void
    {
        ChatAgent::fake(['Hi!']);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA)->postJson('/api/v1/chat', ['message' => 'hello from A']);

        $response = $this->actingAs($userB)->getJson('/api/v1/conversations');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_conversation_can_be_deleted(): void
    {
        ChatAgent::fake(['Hi!']);

        $user = User::factory()->create();

        $conversationId = $this->createConversation($user);

        $response = $this->actingAs($user)->deleteJson("/api/v1/conversations/{$conversationId}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('agent_conversations', ['id' => $conversationId]);
    }

    public function test_user_cannot_delete_other_users_conversation(): void
    {
        ChatAgent::fake(['Hi!']);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversationId = $this->createConversation($userA);

        $response = $this->actingAs($userB)->deleteJson("/api/v1/conversations/{$conversationId}");

        $response->assertNotFound();
    }

    public function test_conversations_can_be_searched_by_title(): void
    {
        $user = User::factory()->create();

        $matchId = (string) Str::uuid();
        $noMatchId = (string) Str::uuid();

        DB::table('agent_conversations')->insert([
            ['id' => $matchId, 'user_id' => $user->id, 'title' => 'Quarterly planning session', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $noMatchId, 'user_id' => $user->id, 'title' => 'Unrelated topic', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/conversations/search?q=quarterly');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($matchId, $ids);
        $this->assertNotContains($noMatchId, $ids);
    }

    public function test_conversations_can_be_searched_by_message_content(): void
    {
        $user = User::factory()->create();

        $matchId = (string) Str::uuid();
        $noMatchId = (string) Str::uuid();

        DB::table('agent_conversations')->insert([
            ['id' => $matchId, 'user_id' => $user->id, 'title' => 'Generic title', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $noMatchId, 'user_id' => $user->id, 'title' => 'Another title', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('agent_conversation_messages')->insert([
            'id' => (string) Str::uuid(),
            'conversation_id' => $matchId,
            'user_id' => $user->id,
            'agent' => 'chat',
            'role' => 'user',
            'content' => 'Can you help me with my deployment pipeline?',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/conversations/search?q=deployment');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($matchId, $ids);
        $this->assertNotContains($noMatchId, $ids);
    }

    public function test_search_requires_minimum_two_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/conversations/search?q=a');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_search_does_not_return_other_users_conversations(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        DB::table('agent_conversations')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userA->id,
            'title' => 'secret planning',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($userB)->getJson('/api/v1/conversations/search?q=planning');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    private function createConversation(User $user): string
    {
        $id = (string) Str::uuid();

        DB::table('agent_conversations')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'title' => 'Test conversation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
