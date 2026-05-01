<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\Person;
use App\Models\User;
use App\Modules\People\Services\ContactStaleness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContactStalenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_includes_people_never_contacted(): void
    {
        $user = User::factory()->create();
        Person::factory()->for($user)->create(['last_contact_at' => null, 'display_name' => 'Never Talked']);
        Person::factory()->for($user)->create(['last_contact_at' => Carbon::now()]);

        $stale = (new ContactStaleness)->query($user)->pluck('display_name')->all();

        $this->assertContains('Never Talked', $stale);
    }

    public function test_includes_people_past_threshold(): void
    {
        $user = User::factory()->create();
        $old = Person::factory()->for($user)->create(['last_contact_at' => Carbon::now()->subDays(120)]);
        $recent = Person::factory()->for($user)->create(['last_contact_at' => Carbon::now()->subDays(30)]);

        $ids = (new ContactStaleness)->query($user, days: 90)->pluck('id')->all();

        $this->assertContains($old->id, $ids);
        $this->assertNotContains($recent->id, $ids);
    }

    public function test_does_not_leak_across_users(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        Person::factory()->for($alice)->create(['last_contact_at' => null]);
        Person::factory()->for($bob)->create(['last_contact_at' => null]);

        $count = (new ContactStaleness)->count($alice);

        $this->assertSame(1, $count);
    }
}
