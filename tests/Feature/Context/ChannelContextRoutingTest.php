<?php

declare(strict_types=1);

namespace Tests\Feature\Context;

use App\Models\Context;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelContextRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_context_prefers_active_override_over_default(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $personal = $user->defaultContext();

        $work = Context::factory()->work('Acme')->create(['user_id' => $user->id]);

        $this->assertSame($personal->id, $user->currentContext()->id);

        $user->setActiveContext($work);

        $this->assertSame($work->id, $user->currentContext()->id);
    }

    public function test_create_task_tool_uses_active_context_when_set(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Phoenix')->create(['user_id' => $user->id]);

        $user->setActiveContext($work);

        Task::create([
            'user_id' => $user->id,
            'context_id' => $user->currentContext()?->id,
            'title' => 'ship it',
            'priority' => 'medium',
        ]);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'context_id' => $work->id,
            'title' => 'ship it',
        ]);
    }

    public function test_reminder_uses_default_context_when_no_override(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $personal = $user->defaultContext();

        Reminder::create([
            'user_id' => $user->id,
            'context_id' => $user->currentContext()?->id,
            'title' => 'standup',
            'remind_at' => now()->addHour(),
            'channel' => 'email',
        ]);

        $this->assertDatabaseHas('reminders', [
            'user_id' => $user->id,
            'context_id' => $personal->id,
            'title' => 'standup',
        ]);
    }
}
