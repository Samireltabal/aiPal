<?php

declare(strict_types=1);

namespace Tests\Feature\Productivity;

use App\Console\Commands\DispatchReminders;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DispatchRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_due_reminders(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Check emails',
            'body' => null,
            'remind_at' => now()->subMinute(),
            'channel' => 'email',
        ]);

        $this->artisan(DispatchReminders::class)->assertSuccessful();

        Notification::assertSentTo($user, ReminderNotification::class, function ($notification) use ($reminder) {
            return $notification->reminder->id === $reminder->id;
        });

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'sent_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_does_not_dispatch_future_reminders(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Future reminder',
            'remind_at' => now()->addHour(),
            'channel' => 'email',
        ]);

        $this->artisan(DispatchReminders::class)->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_re_dispatch_already_sent_reminders(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Already sent',
            'remind_at' => now()->subHour(),
            'channel' => 'email',
            'sent_at' => now()->subMinutes(5),
        ]);

        $this->artisan(DispatchReminders::class)->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_dispatches_nothing_when_no_reminders(): void
    {
        Notification::fake();

        $this->artisan(DispatchReminders::class)->assertSuccessful();

        Notification::assertNothingSent();
    }
}
