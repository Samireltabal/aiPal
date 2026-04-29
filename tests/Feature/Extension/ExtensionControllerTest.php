<?php

declare(strict_types=1);

namespace Tests\Feature\Extension;

use App\Models\Context;
use App\Models\Memory;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class ExtensionControllerTest extends TestCase
{
    use RefreshDatabase;

    private const ABILITY = 'extension';

    protected function setUp(): void
    {
        parent::setUp();

        $mock = Mockery::mock(EmbeddingService::class);
        $mock->allows('embedText')->andReturn(array_fill(0, 1536, 0.0));
        $this->app->instance(EmbeddingService::class, $mock);
    }

    public function test_ping_requires_authentication(): void
    {
        $this->getJson('/api/v1/extension/ping')->assertUnauthorized();
    }

    public function test_ping_requires_extension_ability(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // A wildcard token without the extension ability is rejected.
        // (We use a token without abilities at all to simulate a non-extension PAT.)
        Sanctum::actingAs($user, []);

        $this->getJson('/api/v1/extension/ping')->assertForbidden();
    }

    public function test_ping_returns_user_and_default_context(): void
    {
        $user = User::factory()->create(['name' => 'Sam', 'email' => 'sam@example.com']);
        $default = Context::factory()->for($user)->create(['is_default' => true, 'name' => 'Personal']);
        Context::factory()->for($user)->create(['is_default' => false, 'name' => 'Work']);

        Sanctum::actingAs($user, [self::ABILITY]);

        $this->getJson('/api/v1/extension/ping')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', 'Sam')
            ->assertJsonPath('user.email', 'sam@example.com')
            ->assertJsonPath('default_context.id', $default->id)
            ->assertJsonPath('default_context.name', 'Personal');
    }

    public function test_contexts_returns_user_contexts(): void
    {
        $user = User::factory()->create();
        Context::factory()->for($user)->create(['name' => 'Alpha']);
        Context::factory()->for($user)->create(['name' => 'Beta']);

        // Other user's context must not leak.
        $other = User::factory()->create();
        Context::factory()->for($other)->create(['name' => 'Other']);

        Sanctum::actingAs($user, [self::ABILITY]);

        $response = $this->getJson('/api/v1/extension/contexts')->assertOk();
        $names = collect($response->json('contexts'))->pluck('name')->all();

        $this->assertContains('Alpha', $names);
        $this->assertContains('Beta', $names);
        $this->assertNotContains('Other', $names);
    }

    public function test_capture_requires_kind(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [self::ABILITY]);

        $this->postJson('/api/v1/extension/capture', [
            'url' => 'https://example.com',
            'title' => 'Example',
        ])->assertUnprocessable()->assertJsonValidationErrors(['kind']);
    }

    public function test_capture_rejects_unknown_kind(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [self::ABILITY]);

        $this->postJson('/api/v1/extension/capture', [
            'kind' => 'malicious',
            'url' => 'https://example.com',
            'title' => 'X',
        ])->assertUnprocessable()->assertJsonValidationErrors(['kind']);
    }

    public function test_capture_rejects_oversized_payload(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [self::ABILITY]);

        $this->postJson('/api/v1/extension/capture', [
            'kind' => 'note',
            'url' => 'https://example.com',
            'title' => 'X',
            'article' => str_repeat('a', 100001),
        ])->assertUnprocessable()->assertJsonValidationErrors(['article']);
    }

    public function test_capture_memory_creates_record(): void
    {
        $user = User::factory()->create();
        $context = Context::factory()->for($user)->create(['is_default' => true]);
        Sanctum::actingAs($user, [self::ABILITY]);

        $response = $this->postJson('/api/v1/extension/capture', [
            'kind' => 'memory',
            'url' => 'https://example.com/article',
            'title' => 'Great Article',
            'prompt' => 'Important quote from the article',
            'selection' => 'Lorem ipsum dolor sit amet.',
            'context_id' => $context->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['id', 'kind', 'deep_link']);

        $this->assertDatabaseCount('memories', 1);
        $memory = Memory::first();
        $this->assertSame($user->id, $memory->user_id);
        $this->assertSame($context->id, $memory->context_id);
        $this->assertSame('extension', $memory->source);
        $this->assertStringContainsString('Lorem ipsum', $memory->content);
        $this->assertStringContainsString('https://example.com/article', $memory->content);
    }

    public function test_capture_task_creates_record(): void
    {
        $user = User::factory()->create();
        $context = Context::factory()->for($user)->create(['is_default' => true]);
        Sanctum::actingAs($user, [self::ABILITY]);

        $response = $this->postJson('/api/v1/extension/capture', [
            'kind' => 'task',
            'url' => 'https://example.com',
            'title' => 'Hacker News Top Story',
            'prompt' => 'Read this later',
            'context_id' => $context->id,
        ]);

        $response->assertCreated()->assertJsonPath('kind', 'task');

        $this->assertDatabaseCount('tasks', 1);
        $task = Task::first();
        $this->assertSame($user->id, $task->user_id);
        $this->assertSame($context->id, $task->context_id);
        $this->assertSame('Read this later', $task->title);
        $this->assertStringContainsString('https://example.com', $task->description);
    }

    public function test_capture_note_creates_record(): void
    {
        $user = User::factory()->create();
        $context = Context::factory()->for($user)->create(['is_default' => true]);
        Sanctum::actingAs($user, [self::ABILITY]);

        $response = $this->postJson('/api/v1/extension/capture', [
            'kind' => 'note',
            'url' => 'https://example.com',
            'title' => 'Meeting Notes',
            'article' => 'Long article content.',
            'context_id' => $context->id,
        ]);

        $response->assertCreated()->assertJsonPath('kind', 'note');

        $this->assertDatabaseCount('notes', 1);
        $note = Note::first();
        $this->assertSame('Meeting Notes', $note->title);
        $this->assertStringContainsString('Long article content.', $note->content);
        $this->assertStringContainsString('https://example.com', $note->content);
    }

    public function test_capture_reminder_creates_record(): void
    {
        $user = User::factory()->create();
        $context = Context::factory()->for($user)->create(['is_default' => true]);
        Sanctum::actingAs($user, [self::ABILITY]);

        $remindAt = now()->addHour()->toIso8601String();

        $response = $this->postJson('/api/v1/extension/capture', [
            'kind' => 'reminder',
            'url' => 'https://example.com',
            'title' => 'Follow up',
            'prompt' => 'Check the price on this',
            'remind_at' => $remindAt,
            'context_id' => $context->id,
        ]);

        $response->assertCreated()->assertJsonPath('kind', 'reminder');

        $this->assertDatabaseCount('reminders', 1);
        $reminder = Reminder::first();
        $this->assertSame('Check the price on this', $reminder->title);
        $this->assertNotNull($reminder->remind_at);
    }

    public function test_capture_falls_back_to_default_context_when_omitted(): void
    {
        $user = User::factory()->create();
        $default = Context::factory()->for($user)->create(['is_default' => true]);
        Context::factory()->for($user)->create(['is_default' => false]);

        Sanctum::actingAs($user, [self::ABILITY]);

        $this->postJson('/api/v1/extension/capture', [
            'kind' => 'note',
            'url' => 'https://example.com',
            'title' => 'X',
            'article' => 'hello',
        ])->assertCreated();

        $this->assertSame($default->id, Note::first()->context_id);
    }

    public function test_capture_rejects_context_belonging_to_other_user(): void
    {
        $user = User::factory()->create();
        Context::factory()->for($user)->create(['is_default' => true]);
        $other = User::factory()->create();
        $foreign = Context::factory()->for($other)->create();

        Sanctum::actingAs($user, [self::ABILITY]);

        $this->postJson('/api/v1/extension/capture', [
            'kind' => 'note',
            'url' => 'https://example.com',
            'title' => 'X',
            'article' => 'hello',
            'context_id' => $foreign->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['context_id']);
    }

    public function test_capture_rejects_token_without_extension_ability(): void
    {
        $user = User::factory()->create();
        Context::factory()->for($user)->create(['is_default' => true]);
        Sanctum::actingAs($user, ['some-other-ability']);

        $this->postJson('/api/v1/extension/capture', [
            'kind' => 'note',
            'url' => 'https://example.com',
            'title' => 'X',
            'article' => 'hello',
        ])->assertForbidden();
    }
}
