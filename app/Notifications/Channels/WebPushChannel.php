<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Notifications\Notification;

class WebPushChannel
{
    public function __construct(private readonly WebPushService $pushService) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User) {
            return;
        }

        if (! $notifiable->push_notifications_enabled || $notifiable->pushSubscriptions()->doesntExist()) {
            return;
        }

        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        [$title, $body, $url] = $notification->toWebPush($notifiable);

        $this->pushService->send($notifiable, $title, $body, $url);
    }
}
