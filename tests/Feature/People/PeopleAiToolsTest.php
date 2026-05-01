<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Ai\Tools\CreatePerson;
use App\Ai\Tools\FindPerson;
use App\Ai\Tools\FindStaleContacts;
use App\Ai\Tools\ListPeople;
use App\Ai\Tools\LogInteraction;
use App\Ai\Tools\RecentInteractions;
use App\Ai\Tools\UpdatePerson;
use App\Models\Context;
use App\Models\Interaction;
use App\Models\Person;
use App\Models\PersonEmail;
use App\Models\User;
use App\Modules\People\Services\ContactStaleness;
use App\Modules\People\Services\InteractionRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class PeopleAiToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('people.summarize.enabled', false);
        Queue::fake();

        $this->user = User::factory()->create();
        Context::factory()->for($this->user)->create(['is_default' => true]);
    }

    public function test_find_person_matches_name_email_phone(): void
    {
        $sara = Person::factory()->for($this->user)->create(['display_name' => 'Sara Bryant']);
        PersonEmail::create(['user_id' => $this->user->id, 'person_id' => $sara->id, 'email' => 'sara@example.com']);

        $tool = new FindPerson($this->user);

        $byName = $tool->handle(new Request(['query' => 'Sara']));
        $this->assertStringContainsString('Sara Bryant', (string) $byName);

        $byEmail = $tool->handle(new Request(['query' => 'sara@example']));
        $this->assertStringContainsString('Sara Bryant', (string) $byEmail);

        $miss = $tool->handle(new Request(['query' => 'XYZ-NOPE']));
        $this->assertStringContainsString('No people', (string) $miss);
    }

    public function test_create_person_persists_with_emails_and_phones(): void
    {
        $tool = new CreatePerson($this->user);
        $result = $tool->handle(new Request([
            'display_name' => 'Tom Riddle',
            'email' => 'tom@hogwarts.edu',
            'phone' => '+15551234567',
            'tags' => ['student'],
            'notes' => null,
            'company' => null,
            'title' => null,
        ]));

        $this->assertStringContainsString('Created person', (string) $result);
        $person = Person::where('display_name', 'Tom Riddle')->first();
        $this->assertNotNull($person);
        $this->assertSame('tom@hogwarts.edu', $person->primaryEmail());
        $this->assertSame('+15551234567', $person->primaryPhone());
        $this->assertSame(['student'], $person->tags);
    }

    public function test_create_person_per_turn_guardrail(): void
    {
        $tool = new CreatePerson($this->user);

        for ($i = 1; $i <= 3; $i++) {
            $tool->handle(new Request([
                'display_name' => "Person {$i}",
                'email' => null, 'phone' => null, 'tags' => null, 'notes' => null,
                'company' => null, 'title' => null,
            ]));
        }

        $blocked = $tool->handle(new Request([
            'display_name' => 'Fourth',
            'email' => null, 'phone' => null, 'tags' => null, 'notes' => null,
            'company' => null, 'title' => null,
        ]));

        $this->assertStringContainsString('too many records', (string) $blocked);
        $this->assertSame(3, Person::count());
    }

    public function test_update_person_changes_fields(): void
    {
        $person = Person::factory()->for($this->user)->create(['company' => 'Old']);
        $tool = new UpdatePerson($this->user);

        $tool->handle(new Request([
            'person_id' => $person->id,
            'company' => 'New',
            'display_name' => null, 'title' => null, 'notes' => null, 'tags' => null, 'birthday' => null,
        ]));

        $this->assertSame('New', $person->fresh()->company);
    }

    public function test_update_person_rejects_other_users_person(): void
    {
        $other = User::factory()->create();
        $foreign = Person::factory()->for($other)->create();
        $tool = new UpdatePerson($this->user);

        $result = $tool->handle(new Request([
            'person_id' => $foreign->id,
            'company' => 'pwned',
            'display_name' => null, 'title' => null, 'notes' => null, 'tags' => null, 'birthday' => null,
        ]));

        $this->assertStringContainsString('No person', (string) $result);
        $this->assertNotSame('pwned', $foreign->fresh()->company);
    }

    public function test_list_people_filters_by_tag(): void
    {
        Person::factory()->for($this->user)->create(['display_name' => 'Investor', 'tags' => ['vc']]);
        Person::factory()->for($this->user)->create(['display_name' => 'Friend', 'tags' => ['friend']]);

        $tool = new ListPeople($this->user);
        $result = (string) $tool->handle(new Request(['tag' => 'vc', 'recent_only' => null]));

        $this->assertStringContainsString('Investor', $result);
        $this->assertStringNotContainsString('Friend', $result);
    }

    public function test_log_interaction_creates_record_and_updates_last_contact(): void
    {
        $person = Person::factory()->for($this->user)->create(['last_contact_at' => null]);
        $tool = new LogInteraction($this->user, new InteractionRecorder);

        $tool->handle(new Request([
            'person_id' => $person->id,
            'channel' => 'meeting',
            'subject' => 'Coffee',
            'summary' => 'Caught up about the launch',
            'direction' => null, 'occurred_at' => null,
        ]));

        $this->assertSame(1, Interaction::count());
        $this->assertNotNull($person->fresh()->last_contact_at);
    }

    public function test_recent_interactions_for_specific_person(): void
    {
        $person = Person::factory()->for($this->user)->create();
        Interaction::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'person_id' => $person->id,
            'summary' => 'A test interaction',
        ]);

        $tool = new RecentInteractions($this->user);
        $result = (string) $tool->handle(new Request(['person_id' => $person->id, 'limit' => null]));

        $this->assertStringContainsString('A test interaction', $result);
    }

    public function test_find_stale_contacts_returns_old_and_never(): void
    {
        Person::factory()->for($this->user)->create(['display_name' => 'Never', 'last_contact_at' => null]);
        Person::factory()->for($this->user)->create(['display_name' => 'Old', 'last_contact_at' => now()->subDays(120)]);
        Person::factory()->for($this->user)->create(['display_name' => 'Recent', 'last_contact_at' => now()->subDays(5)]);

        $tool = new FindStaleContacts($this->user, new ContactStaleness);
        $result = (string) $tool->handle(new Request(['days' => 90, 'limit' => null]));

        $this->assertStringContainsString('Never', $result);
        $this->assertStringContainsString('Old', $result);
        $this->assertStringNotContainsString('Recent', $result);
    }
}
