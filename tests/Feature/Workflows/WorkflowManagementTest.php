<?php

declare(strict_types=1);

namespace Tests\Feature\Workflows;

use App\Jobs\RunWorkflowJob;
use App\Livewire\Workflows;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class WorkflowManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_schedule_workflow(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Workflows::class)
            ->call('openCreate')
            ->set('name', 'Morning brief')
            ->set('prompt', 'Give me my morning brief')
            ->set('triggerType', 'schedule')
            ->set('cronExpression', '0 8 * * 1-5')
            ->set('selectedTools', ['google_calendar'])
            ->set('deliveryChannel', 'email')
            ->call('save')
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('workflows', [
            'user_id' => $user->id,
            'name' => 'Morning brief',
            'trigger_type' => 'schedule',
            'cron_expression' => '0 8 * * 1-5',
        ]);
    }

    public function test_invalid_cron_expression_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Workflows::class)
            ->call('openCreate')
            ->set('name', 'Bad cron')
            ->set('prompt', 'x')
            ->set('triggerType', 'schedule')
            ->set('cronExpression', 'not a cron')
            ->call('save')
            ->assertSet('showForm', true);

        $this->assertDatabaseMissing('workflows', ['name' => 'Bad cron']);
    }

    public function test_webhook_workflow_generates_token_on_save(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Workflows::class)
            ->call('openCreate')
            ->set('name', 'Webhook flow')
            ->set('prompt', 'x')
            ->set('triggerType', 'webhook')
            ->call('save');

        $wf = Workflow::where('user_id', $user->id)->where('name', 'Webhook flow')->first();

        $this->assertNotNull($wf);
        $this->assertNotNull($wf->webhook_token);
        $this->assertGreaterThanOrEqual(36, strlen($wf->webhook_token));
    }

    public function test_message_workflow_requires_pattern(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Workflows::class)
            ->call('openCreate')
            ->set('name', 'No pattern')
            ->set('prompt', 'x')
            ->set('triggerType', 'message')
            ->set('messagePattern', '')
            ->call('save')
            ->assertSet('showForm', true);

        $this->assertDatabaseMissing('workflows', ['name' => 'No pattern']);
    }

    public function test_run_now_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $workflow = Workflow::create([
            'user_id' => $user->id,
            'name' => 'Manual',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'notification',
            'trigger_type' => 'manual',
        ]);

        Livewire::actingAs($user)
            ->test(Workflows::class)
            ->call('runNow', $workflow->id);

        Queue::assertPushed(RunWorkflowJob::class, function (RunWorkflowJob $job) use ($workflow): bool {
            return $job->workflowId === $workflow->id && $job->triggeredBy === 'manual';
        });
    }

    public function test_toggle_enabled_flips_the_flag(): void
    {
        $user = User::factory()->create();
        $workflow = Workflow::create([
            'user_id' => $user->id,
            'name' => 'Toggle',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'notification',
            'trigger_type' => 'manual',
        ]);

        Livewire::actingAs($user)
            ->test(Workflows::class)
            ->call('toggleEnabled', $workflow->id);

        $this->assertFalse($workflow->fresh()->enabled);
    }

    public function test_user_cannot_run_other_users_workflow(): void
    {
        Queue::fake();

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $workflow = Workflow::create([
            'user_id' => $userA->id,
            'name' => 'Private',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'notification',
            'trigger_type' => 'manual',
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($userB)
            ->test(Workflows::class)
            ->call('runNow', $workflow->id);

        Queue::assertNothingPushed();
    }

    public function test_user_cannot_delete_other_users_workflow(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $workflow = Workflow::create([
            'user_id' => $userA->id,
            'name' => 'Private',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'notification',
            'trigger_type' => 'manual',
        ]);

        try {
            Livewire::actingAs($userB)
                ->test(Workflows::class)
                ->call('delete', $workflow->id);
            $this->fail('Expected ModelNotFoundException');
        } catch (ModelNotFoundException) {
            // expected
        }

        $this->assertDatabaseHas('workflows', ['id' => $workflow->id]);
    }
}
