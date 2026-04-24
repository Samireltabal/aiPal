<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound;

use App\Ai\Agents\Inbound\ForwardedEmailClassifierAgent;
use App\Models\Memory;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class InboundEmailControllerTest extends TestCase
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
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_INBOUND_SIGNATURE' => $signature,
            ],
            $body,
        );
    }

    public function test_rejects_request_without_valid_hmac(): void
    {
        $this->call(
            'POST',
            route('webhooks.email.inbound'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_INBOUND_SIGNATURE' => 'bogus'],
            json_encode(['to' => 'x@x', 'text' => 'hi']),
        )->assertUnauthorized();
    }

    public function test_rejects_spf_fail(): void
    {
        $user = User::factory()->withDefaultContext()->create(['inbound_email_token' => str_repeat('a', 32)]);

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}@inbound.samirai.xyz",
            'from' => 'spammer@bad.example',
            'subject' => 'Buy this',
            'text' => 'hi',
            'spf' => 'fail',
            'dkim' => 'pass',
        ])->assertStatus(422);
    }

    public function test_rejects_unknown_recipient_token(): void
    {
        $this->postSigned([
            'to' => 'forward-'.str_repeat('z', 32).'@inbound.samirai.xyz',
            'from' => 'a@b',
            'subject' => 'x',
            'text' => 'hi',
            'spf' => 'pass',
            'dkim' => 'pass',
        ])->assertNotFound();
    }

    public function test_task_classification_creates_task(): void
    {
        $user = User::factory()->withDefaultContext()->create(['inbound_email_token' => str_repeat('b', 32)]);

        ForwardedEmailClassifierAgent::fake([
            [
                'kind' => 'task',
                'title' => 'Review draft proposal',
                'summary' => 'Read the attached draft and send feedback.',
                'priority' => 'high',
            ],
        ]);

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}@inbound.samirai.xyz",
            'from' => 'boss@example.com',
            'subject' => 'Please review draft',
            'text' => 'Hey — can you take a look at this draft and send feedback?',
            'spf' => 'pass',
            'dkim' => 'pass',
        ])->assertStatus(202);

        $this->assertSame(1, Task::where('user_id', $user->id)->count());
        $this->assertSame('Review draft proposal', Task::where('user_id', $user->id)->first()->title);
    }

    public function test_memory_classification_creates_memory(): void
    {
        $user = User::factory()->withDefaultContext()->create(['inbound_email_token' => str_repeat('c', 32)]);

        ForwardedEmailClassifierAgent::fake([
            [
                'kind' => 'memory',
                'title' => 'Prefers decaf after 3pm',
                'summary' => 'User prefers decaf coffee after 3pm to avoid insomnia.',
                'priority' => 'low',
            ],
        ]);

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}@inbound.samirai.xyz",
            'from' => 'self@example.com',
            'subject' => 'Remember this',
            'text' => 'I should only drink decaf after 3pm.',
            'spf' => 'pass',
            'dkim' => 'pass',
        ])->assertStatus(202);

        $this->assertSame(1, Memory::where('user_id', $user->id)->count());
    }

    public function test_note_classification_creates_note(): void
    {
        $user = User::factory()->withDefaultContext()->create(['inbound_email_token' => str_repeat('d', 32)]);

        ForwardedEmailClassifierAgent::fake([
            [
                'kind' => 'note',
                'title' => 'Article on latency budgets',
                'summary' => 'Good reference on latency budget allocation across microservices.',
                'priority' => 'low',
            ],
        ]);

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}@inbound.samirai.xyz",
            'from' => 'self@example.com',
            'subject' => 'Worth reading',
            'text' => 'Full article content here.',
            'spf' => 'pass',
            'dkim' => 'pass',
        ])->assertStatus(202);

        $this->assertSame(1, Note::where('user_id', $user->id)->count());
    }
}
