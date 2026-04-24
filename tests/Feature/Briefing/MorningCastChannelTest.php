<?php

declare(strict_types=1);

namespace Tests\Feature\Briefing;

use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\DailyBriefingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MorningCastChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_routes_to_telegram_when_user_has_linked_telegram(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '123456789',
        ]);

        $notification = new DailyBriefingNotification('Focus on X.', 'Fri, 24 Apr 2026');

        $this->assertSame([TelegramChannel::class], $notification->via($user));
    }

    public function test_routes_to_email_when_no_telegram_link(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => null,
        ]);

        $notification = new DailyBriefingNotification('Focus on X.', 'Fri, 24 Apr 2026');

        $this->assertSame(['mail'], $notification->via($user));
    }

    public function test_telegram_payload_contains_date_and_body(): void
    {
        $user = User::factory()->create(['telegram_chat_id' => '123']);
        $notification = new DailyBriefingNotification(
            "Today looks quiet.\n• Deep work on Feature X\n• Reply to Sam\n• Close inbox at 5pm",
            'Fri, 24 Apr 2026',
        );

        $message = $notification->toTelegram($user);

        $this->assertStringContainsString('Fri, 24 Apr 2026', $message);
        $this->assertStringContainsString('Today looks quiet.', $message);
        $this->assertStringContainsString('Reply to Sam', $message);
    }

    public function test_mail_payload_uses_focus_subject(): void
    {
        $user = User::factory()->create();
        $notification = new DailyBriefingNotification('Top 3 things today.', 'Fri, 24 Apr 2026');

        $mail = $notification->toMail($user);

        $this->assertStringContainsString("Today's focus", $mail->subject);
        $this->assertStringContainsString('Fri, 24 Apr 2026', $mail->subject);
    }
}
