<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Workflow;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowOutputNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Workflow $workflow,
        public readonly string $output,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (property_exists($notifiable, 'push_notifications_enabled') && $notifiable->push_notifications_enabled) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Workflow: {$this->workflow->name}")
            ->greeting("Hi {$notifiable->name}!")
            ->line($this->output)
            ->line('— sent by aiPal workflow automation');
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public function toWebPush(object $notifiable): array
    {
        $body = mb_strimwidth($this->output, 0, 140, '…');

        return [
            "Workflow: {$this->workflow->name}",
            $body,
            '/workflows',
        ];
    }
}
