<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\Person;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckBirthdaysCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_reminder_for_upcoming_birthday(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $birthday = now()->addDays(3);
        Person::factory()->for($user)->create([
            'display_name' => 'Birthday Buddy',
            'birthday' => $birthday->copy()->subYears(30),
        ]);

        $this->artisan('people:birthday-check')->assertOk();

        $this->assertDatabaseHas('reminders', [
            'user_id' => $user->id,
            'title' => 'Birthday: Birthday Buddy',
        ]);
    }

    public function test_does_not_create_reminder_outside_lookahead(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        Person::factory()->for($user)->create([
            'display_name' => 'Faraway',
            'birthday' => now()->addDays(60)->subYears(20),
        ]);

        $this->artisan('people:birthday-check')->assertOk();

        $this->assertDatabaseMissing('reminders', ['title' => 'Birthday: Faraway']);
    }

    public function test_idempotent_within_same_year(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        Person::factory()->for($user)->create([
            'display_name' => 'Idempotent Bob',
            'birthday' => now()->addDays(2)->subYears(40),
        ]);

        $this->artisan('people:birthday-check')->assertOk();
        $this->artisan('people:birthday-check')->assertOk();

        $count = Reminder::query()
            ->where('user_id', $user->id)
            ->where('title', 'Birthday: Idempotent Bob')
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_skips_people_without_birthday(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        Person::factory()->for($user)->create(['display_name' => 'No Birthday', 'birthday' => null]);

        $this->artisan('people:birthday-check')->assertOk();

        $this->assertDatabaseMissing('reminders', ['user_id' => $user->id]);
    }
}
