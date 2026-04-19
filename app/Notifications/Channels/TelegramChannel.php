<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Services\TelegramService;
use Illuminate\Notifications\Notification;

class TelegramChannel
{
    public function __construct(private readonly TelegramService $telegram) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        $chatId = $notifiable->telegram_chat_id;

        if (! $chatId) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        $this->telegram->send($chatId, $message);
    }
}
