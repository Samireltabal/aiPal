<?php

declare(strict_types=1);

namespace Tests\Feature\Telegram;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\ReminderNotification;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TelegramChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_notification_uses_telegram_channel_when_set(): void
    {
        $user = User::factory()->create(['telegram_chat_id' => '55555']);

        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'channel' => 'telegram',
            'title' => 'Take meds',
        ]);

        $notification = new ReminderNotification($reminder);
        $channels = $notification->via($user);

        $this->assertContains(TelegramChannel::class, $channels);
    }

    public function test_telegram_channel_sends_message_to_linked_user(): void
    {
        $user = User::factory()->create(['telegram_chat_id' => '55555']);

        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'channel' => 'telegram',
            'title' => 'Take meds',
        ]);

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('send')
            ->once()
            ->withArgs(fn ($to, $msg) => $to === '55555' && str_contains($msg, 'Take meds'));

        $this->app->instance(TelegramService::class, $telegram);

        $notification = new ReminderNotification($reminder);
        $channel = $this->app->make(TelegramChannel::class);
        $channel->send($user, $notification);
    }

    public function test_telegram_channel_skips_user_without_linked_account(): void
    {
        $user = User::factory()->create(['telegram_chat_id' => null]);

        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'channel' => 'telegram',
            'title' => 'Take meds',
        ]);

        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('send')->never();

        $this->app->instance(TelegramService::class, $telegram);

        $notification = new ReminderNotification($reminder);
        $channel = $this->app->make(TelegramChannel::class);
        $channel->send($user, $notification);
    }

    public function test_to_telegram_formats_reminder_message(): void
    {
        $user = User::factory()->create();

        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'title' => 'Team standup',
            'body' => 'Join the call at 9am',
        ]);

        $notification = new ReminderNotification($reminder);
        $message = $notification->toTelegram($user);

        $this->assertStringContainsString('Team standup', $message);
        $this->assertStringContainsString('Join the call at 9am', $message);
    }
}
