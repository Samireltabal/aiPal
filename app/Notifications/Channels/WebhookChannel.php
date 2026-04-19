<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebhook')) {
            return;
        }

        $url = $notifiable->reminders()
            ->find(data_get($notification, 'reminder.id'))
            ?->webhook_url;

        if (! $url) {
            Log::warning('WebhookChannel: no webhook_url set for reminder', [
                'reminder_id' => data_get($notification, 'reminder.id'),
            ]);

            return;
        }

        Http::timeout(10)
            ->retry(2, 500)
            ->post($url, $notification->toWebhook($notifiable));
    }
}
