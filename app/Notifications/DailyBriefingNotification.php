<?php

declare(strict_types=1);

namespace App\Notifications;

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

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your Morning Briefing — {$this->date}")
            ->greeting("Good morning, {$notifiable->name}!")
            ->line($this->briefingContent)
            ->line('Have a productive day!');
    }
}
