<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Jobs\SummarizeInteractionJob;
use App\Models\Context;
use App\Models\Interaction;
use App\Models\Person;
use App\Models\User;
use App\Modules\People\Services\InteractionRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InteractionRecorderTest extends TestCase
{
    use RefreshDatabase;

    private function recorder(): InteractionRecorder
    {
        return new InteractionRecorder;
    }

    public function test_records_basic_interaction(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        $interaction = $this->recorder()->record($person, [
            'channel' => 'email',
            'direction' => 'inbound',
            'subject' => 'hello',
            'raw_excerpt' => 'Hi there, how are you?',
        ]);

        $this->assertNotNull($interaction);
        $this->assertSame($user->id, $interaction->user_id);
        $this->assertSame($person->id, $interaction->person_id);
        $this->assertSame('email', $interaction->channel);
        $this->assertSame('inbound', $interaction->direction);
    }

    public function test_updates_person_last_contact_at(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create(['last_contact_at' => null]);

        $occurred = Carbon::parse('2026-04-29 10:00:00');
        $this->recorder()->record($person, [
            'channel' => 'email',
            'occurred_at' => $occurred,
        ]);

        $this->assertTrue($occurred->equalTo($person->fresh()->last_contact_at));
    }

    public function test_back_dated_interaction_does_not_move_last_contact_backwards(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $recent = Carbon::parse('2026-04-29 10:00:00');
        $person = Person::factory()->for($user)->create(['last_contact_at' => $recent]);

        // Manual back-dated note from a week ago.
        $this->recorder()->record($person, [
            'channel' => 'note',
            'occurred_at' => Carbon::parse('2026-04-22 09:00:00'),
            'summary' => 'Old note',
        ]);

        $this->assertTrue($recent->equalTo($person->fresh()->last_contact_at));
    }

    public function test_dedup_via_external_id_returns_existing(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        $first = $this->recorder()->record($person, [
            'channel' => 'email',
            'external_id' => 'msg-abc-123',
            'subject' => 'hello',
        ]);

        $second = $this->recorder()->record($person, [
            'channel' => 'email',
            'external_id' => 'msg-abc-123',
            'subject' => 'different',
        ]);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Interaction::count());
    }

    public function test_dispatches_summarize_job_when_no_summary_provided(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        $interaction = $this->recorder()->record($person, [
            'channel' => 'email',
            'raw_excerpt' => 'Long body that needs summarizing',
        ]);

        Queue::assertPushed(SummarizeInteractionJob::class, fn ($job) => $job->interactionId === $interaction->id);
    }

    public function test_does_not_dispatch_summarize_when_summary_already_present(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        $this->recorder()->record($person, [
            'channel' => 'email',
            'summary' => 'pre-supplied summary',
            'raw_excerpt' => 'body',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_does_not_dispatch_when_summarize_disabled(): void
    {
        Queue::fake();
        config()->set('people.summarize.enabled', false);
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        $this->recorder()->record($person, [
            'channel' => 'email',
            'raw_excerpt' => 'body',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_inherits_context_from_person(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $context = Context::factory()->for($user)->create();
        $person = Person::factory()->for($user)->create(['context_id' => $context->id]);

        $interaction = $this->recorder()->record($person, ['channel' => 'email']);

        $this->assertSame($context->id, $interaction->context_id);
    }

    public function test_truncates_raw_excerpt(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        $interaction = $this->recorder()->record($person, [
            'channel' => 'email',
            'raw_excerpt' => str_repeat('a', 5000),
        ]);

        $this->assertSame(2000, mb_strlen((string) $interaction->raw_excerpt));
    }
}
