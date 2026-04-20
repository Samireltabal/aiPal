<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Reminder;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Channels\WebhookChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Reminder $reminder,
    ) {}

    public function via(object $notifiable): array
    {
        return match ($this->reminder->channel) {
            'webhook' => [WebhookChannel::class],
            'telegram' => [TelegramChannel::class],
            'whatsapp' => [WhatsAppChannel::class],
            default => ['mail'],
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Reminder: {$this->reminder->title}")
            ->greeting("Hi {$notifiable->name}!")
            ->line($this->reminder->body ?? $this->reminder->title);

        return $message->line('This reminder was set via aiPal.');
    }

    public function toTelegram(object $notifiable): string
    {
        $body = $this->reminder->body ? "\n{$this->reminder->body}" : '';

        return "⏰ *Reminder:* {$this->reminder->title}{$body}";
    }

    public function toWhatsApp(object $notifiable): string
    {
        $body = $this->reminder->body ? "\n{$this->reminder->body}" : '';

        return "⏰ *Reminder:* {$this->reminder->title}{$body}";
    }

    public function toWebhook(object $notifiable): array
    {
        return [
            'event' => 'reminder.fired',
            'reminder' => [
                'id' => $this->reminder->id,
                'title' => $this->reminder->title,
                'body' => $this->reminder->body,
                'remind_at' => $this->reminder->remind_at?->toIso8601String(),
            ],
            'user' => [
                'id' => $notifiable->id,
                'email' => $notifiable->email,
            ],
        ];
    }
}
