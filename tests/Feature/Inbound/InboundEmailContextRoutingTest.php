<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound;

use App\Ai\Agents\Inbound\ForwardedEmailClassifierAgent;
use App\Models\Context;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class InboundEmailContextRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('inbound.domain', 'inbound.samirai.xyz');
        config()->set('inbound.hmac_secret', 'test-secret');
        Mail::fake();
    }

    private function postSigned(array $payload): TestResponse
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'test-secret');

        return $this->call(
            'POST',
            route('webhooks.email.inbound'),
            [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_INBOUND_SIGNATURE' => $signature],
            $body,
        );
    }

    private function stubClassifier(): void
    {
        ForwardedEmailClassifierAgent::fake([[
            'kind' => 'task',
            'title' => 'Review draft',
            'summary' => 'Read and comment.',
            'priority' => 'medium',
        ]]);
    }

    public function test_slug_in_to_address_routes_to_matching_context(): void
    {
        $user = User::factory()->withDefaultContext()->create(['inbound_email_token' => str_repeat('a', 32)]);
        $work = Context::factory()->work('Acme')->create(['user_id' => $user->id]);
        $this->stubClassifier();

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}-{$work->slug}@inbound.samirai.xyz",
            'from' => 'boss@acme.com',
            'subject' => 'Review draft',
            'text' => 'Please review',
            'spf' => 'pass', 'dkim' => 'pass',
        ])->assertStatus(202)->assertJsonPath('context', $work->slug);

        $this->assertSame($work->id, Task::where('user_id', $user->id)->firstOrFail()->context_id);
    }

    public function test_no_slug_falls_back_to_default_context(): void
    {
        $user = User::factory()->withDefaultContext()->create(['inbound_email_token' => str_repeat('b', 32)]);
        $this->stubClassifier();

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}@inbound.samirai.xyz",
            'from' => 'friend@example.com',
            'subject' => 'FYI',
            'text' => 'content',
            'spf' => 'pass', 'dkim' => 'pass',
        ])->assertStatus(202)->assertJsonPath('context', 'personal');

        $this->assertSame($user->defaultContext()->id, Task::where('user_id', $user->id)->firstOrFail()->context_id);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $user = User::factory()->withDefaultContext()->create(['inbound_email_token' => str_repeat('c', 32)]);

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}-nonexistent@inbound.samirai.xyz",
            'from' => 'x@y.com',
            'subject' => 'x',
            'text' => 'x',
            'spf' => 'pass', 'dkim' => 'pass',
        ])->assertNotFound();
    }

    public function test_sender_domain_rule_routes_to_matching_context(): void
    {
        $user = User::factory()->withDefaultContext()->create(['inbound_email_token' => str_repeat('d', 32)]);
        $work = Context::factory()->work('Acme')->create([
            'user_id' => $user->id,
            'inference_rules' => [
                ['type' => 'sender_domain', 'value' => '@acme.com', 'priority' => 1],
            ],
        ]);
        $this->stubClassifier();

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}@inbound.samirai.xyz",
            'from' => 'boss@acme.com',
            'subject' => 'Standup',
            'text' => 'morning',
            'spf' => 'pass', 'dkim' => 'pass',
        ])->assertStatus(202)->assertJsonPath('context', $work->slug);

        $this->assertSame($work->id, Task::where('user_id', $user->id)->firstOrFail()->context_id);
    }

    public function test_lower_priority_rule_wins_over_higher_number(): void
    {
        $user = User::factory()->withDefaultContext()->create(['inbound_email_token' => str_repeat('e', 32)]);

        $ctxHigh = Context::factory()->work('HighPriority')->create([
            'user_id' => $user->id,
            'inference_rules' => [['type' => 'sender_domain', 'value' => 'example.com', 'priority' => 1]],
        ]);
        Context::factory()->freelance('LowPriority')->create([
            'user_id' => $user->id,
            'inference_rules' => [['type' => 'sender_domain', 'value' => 'example.com', 'priority' => 10]],
        ]);
        $this->stubClassifier();

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}@inbound.samirai.xyz",
            'from' => 'a@example.com',
            'subject' => 's',
            'text' => 't',
            'spf' => 'pass', 'dkim' => 'pass',
        ])->assertStatus(202)->assertJsonPath('context', $ctxHigh->slug);
    }

    public function test_slug_suffix_overrides_inference_rule(): void
    {
        $user = User::factory()->withDefaultContext()->create(['inbound_email_token' => str_repeat('f', 32)]);
        $work = Context::factory()->work('Acme')->create([
            'user_id' => $user->id,
            'inference_rules' => [['type' => 'sender_domain', 'value' => '@acme.com', 'priority' => 1]],
        ]);
        $phoenix = Context::factory()->freelance('Phoenix')->create(['user_id' => $user->id]);
        $this->stubClassifier();

        // Email from @acme.com but addressed to the phoenix slug — slug wins.
        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}-{$phoenix->slug}@inbound.samirai.xyz",
            'from' => 'boss@acme.com',
            'subject' => 's',
            'text' => 't',
            'spf' => 'pass', 'dkim' => 'pass',
        ])->assertStatus(202)->assertJsonPath('context', $phoenix->slug);

        $this->assertSame($phoenix->id, Task::where('user_id', $user->id)->firstOrFail()->context_id);
    }
}
