<?php

declare(strict_types=1);

namespace Tests\Feature\Workflows;

use App\Jobs\RunWorkflowJob;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WorkflowScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_schedule_workflow_when_cron_matches(): void
    {
        Queue::fake();

        // Lock "now" to Monday 08:00 UTC so "0 8 * * 1-5" matches.
        Carbon::setTestNow(Carbon::create(2026, 4, 27, 8, 0, 0, 'UTC'));

        $user = User::factory()->create();
        $workflow = Workflow::create([
            'user_id' => $user->id,
            'name' => 'Morning brief',
            'enabled' => true,
            'prompt' => 'Brief me',
            'enabled_tool_names' => [],
            'delivery_channel' => 'email',
            'trigger_type' => 'schedule',
            'cron_expression' => '0 8 * * 1-5',
        ]);

        $this->artisan('workflows:dispatch-due')->assertExitCode(0);

        Queue::assertPushed(RunWorkflowJob::class, fn (RunWorkflowJob $job) => $job->workflowId === $workflow->id);

        Carbon::setTestNow();
    }

    public function test_does_not_dispatch_when_cron_does_not_match(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::create(2026, 4, 27, 14, 30, 0, 'UTC'));

        $user = User::factory()->create();
        Workflow::create([
            'user_id' => $user->id,
            'name' => 'Eight am',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'email',
            'trigger_type' => 'schedule',
            'cron_expression' => '0 8 * * *',
        ]);

        $this->artisan('workflows:dispatch-due')->assertExitCode(0);

        Queue::assertNothingPushed();
        Carbon::setTestNow();
    }

    public function test_skips_disabled_workflows(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::create(2026, 4, 27, 8, 0, 0, 'UTC'));

        $user = User::factory()->create();
        Workflow::create([
            'user_id' => $user->id,
            'name' => 'Disabled',
            'enabled' => false,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'email',
            'trigger_type' => 'schedule',
            'cron_expression' => '* * * * *',
        ]);

        $this->artisan('workflows:dispatch-due')->assertExitCode(0);

        Queue::assertNothingPushed();
        Carbon::setTestNow();
    }

    public function test_skips_non_schedule_triggers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Workflow::create([
            'user_id' => $user->id,
            'name' => 'Webhook',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'email',
            'trigger_type' => 'webhook',
            'webhook_token' => 'abc123xyz',
        ]);

        $this->artisan('workflows:dispatch-due')->assertExitCode(0);

        Queue::assertNothingPushed();
    }
}
