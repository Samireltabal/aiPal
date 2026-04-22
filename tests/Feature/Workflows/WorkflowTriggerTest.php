<?php

declare(strict_types=1);

namespace Tests\Feature\Workflows;

use App\Jobs\RunWorkflowJob;
use App\Models\User;
use App\Models\Workflow;
use App\Services\Workflow\WorkflowMessageMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkflowTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_endpoint_dispatches_job_for_valid_token(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $workflow = Workflow::create([
            'user_id' => $user->id,
            'name' => 'Test webhook',
            'enabled' => true,
            'prompt' => 'Echo the payload',
            'enabled_tool_names' => [],
            'delivery_channel' => 'notification',
            'trigger_type' => 'webhook',
            'webhook_token' => (string) Str::uuid().'abc',
        ]);

        $response = $this->postJson("/webhooks/workflow/{$workflow->webhook_token}", [
            'issue' => ['key' => 'APP-123', 'summary' => 'Test'],
        ]);

        $response->assertAccepted();
        $response->assertJson(['accepted' => true]);

        Queue::assertPushed(RunWorkflowJob::class, function (RunWorkflowJob $job) use ($workflow): bool {
            return $job->workflowId === $workflow->id
                && $job->triggeredBy === 'webhook'
                && isset($job->triggerPayload['body']['issue']);
        });
    }

    public function test_webhook_endpoint_returns_404_for_invalid_token(): void
    {
        Queue::fake();

        $response = $this->postJson('/webhooks/workflow/nonexistent-token-here', []);

        $response->assertNotFound();
        Queue::assertNothingPushed();
    }

    public function test_webhook_endpoint_returns_404_for_disabled_workflow(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $workflow = Workflow::create([
            'user_id' => $user->id,
            'name' => 'Disabled',
            'enabled' => false,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'none',
            'trigger_type' => 'webhook',
            'webhook_token' => 'disabled-token-12345',
        ]);

        $response = $this->postJson("/webhooks/workflow/{$workflow->webhook_token}");

        $response->assertNotFound();
        Queue::assertNothingPushed();
    }

    public function test_webhook_strips_sensitive_headers_from_payload(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $workflow = Workflow::create([
            'user_id' => $user->id,
            'name' => 'Header test',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'none',
            'trigger_type' => 'webhook',
            'webhook_token' => 'header-test-token-xyz',
        ]);

        $this->postJson(
            "/webhooks/workflow/{$workflow->webhook_token}",
            ['x' => 1],
            ['Authorization' => 'Bearer secret', 'X-Custom' => 'ok']
        );

        Queue::assertPushed(RunWorkflowJob::class, function (RunWorkflowJob $job): bool {
            $headers = $job->triggerPayload['headers'] ?? [];

            return ! array_key_exists('authorization', array_change_key_case($headers))
                && array_key_exists('x-custom', array_change_key_case($headers));
        });
    }

    public function test_message_matcher_finds_prefix_match(): void
    {
        $user = User::factory()->create();
        Workflow::create([
            'user_id' => $user->id,
            'name' => 'Morning',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'whatsapp',
            'trigger_type' => 'message',
            'message_channel' => 'whatsapp',
            'message_trigger_pattern' => '/morning',
        ]);

        $match = (new WorkflowMessageMatcher)->match($user, 'whatsapp', '/morning brief please');

        $this->assertNotNull($match);
        $this->assertSame('Morning', $match->name);
    }

    public function test_message_matcher_finds_regex_match(): void
    {
        $user = User::factory()->create();
        Workflow::create([
            'user_id' => $user->id,
            'name' => 'Status regex',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'telegram',
            'trigger_type' => 'message',
            'message_channel' => 'telegram',
            'message_trigger_pattern' => '/^status/i',
        ]);

        $match = (new WorkflowMessageMatcher)->match($user, 'telegram', 'Status report please');

        $this->assertNotNull($match);
    }

    public function test_message_matcher_respects_channel_filter(): void
    {
        $user = User::factory()->create();
        Workflow::create([
            'user_id' => $user->id,
            'name' => 'WhatsApp only',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'whatsapp',
            'trigger_type' => 'message',
            'message_channel' => 'whatsapp',
            'message_trigger_pattern' => '/x',
        ]);

        $match = (new WorkflowMessageMatcher)->match($user, 'telegram', '/x hello');
        $this->assertNull($match);

        $match = (new WorkflowMessageMatcher)->match($user, 'whatsapp', '/x hello');
        $this->assertNotNull($match);
    }

    public function test_message_matcher_any_channel_matches_all(): void
    {
        $user = User::factory()->create();
        Workflow::create([
            'user_id' => $user->id,
            'name' => 'Any',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'notification',
            'trigger_type' => 'message',
            'message_channel' => 'any',
            'message_trigger_pattern' => '/any',
        ]);

        foreach (['whatsapp', 'telegram', 'chat'] as $channel) {
            $match = (new WorkflowMessageMatcher)->match($user, $channel, '/any thing');
            $this->assertNotNull($match, "Failed for channel: {$channel}");
        }
    }

    public function test_message_matcher_skips_disabled_workflows(): void
    {
        $user = User::factory()->create();
        Workflow::create([
            'user_id' => $user->id,
            'name' => 'Off',
            'enabled' => false,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'none',
            'trigger_type' => 'message',
            'message_channel' => 'any',
            'message_trigger_pattern' => '/off',
        ]);

        $match = (new WorkflowMessageMatcher)->match($user, 'whatsapp', '/off');
        $this->assertNull($match);
    }

    public function test_message_matcher_is_user_scoped(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Workflow::create([
            'user_id' => $userA->id,
            'name' => 'A only',
            'enabled' => true,
            'prompt' => 'x',
            'enabled_tool_names' => [],
            'delivery_channel' => 'none',
            'trigger_type' => 'message',
            'message_channel' => 'any',
            'message_trigger_pattern' => '/secret',
        ]);

        $match = (new WorkflowMessageMatcher)->match($userB, 'whatsapp', '/secret');
        $this->assertNull($match);
    }
}
