<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyBriefingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $briefingContent,
        public readonly string $date,
    ) {}

    /**
     * Channel preference: Telegram if linked, else email. WhatsApp will join
     * this list once a pre-approved UTILITY template is available (see
     * docs/ideas/top-3-features-plan.md).
     */
    public function via(object $notifiable): array
    {
        if (! empty($notifiable->telegram_chat_id)) {
            return [TelegramChannel::class];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Today's focus — {$this->date}")
            ->greeting("Good morning, {$notifiable->name}.")
            ->line($this->briefingContent);
    }

    public function toTelegram(object $notifiable): string
    {
        return "🌅 *Today's focus — {$this->date}*\n\n{$this->briefingContent}";
    }
}
