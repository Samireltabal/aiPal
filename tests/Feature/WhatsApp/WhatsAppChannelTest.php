<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsApp;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\ReminderNotification;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_notification_uses_whatsapp_channel_when_channel_is_whatsapp(): void
    {
        $user = User::factory()->create(['whatsapp_phone' => '201234567890']);
        $reminder = Reminder::factory()->create(['user_id' => $user->id, 'channel' => 'whatsapp']);

        $notification = new ReminderNotification($reminder);

        $this->assertContains(WhatsAppChannel::class, $notification->via($user));
    }

    public function test_sends_message_to_linked_user(): void
    {
        $user = User::factory()->create(['whatsapp_phone' => '201234567890']);
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'title' => 'Buy milk',
            'body' => null,
            'channel' => 'whatsapp',
        ]);

        $whatsApp = $this->mock(WhatsAppService::class);
        $whatsApp->shouldReceive('send')
            ->once()
            ->withArgs(fn ($to, $msg) => $to === '201234567890' && str_contains($msg, 'Buy milk'));

        $channel = app(WhatsAppChannel::class);
        $channel->send($user, new ReminderNotification($reminder));
    }

    public function test_skips_users_without_linked_whatsapp(): void
    {
        $user = User::factory()->create(['whatsapp_phone' => null]);
        $reminder = Reminder::factory()->create(['user_id' => $user->id, 'channel' => 'whatsapp']);

        $whatsApp = $this->mock(WhatsAppService::class);
        $whatsApp->shouldNotReceive('send');

        $channel = app(WhatsAppChannel::class);
        $channel->send($user, new ReminderNotification($reminder));
    }

    public function test_formats_reminder_with_body(): void
    {
        $user = User::factory()->create(['whatsapp_phone' => '201234567890']);
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'title' => 'Call doctor',
            'body' => 'At 3pm',
            'channel' => 'whatsapp',
        ]);

        $whatsApp = $this->mock(WhatsAppService::class);
        $whatsApp->shouldReceive('send')
            ->once()
            ->withArgs(fn ($to, $msg) => str_contains($msg, 'Call doctor') && str_contains($msg, 'At 3pm'));

        $channel = app(WhatsAppChannel::class);
        $channel->send($user, new ReminderNotification($reminder));
    }
}
