<?php

declare(strict_types=1);

namespace Tests\Feature\Productivity;

use App\Ai\Tools\CreateTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class CreateRecordsGuardrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_task_blocks_after_three_records_in_one_turn(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $tool = new CreateTask($user);

        $args = ['title' => 'x', 'description' => null, 'priority' => null, 'due_date' => null];

        // First three creations succeed.
        $this->assertStringContainsString('Task created', (string) $tool->handle(new Request($args)));
        $this->assertStringContainsString('Task created', (string) $tool->handle(new Request($args)));
        $this->assertStringContainsString('Task created', (string) $tool->handle(new Request($args)));

        // Fourth call hits the guardrail.
        $result = (string) $tool->handle(new Request($args));
        $this->assertStringContainsString('GUARDRAIL', $result);
        $this->assertSame(3, $user->tasks()->count());
    }
}
