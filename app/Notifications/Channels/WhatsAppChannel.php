<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Services\WhatsAppService;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(private readonly WhatsAppService $whatsApp) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $phone = $notifiable->whatsapp_phone;

        if (! $phone) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        $this->whatsApp->send($phone, $message);
    }
}
