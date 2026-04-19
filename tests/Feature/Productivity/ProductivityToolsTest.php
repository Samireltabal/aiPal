<?php

declare(strict_types=1);

namespace Tests\Feature\Productivity;

use App\Ai\Agents\Productivity\ReminderParserAgent;
use App\Ai\Tools\CreateNote;
use App\Ai\Tools\CreateReminder;
use App\Ai\Tools\CreateTask;
use App\Ai\Tools\ListTasks;
use App\Ai\Tools\SearchNotes;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class ProductivityToolsTest extends TestCase
{
    use RefreshDatabase;

    // — CreateNote —

    public function test_create_note_saves_note_with_embedding(): void
    {
        Embeddings::fake();

        $user = User::factory()->create();
        $tool = new CreateNote($user, new EmbeddingService);
        $result = $tool->handle(new Request(['title' => 'My Note', 'content' => 'Hello world']));

        $this->assertStringContainsString('Note saved', (string) $result);
        $this->assertDatabaseHas('notes', ['user_id' => $user->id, 'title' => 'My Note', 'content' => 'Hello world']);
    }

    public function test_create_note_without_title(): void
    {
        Embeddings::fake();

        $user = User::factory()->create();
        $tool = new CreateNote($user, new EmbeddingService);
        $result = $tool->handle(new Request(['title' => null, 'content' => 'Anonymous note']));

        $this->assertDatabaseHas('notes', ['user_id' => $user->id, 'title' => null]);
        $this->assertStringNotContainsString('as "', (string) $result);
    }

    // — SearchNotes —

    public function test_search_notes_returns_no_notes_message_when_empty(): void
    {
        Embeddings::fake();

        $user = User::factory()->create();
        $tool = new SearchNotes($user, new EmbeddingService);
        $result = $tool->handle(new Request(['query' => 'test']));

        $this->assertStringContainsString('No notes found', (string) $result);
    }

    public function test_search_notes_returns_results_when_notes_exist(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('Requires PostgreSQL with pgvector for vector similarity search.');
        }

        Embeddings::fake();

        $user = User::factory()->create();
        Note::create([
            'user_id' => $user->id,
            'title' => 'Laravel tips',
            'content' => 'Use eager loading.',
            'embedding' => array_fill(0, 1536, 0.1),
        ]);

        $tool = new SearchNotes($user, new EmbeddingService);
        $result = $tool->handle(new Request(['query' => 'Laravel']));

        $this->assertStringContainsString('Laravel tips', (string) $result);
    }

    // — CreateTask —

    public function test_create_task_saves_task(): void
    {
        $user = User::factory()->create();
        $tool = new CreateTask($user);
        $result = $tool->handle(new Request([
            'title' => 'Write tests',
            'description' => null,
            'priority' => 'high',
            'due_date' => null,
        ]));

        $this->assertStringContainsString('Write tests', (string) $result);
        $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => 'Write tests', 'priority' => 'high']);
    }

    public function test_create_task_defaults_priority_to_medium(): void
    {
        $user = User::factory()->create();
        $tool = new CreateTask($user);
        $tool->handle(new Request(['title' => 'Default task', 'description' => null, 'priority' => null, 'due_date' => null]));

        $this->assertDatabaseHas('tasks', ['title' => 'Default task', 'priority' => 'medium']);
    }

    // — ListTasks —

    public function test_list_tasks_returns_pending_tasks(): void
    {
        $user = User::factory()->create();
        Task::create(['user_id' => $user->id, 'title' => 'Pending task', 'priority' => 'medium']);
        Task::create(['user_id' => $user->id, 'title' => 'Done task', 'priority' => 'low', 'completed_at' => now()]);

        $tool = new ListTasks($user);
        $result = (string) $tool->handle(new Request(['include_completed' => false]));

        $this->assertStringContainsString('Pending task', $result);
        $this->assertStringNotContainsString('Done task', $result);
    }

    public function test_list_tasks_includes_completed_when_requested(): void
    {
        $user = User::factory()->create();
        Task::create(['user_id' => $user->id, 'title' => 'Done task', 'priority' => 'low', 'completed_at' => now()]);

        $tool = new ListTasks($user);
        $result = (string) $tool->handle(new Request(['include_completed' => true]));

        $this->assertStringContainsString('Done task', $result);
    }

    public function test_list_tasks_empty_message(): void
    {
        $user = User::factory()->create();
        $tool = new ListTasks($user);
        $result = (string) $tool->handle(new Request(['include_completed' => false]));

        $this->assertStringContainsString('No pending tasks', $result);
    }

    // — CreateReminder —

    public function test_create_reminder_parses_and_saves(): void
    {
        ReminderParserAgent::fake([[
            'title' => 'Review the PR',
            'body' => "Don't forget to review the PR",
            'remind_at' => now()->addHour()->toIso8601String(),
            'channel' => 'email',
        ]]);

        $user = User::factory()->create();
        $tool = new CreateReminder($user);
        $result = $tool->handle(new Request(['natural_language' => 'remind me to review the PR in 1 hour']));

        $this->assertStringContainsString('Review the PR', (string) $result);
        $this->assertDatabaseHas('reminders', ['user_id' => $user->id, 'title' => 'Review the PR', 'channel' => 'email']);
    }

    public function test_create_reminder_rejects_past_date(): void
    {
        ReminderParserAgent::fake([[
            'title' => 'Old reminder',
            'body' => null,
            'remind_at' => now()->subHour()->toIso8601String(),
            'channel' => 'email',
        ]]);

        $user = User::factory()->create();
        $tool = new CreateReminder($user);
        $result = $tool->handle(new Request(['natural_language' => 'remind me an hour ago']));

        $this->assertStringContainsString("couldn't parse", (string) $result);
        $this->assertDatabaseEmpty('reminders');
    }
}
