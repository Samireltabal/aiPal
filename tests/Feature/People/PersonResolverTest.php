<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\Context;
use App\Models\Person;
use App\Models\PersonEmail;
use App\Models\User;
use App\Modules\People\Services\PersonResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): PersonResolver
    {
        return new PersonResolver;
    }

    public function test_creates_person_from_named_address(): void
    {
        $user = User::factory()->create();
        Context::factory()->for($user)->create(['is_default' => true]);

        $person = $this->resolver()->fromEmail($user, '"Sara Bryant" <sara@example.com>');

        $this->assertNotNull($person);
        $this->assertSame('Sara Bryant', $person->display_name);
        $this->assertSame('sara@example.com', $person->primaryEmail());
    }

    public function test_returns_existing_person_for_same_email_lowercase(): void
    {
        $user = User::factory()->create();
        Context::factory()->for($user)->create(['is_default' => true]);

        $first = $this->resolver()->fromEmail($user, 'Sara@Example.COM');
        $second = $this->resolver()->fromEmail($user, '<SARA@example.com>');

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PersonEmail::count());
    }

    public function test_returns_null_for_transactional_local_part(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->resolver()->fromEmail($user, 'noreply@github.com'));
        $this->assertNull($this->resolver()->fromEmail($user, 'do-not-reply@stripe.com'));
        $this->assertNull($this->resolver()->fromEmail($user, 'notifications@linkedin.com'));
        $this->assertNull($this->resolver()->fromEmail($user, 'mailer-daemon@google.com'));
        $this->assertSame(0, Person::count());
    }

    public function test_returns_null_for_blocklisted_domain(): void
    {
        config()->set('people.transactional_domains', ['mailgun.org']);
        $user = User::factory()->create();

        $this->assertNull($this->resolver()->fromEmail($user, 'campaign-12345@mailgun.org'));
    }

    public function test_returns_null_for_invalid_input(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->resolver()->fromEmail($user, null));
        $this->assertNull($this->resolver()->fromEmail($user, ''));
        $this->assertNull($this->resolver()->fromEmail($user, 'not-an-email'));
    }

    public function test_falls_back_to_local_part_for_display_name(): void
    {
        $user = User::factory()->create();

        $person = $this->resolver()->fromEmail($user, 'tom.riddle@hogwarts.edu');

        $this->assertSame('Tom Riddle', $person->display_name);
    }

    public function test_strips_plus_addressing_from_fallback_name(): void
    {
        $user = User::factory()->create();

        $person = $this->resolver()->fromEmail($user, 'sara.bryant+work@example.com');

        $this->assertSame('Sara Bryant', $person->display_name);
        $this->assertSame('sara.bryant+work@example.com', $person->primaryEmail());
    }

    public function test_attaches_provided_context(): void
    {
        $user = User::factory()->create();
        $work = Context::factory()->for($user)->create(['is_default' => false, 'name' => 'Work']);
        Context::factory()->for($user)->create(['is_default' => true, 'name' => 'Personal']);

        $person = $this->resolver()->fromEmail($user, 'sara@example.com', $work->id);

        $this->assertSame($work->id, $person->context_id);
    }

    public function test_falls_back_to_default_context(): void
    {
        $user = User::factory()->create();
        $default = Context::factory()->for($user)->create(['is_default' => true]);

        $person = $this->resolver()->fromEmail($user, 'sara@example.com');

        $this->assertSame($default->id, $person->context_id);
    }

    public function test_does_not_leak_across_users(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $aPerson = $this->resolver()->fromEmail($alice, 'sara@example.com');
        $bPerson = $this->resolver()->fromEmail($bob, 'sara@example.com');

        $this->assertNotSame($aPerson->id, $bPerson->id);
        $this->assertSame($alice->id, $aPerson->user_id);
        $this->assertSame($bob->id, $bPerson->user_id);
    }
}
