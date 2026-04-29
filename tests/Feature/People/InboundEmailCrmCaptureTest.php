<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Ai\Agents\Inbound\ForwardedEmailClassifierAgent;
use App\Models\Interaction;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class InboundEmailCrmCaptureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('inbound.domain', 'inbound.test');
        config()->set('inbound.hmac_secret', 'test-secret');
        config()->set('people.summarize.enabled', false); // keep tests offline
        Mail::fake();
        Queue::fake();

        ForwardedEmailClassifierAgent::fake([
            ['kind' => 'note', 'title' => 'x', 'summary' => 'x', 'priority' => 'medium'],
        ]);
    }

    private function postSigned(array $payload): TestResponse
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'test-secret');

        return $this->call('POST', route('webhooks.email.inbound'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_INBOUND_SIGNATURE' => $signature,
        ], $body);
    }

    public function test_inbound_email_creates_person_and_interaction(): void
    {
        $user = User::factory()->withDefaultContext()->create([
            'inbound_email_token' => str_repeat('a', 32),
        ]);

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}@inbound.test",
            'from' => '"Sara Bryant" <sara@example.com>',
            'subject' => 'Following up',
            'text' => 'Hey, just checking in on the proposal.',
            'message_id' => '<msg-1@mail.example.com>',
            'spf' => 'pass',
            'dkim' => 'pass',
        ])->assertAccepted();

        $this->assertSame(1, Person::where('user_id', $user->id)->count());
        $person = Person::first();
        $this->assertSame('Sara Bryant', $person->display_name);
        $this->assertSame('sara@example.com', $person->primaryEmail());

        $this->assertSame(1, Interaction::count());
        $interaction = Interaction::first();
        $this->assertSame($person->id, $interaction->person_id);
        $this->assertSame('email', $interaction->channel);
        $this->assertSame('inbound', $interaction->direction);
        $this->assertSame('<msg-1@mail.example.com>', $interaction->external_id);
    }

    public function test_inbound_email_dedups_via_message_id(): void
    {
        $user = User::factory()->withDefaultContext()->create([
            'inbound_email_token' => str_repeat('b', 32),
        ]);
        $payload = [
            'to' => "forward-{$user->inbound_email_token}@inbound.test",
            'from' => 'sara@example.com',
            'subject' => 'a',
            'text' => 'b',
            'message_id' => '<dup@mail.example.com>',
            'spf' => 'pass',
            'dkim' => 'pass',
        ];

        $this->postSigned($payload)->assertAccepted();
        $this->postSigned($payload)->assertAccepted();

        $this->assertSame(1, Interaction::count());
    }

    public function test_inbound_email_skips_transactional_senders(): void
    {
        $user = User::factory()->withDefaultContext()->create([
            'inbound_email_token' => str_repeat('c', 32),
        ]);

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}@inbound.test",
            'from' => 'noreply@github.com',
            'subject' => 'PR comment',
            'text' => 'X commented...',
            'spf' => 'pass',
            'dkim' => 'pass',
        ])->assertAccepted();

        $this->assertSame(0, Person::count());
        $this->assertSame(0, Interaction::count());
    }

    public function test_inbound_email_falls_back_to_default_context(): void
    {
        $user = User::factory()->withDefaultContext()->create([
            'inbound_email_token' => str_repeat('d', 32),
        ]);

        $this->postSigned([
            'to' => "forward-{$user->inbound_email_token}@inbound.test",
            'from' => 'sara@example.com',
            'subject' => 'a',
            'text' => 'b',
            'spf' => 'pass',
            'dkim' => 'pass',
        ])->assertAccepted();

        $person = Person::first();
        $this->assertNotNull($person->context_id);
    }
}
