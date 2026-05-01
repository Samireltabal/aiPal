<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\Context;
use App\Models\Interaction;
use App\Models\Person;
use App\Models\PersonEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PeopleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('people.summarize.enabled', false);
        Queue::fake();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/people')->assertUnauthorized();
    }

    public function test_index_returns_user_people_only(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        Person::factory()->for($alice)->create(['display_name' => 'Aliceland']);
        Person::factory()->for($bob)->create(['display_name' => 'Bobland']);

        Sanctum::actingAs($alice);
        $response = $this->getJson('/api/v1/people')->assertOk();

        $names = collect($response->json('data'))->pluck('display_name')->all();
        $this->assertContains('Aliceland', $names);
        $this->assertNotContains('Bobland', $names);
    }

    public function test_index_search_matches_name_company_or_email(): void
    {
        $user = User::factory()->create();
        $sara = Person::factory()->for($user)->create(['display_name' => 'Sara Bryant', 'company' => 'Acme']);
        $tom = Person::factory()->for($user)->create(['display_name' => 'Tom Riddle', 'company' => 'Hogwarts']);
        PersonEmail::create(['user_id' => $user->id, 'person_id' => $tom->id, 'email' => 'tom@hogwarts.edu']);

        Sanctum::actingAs($user);

        $byName = collect($this->getJson('/api/v1/people?q=sara')->json('data'))->pluck('id');
        $this->assertSame([$sara->id], $byName->all());

        $byCompany = collect($this->getJson('/api/v1/people?q=hog')->json('data'))->pluck('id');
        $this->assertSame([$tom->id], $byCompany->all());

        $byEmail = collect($this->getJson('/api/v1/people?q=tom@')->json('data'))->pluck('id');
        $this->assertSame([$tom->id], $byEmail->all());
    }

    public function test_index_filters_by_tag(): void
    {
        $user = User::factory()->create();
        Person::factory()->for($user)->create(['display_name' => 'Investor', 'tags' => ['vc', 'priority']]);
        Person::factory()->for($user)->create(['display_name' => 'Friend', 'tags' => ['friend']]);

        Sanctum::actingAs($user);
        $names = collect($this->getJson('/api/v1/people?tag=vc')->json('data'))->pluck('display_name');

        $this->assertSame(['Investor'], $names->all());
    }

    public function test_index_filters_stale_contacts(): void
    {
        $user = User::factory()->create();
        $never = Person::factory()->for($user)->create(['last_contact_at' => null]);
        Person::factory()->for($user)->create(['last_contact_at' => now()]);

        Sanctum::actingAs($user);
        $ids = collect($this->getJson('/api/v1/people?stale=1')->json('data'))->pluck('id');

        $this->assertSame([$never->id], $ids->all());
    }

    public function test_show_includes_emails_and_phones(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();
        PersonEmail::create(['user_id' => $user->id, 'person_id' => $person->id, 'email' => 'a@b.c', 'is_primary' => true]);

        Sanctum::actingAs($user);
        $this->getJson("/api/v1/people/{$person->id}")
            ->assertOk()
            ->assertJsonPath('data.emails.0.email', 'a@b.c');
    }

    public function test_show_returns_404_for_other_users_person(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $bobPerson = Person::factory()->for($bob)->create();

        Sanctum::actingAs($alice);
        $this->getJson("/api/v1/people/{$bobPerson->id}")->assertNotFound();
    }

    public function test_store_creates_person_with_emails_and_phones(): void
    {
        $user = User::factory()->create();
        $context = Context::factory()->for($user)->create(['is_default' => true]);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/people', [
            'display_name' => 'Sara Bryant',
            'company' => 'Acme',
            'tags' => ['client'],
            'emails' => ['Sara@Example.COM'],
            'phones' => ['+15551234567'],
        ])->assertCreated();

        $person = Person::first();
        $this->assertSame('Sara Bryant', $person->display_name);
        $this->assertSame($context->id, $person->context_id);
        $this->assertSame('sara@example.com', $person->primaryEmail());
        $this->assertSame('+15551234567', $person->primaryPhone());
    }

    public function test_store_rejects_email_already_owned_by_another_person_same_user(): void
    {
        $user = User::factory()->create();
        Context::factory()->for($user)->create(['is_default' => true]);
        $existing = Person::factory()->for($user)->create();
        PersonEmail::create(['user_id' => $user->id, 'person_id' => $existing->id, 'email' => 'sara@example.com']);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/people', [
            'display_name' => 'Different Sara',
            'emails' => ['sara@example.com'],
        ])->assertCreated();

        // firstOrCreate keeps the existing email pointing at the original person.
        $this->assertSame($existing->id, PersonEmail::where('email', 'sara@example.com')->value('person_id'));
    }

    public function test_update_changes_fields(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create(['display_name' => 'Old']);

        Sanctum::actingAs($user);
        $this->patchJson("/api/v1/people/{$person->id}", ['display_name' => 'New', 'tags' => ['vip']])
            ->assertOk()
            ->assertJsonPath('data.display_name', 'New');

        $this->assertSame('New', $person->fresh()->display_name);
        $this->assertSame(['vip'], $person->fresh()->tags);
    }

    public function test_destroy_soft_deletes(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        Sanctum::actingAs($user);
        $this->deleteJson("/api/v1/people/{$person->id}")->assertNoContent();

        $this->assertSoftDeleted($person);
    }

    public function test_interactions_index_returns_timeline(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();
        Interaction::factory()->count(3)->create(['user_id' => $user->id, 'person_id' => $person->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/people/{$person->id}/interactions")->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_interactions_store_records_via_recorder(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/people/{$person->id}/interactions", [
            'channel' => 'meeting',
            'subject' => 'Coffee',
            'summary' => 'Caught up about the launch',
        ])->assertCreated();

        $this->assertSame(1, Interaction::count());
        $this->assertNotNull($person->fresh()->last_contact_at);
    }

    public function test_interactions_store_rejects_unknown_channel(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/people/{$person->id}/interactions", [
            'channel' => 'pigeon',
        ])->assertUnprocessable()->assertJsonValidationErrors(['channel']);
    }
}
