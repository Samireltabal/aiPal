<?php

declare(strict_types=1);

namespace Tests\Feature\Productivity;

use App\Livewire\Productivity;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Embeddings;
use Livewire\Livewire;
use Tests\TestCase;

class ProductivityLivewireTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPersona(): User
    {
        $user = User::factory()->create();
        $user->persona()->create([
            'assistant_name' => 'Pal',
            'tone' => 'friendly',
            'formality' => 'casual',
            'humor_level' => 'low',
            'backstory' => 'A helpful assistant.',
            'system_prompt' => 'You are Pal.',
        ]);

        return $user;
    }

    public function test_page_requires_auth(): void
    {
        $this->get(route('productivity'))->assertRedirect(route('login'));
    }

    public function test_page_renders_for_authenticated_user(): void
    {
        $user = $this->userWithPersona();

        $this->actingAs($user)
            ->get(route('productivity'))
            ->assertOk()
            ->assertSeeLivewire(Productivity::class);
    }

    public function test_can_create_task(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->set('taskTitle', 'Write unit tests')
            ->set('taskPriority', 'high')
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => 'Write unit tests', 'priority' => 'high']);
    }

    public function test_task_requires_title(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->set('taskTitle', '')
            ->call('saveTask')
            ->assertHasErrors(['taskTitle']);
    }

    public function test_can_complete_and_uncomplete_task(): void
    {
        $user = $this->userWithPersona();
        $task = Task::create(['user_id' => $user->id, 'title' => 'My task', 'priority' => 'medium']);

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->call('completeTask', $task->id);

        $this->assertNotNull($task->fresh()->completed_at);

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->call('uncompleteTask', $task->id);

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_can_delete_task(): void
    {
        $user = $this->userWithPersona();
        $task = Task::create(['user_id' => $user->id, 'title' => 'Delete me', 'priority' => 'low']);

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->call('deleteTask', $task->id);

        $this->assertModelMissing($task);
    }

    public function test_can_create_reminder(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->set('reminderTitle', 'Team standup')
            ->set('reminderAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('reminderChannel', 'email')
            ->call('saveReminder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reminders', ['user_id' => $user->id, 'title' => 'Team standup']);
    }

    public function test_reminder_requires_title_and_future_date(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->set('reminderTitle', '')
            ->set('reminderAt', now()->subHour()->format('Y-m-d\TH:i'))
            ->call('saveReminder')
            ->assertHasErrors(['reminderTitle', 'reminderAt']);
    }

    public function test_can_save_note(): void
    {
        Embeddings::fake();
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->set('tab', 'notes')
            ->set('noteTitle', 'Idea')
            ->set('noteContent', 'Use pgvector for semantic search')
            ->call('saveNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notes', ['user_id' => $user->id, 'title' => 'Idea']);
    }

    public function test_note_requires_content(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->set('noteContent', '')
            ->call('saveNote')
            ->assertHasErrors(['noteContent']);
    }

    public function test_cannot_delete_another_users_task(): void
    {
        $user = $this->userWithPersona();
        $other = User::factory()->create();
        $task = Task::create(['user_id' => $other->id, 'title' => 'Not yours', 'priority' => 'low']);

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->call('deleteTask', $task->id);

        $this->assertModelExists($task);
    }

    public function test_cannot_delete_another_users_reminder(): void
    {
        $user = $this->userWithPersona();
        $other = User::factory()->create();
        $reminder = Reminder::create([
            'user_id' => $other->id,
            'title' => 'Private reminder',
            'remind_at' => now()->addHour(),
            'channel' => 'email',
        ]);

        Livewire::actingAs($user)
            ->test(Productivity::class)
            ->call('deleteReminder', $reminder->id);

        $this->assertModelExists($reminder);
    }
}
