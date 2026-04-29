<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Ai\Tools\GmailTool;
use App\Models\Connection;
use App\Models\Interaction;
use App\Models\Person;
use App\Models\User;
use App\Modules\People\Services\InteractionRecorder;
use App\Modules\People\Services\PersonResolver;
use App\Services\GmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Tools\Request;
use Mockery;
use Tests\TestCase;

class GmailToolCrmCaptureTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function fakeGmailService(): GmailService
    {
        $mock = Mockery::mock(GmailService::class);
        $mock->shouldReceive('getMessage')
            ->andReturn([
                'id' => 'orig-msg-1',
                'from' => 'sara@example.com',
                'subject' => 'Project status',
                'body' => '...',
            ]);
        $mock->shouldReceive('createDraft')->andReturn('draft-abc');

        return $mock;
    }

    public function test_drafting_a_reply_records_outbound_interaction(): void
    {
        Queue::fake();
        config()->set('people.summarize.enabled', false);

        $user = User::factory()->withDefaultContext()->create();
        Connection::create([
            'user_id' => $user->id,
            'context_id' => $user->defaultContext()?->id,
            'provider' => Connection::PROVIDER_GOOGLE,
            'identifier' => 'google-test',
            'enabled' => true,
            'is_default' => true,
            'credentials' => ['access_token' => 'x', 'expires_at' => now()->addHour()->toIso8601String()],
            'capabilities' => [],
        ]);
        $tool = new GmailTool($user, $this->fakeGmailService(), new PersonResolver, new InteractionRecorder);

        $request = new Request([
            'action' => 'draft',
            'message_id' => 'orig-msg-1',
            'reply_body' => 'Thanks Sara, will do.',
        ]);

        $tool->handle($request);

        $person = Person::first();
        $this->assertNotNull($person);
        $this->assertSame('sara@example.com', $person->primaryEmail());

        $interaction = Interaction::first();
        $this->assertNotNull($interaction);
        $this->assertSame('outbound', $interaction->direction);
        $this->assertSame('email', $interaction->channel);
        $this->assertSame('gmail-draft:draft-abc', $interaction->external_id);
        $this->assertTrue($interaction->metadata['draft'] ?? false);
    }

    public function test_drafting_to_transactional_recipient_does_not_create_person(): void
    {
        Queue::fake();
        config()->set('people.summarize.enabled', false);

        $user = User::factory()->withDefaultContext()->create();
        Connection::create([
            'user_id' => $user->id,
            'context_id' => $user->defaultContext()?->id,
            'provider' => Connection::PROVIDER_GOOGLE,
            'identifier' => 'google-test',
            'enabled' => true,
            'is_default' => true,
            'credentials' => ['access_token' => 'x', 'expires_at' => now()->addHour()->toIso8601String()],
            'capabilities' => [],
        ]);
        $mock = Mockery::mock(GmailService::class);
        $mock->shouldReceive('getMessage')
            ->andReturn([
                'id' => 'orig-msg-2',
                'from' => 'noreply@github.com',
                'subject' => 'New PR',
                'body' => '...',
            ]);
        $mock->shouldReceive('createDraft')->andReturn('draft-x');

        $tool = new GmailTool($user, $mock, new PersonResolver, new InteractionRecorder);
        $tool->handle(new Request([
            'action' => 'draft',
            'message_id' => 'orig-msg-2',
            'reply_body' => 'I will look into it',
        ]));

        $this->assertSame(0, Person::count());
        $this->assertSame(0, Interaction::count());
    }
}
