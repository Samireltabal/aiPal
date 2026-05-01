<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Livewire\People;
use App\Livewire\PersonDetail;
use App\Models\Interaction;
use App\Models\Person;
use App\Models\Persona;
use App\Models\PersonEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class PeopleLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('people.summarize.enabled', false);
        Queue::fake();
    }

    private function user(): User
    {
        $user = User::factory()->withDefaultContext()->create();

        Persona::create([
            'user_id' => $user->id,
            'assistant_name' => 'Pal',
            'tone' => 'friendly',
            'formality' => 'casual',
            'humor_level' => 'none',
            'system_prompt' => 'You are helpful.',
        ]);

        return $user;
    }

    public function test_people_page_loads(): void
    {
        $this->actingAs($this->user())
            ->get(route('people'))
            ->assertOk()
            ->assertSee('People');
    }

    public function test_people_list_renders_persons(): void
    {
        $user = $this->user();
        Person::factory()->for($user)->create(['display_name' => 'Sara Connor']);
        Person::factory()->for($user)->create(['display_name' => 'John Doe']);

        Livewire::actingAs($user)
            ->test(People::class)
            ->assertSee('Sara Connor')
            ->assertSee('John Doe');
    }

    public function test_search_filters_by_name(): void
    {
        $user = $this->user();
        Person::factory()->for($user)->create(['display_name' => 'Sara Connor']);
        Person::factory()->for($user)->create(['display_name' => 'John Doe']);

        Livewire::actingAs($user)
            ->test(People::class)
            ->set('search', 'sara')
            ->assertSee('Sara Connor')
            ->assertDontSee('John Doe');
    }

    public function test_stale_filter_only_shows_stale(): void
    {
        $user = $this->user();
        Person::factory()->for($user)->create([
            'display_name' => 'Recent Pal',
            'last_contact_at' => now()->subDays(5),
        ]);
        Person::factory()->for($user)->create([
            'display_name' => 'Stale Pal',
            'last_contact_at' => now()->subDays(120),
        ]);

        Livewire::actingAs($user)
            ->test(People::class)
            ->set('stale', true)
            ->assertSee('Stale Pal')
            ->assertDontSee('Recent Pal');
    }

    public function test_create_person_inline(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test(People::class)
            ->set('newName', 'Jane Smith')
            ->set('newEmail', 'jane@example.com')
            ->set('newCompany', 'Acme')
            ->call('createPerson');

        $this->assertDatabaseHas('people', ['user_id' => $user->id, 'display_name' => 'Jane Smith', 'company' => 'Acme']);
        $this->assertDatabaseHas('person_emails', ['user_id' => $user->id, 'email' => 'jane@example.com']);
    }

    public function test_other_users_people_are_isolated(): void
    {
        $userA = $this->user();
        $userB = $this->user();
        Person::factory()->for($userA)->create(['display_name' => 'Visible']);
        Person::factory()->for($userB)->create(['display_name' => 'Hidden']);

        Livewire::actingAs($userA)
            ->test(People::class)
            ->assertSee('Visible')
            ->assertDontSee('Hidden');
    }

    public function test_detail_page_loads(): void
    {
        $user = $this->user();
        $person = Person::factory()->for($user)->create(['display_name' => 'Sara Connor']);

        $this->actingAs($user)
            ->get(route('people.show', $person->id))
            ->assertOk()
            ->assertSee('Sara Connor');
    }

    public function test_detail_other_user_blocked(): void
    {
        $userA = $this->user();
        $userB = $this->user();
        $person = Person::factory()->for($userB)->create();

        $this->actingAs($userA)
            ->get(route('people.show', $person->id))
            ->assertNotFound();
    }

    public function test_save_profile_updates_fields(): void
    {
        $user = $this->user();
        $person = Person::factory()->for($user)->create(['display_name' => 'Old Name']);

        Livewire::actingAs($user)
            ->test(PersonDetail::class, ['id' => $person->id])
            ->set('displayName', 'New Name')
            ->set('company', 'Acme')
            ->set('tagsInput', 'friend, work')
            ->set('notes', 'Met at conference.')
            ->call('saveProfile');

        $person->refresh();
        $this->assertSame('New Name', $person->display_name);
        $this->assertSame('Acme', $person->company);
        $this->assertSame(['friend', 'work'], $person->tags);
        $this->assertSame('Met at conference.', $person->notes);
    }

    public function test_log_interaction_creates_record_and_updates_last_contact(): void
    {
        $user = $this->user();
        $person = Person::factory()->for($user)->create(['last_contact_at' => null]);

        Livewire::actingAs($user)
            ->test(PersonDetail::class, ['id' => $person->id])
            ->set('logChannel', 'meeting')
            ->set('logDirection', 'none')
            ->set('logOccurredAt', now()->format('Y-m-d\TH:i'))
            ->set('logSubject', 'Coffee chat')
            ->set('logSummary', 'Caught up on projects.')
            ->call('logInteraction');

        $this->assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'person_id' => $person->id,
            'channel' => 'meeting',
            'subject' => 'Coffee chat',
        ]);
        $this->assertNotNull($person->fresh()->last_contact_at);
    }

    public function test_add_email_creates_email_row(): void
    {
        $user = $this->user();
        $person = Person::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(PersonDetail::class, ['id' => $person->id])
            ->set('newEmail', 'a@b.test')
            ->call('addEmail');

        $this->assertDatabaseHas('person_emails', [
            'user_id' => $user->id,
            'person_id' => $person->id,
            'email' => 'a@b.test',
            'is_primary' => true,
        ]);
    }

    public function test_make_email_primary_swaps_flag(): void
    {
        $user = $this->user();
        $person = Person::factory()->for($user)->create();
        $first = PersonEmail::create(['user_id' => $user->id, 'person_id' => $person->id, 'email' => 'a@x.test', 'is_primary' => true]);
        $second = PersonEmail::create(['user_id' => $user->id, 'person_id' => $person->id, 'email' => 'b@x.test', 'is_primary' => false]);

        Livewire::actingAs($user)
            ->test(PersonDetail::class, ['id' => $person->id])
            ->call('makeEmailPrimary', $second->id);

        $this->assertFalse((bool) $first->fresh()->is_primary);
        $this->assertTrue((bool) $second->fresh()->is_primary);
    }

    public function test_delete_person_redirects_to_index(): void
    {
        $user = $this->user();
        $person = Person::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(PersonDetail::class, ['id' => $person->id])
            ->call('deletePerson')
            ->assertRedirect(route('people'));

        $this->assertSoftDeleted('people', ['id' => $person->id]);
    }

    public function test_timeline_shows_interactions(): void
    {
        $user = $this->user();
        $person = Person::factory()->for($user)->create();
        Interaction::factory()->create([
            'user_id' => $user->id,
            'person_id' => $person->id,
            'subject' => 'Important meeting',
            'channel' => 'meeting',
        ]);

        Livewire::actingAs($user)
            ->test(PersonDetail::class, ['id' => $person->id])
            ->assertSee('Important meeting');
    }
}
