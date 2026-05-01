<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\Context;
use App\Models\Interaction;
use App\Models\Person;
use App\Models\PersonEmail;
use App\Models\PersonPhone;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_belongs_to_user_and_optional_context(): void
    {
        $user = User::factory()->create();
        $context = Context::factory()->for($user)->create();
        $person = Person::factory()->for($user)->create(['context_id' => $context->id]);

        $this->assertTrue($person->user->is($user));
        $this->assertTrue($person->context->is($context));
    }

    public function test_user_has_many_people(): void
    {
        $user = User::factory()->create();
        Person::factory()->for($user)->count(3)->create();
        Person::factory()->count(2)->create(); // other users

        $this->assertCount(3, $user->people);
    }

    public function test_email_is_lowercased_on_assignment(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        $email = PersonEmail::create([
            'person_id' => $person->id,
            'user_id' => $user->id,
            'email' => 'Sara.Bryant@Example.COM',
            'is_primary' => true,
        ]);

        $this->assertSame('sara.bryant@example.com', $email->fresh()->email);
    }

    public function test_emails_are_unique_per_user(): void
    {
        $user = User::factory()->create();
        $a = Person::factory()->for($user)->create();
        $b = Person::factory()->for($user)->create();

        PersonEmail::create([
            'person_id' => $a->id, 'user_id' => $user->id, 'email' => 'sara@example.com',
        ]);

        $this->expectException(QueryException::class);
        PersonEmail::create([
            'person_id' => $b->id, 'user_id' => $user->id, 'email' => 'sara@example.com',
        ]);
    }

    public function test_same_email_can_belong_to_different_users(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $aPerson = Person::factory()->for($alice)->create();
        $bPerson = Person::factory()->for($bob)->create();

        PersonEmail::create([
            'person_id' => $aPerson->id, 'user_id' => $alice->id, 'email' => 'sara@example.com',
        ]);
        PersonEmail::create([
            'person_id' => $bPerson->id, 'user_id' => $bob->id, 'email' => 'sara@example.com',
        ]);

        $this->assertSame(2, PersonEmail::count());
    }

    public function test_phones_are_unique_per_user(): void
    {
        $user = User::factory()->create();
        $a = Person::factory()->for($user)->create();
        $b = Person::factory()->for($user)->create();

        PersonPhone::create([
            'person_id' => $a->id, 'user_id' => $user->id, 'phone' => '+15551234567',
        ]);

        $this->expectException(QueryException::class);
        PersonPhone::create([
            'person_id' => $b->id, 'user_id' => $user->id, 'phone' => '+15551234567',
        ]);
    }

    public function test_primary_email_returns_flagged_one(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        PersonEmail::create([
            'person_id' => $person->id, 'user_id' => $user->id, 'email' => 'work@example.com', 'is_primary' => false,
        ]);
        PersonEmail::create([
            'person_id' => $person->id, 'user_id' => $user->id, 'email' => 'home@example.com', 'is_primary' => true,
        ]);

        $this->assertSame('home@example.com', $person->primaryEmail());
    }

    public function test_interactions_relate_to_person(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        Interaction::factory()->count(3)->create([
            'person_id' => $person->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(3, $person->interactions);
        $this->assertCount(3, $user->interactions);
    }

    public function test_soft_deletes_person(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        $person->delete();

        $this->assertCount(0, Person::all());
        $this->assertCount(1, Person::withTrashed()->get());
    }

    public function test_dedup_unique_external_id_per_channel_per_user(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        Interaction::factory()->create([
            'person_id' => $person->id,
            'user_id' => $user->id,
            'channel' => 'email',
            'external_id' => 'msg-abc-123',
        ]);

        $this->expectException(QueryException::class);
        Interaction::factory()->create([
            'person_id' => $person->id,
            'user_id' => $user->id,
            'channel' => 'email',
            'external_id' => 'msg-abc-123',
        ]);
    }

    public function test_null_external_ids_do_not_collide(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->for($user)->create();

        Interaction::factory()->count(3)->create([
            'person_id' => $person->id,
            'user_id' => $user->id,
            'channel' => 'note',
            'external_id' => null,
        ]);

        $this->assertSame(3, Interaction::count());
    }
}
