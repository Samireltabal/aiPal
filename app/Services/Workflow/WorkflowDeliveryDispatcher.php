<?php

declare(strict_types=1);

namespace App\Services\Workflow;

use App\Models\Workflow;
use App\Notifications\WorkflowOutputNotification;
use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class WorkflowDeliveryDispatcher
{
    public function deliver(Workflow $workflow, string $output): void
    {
        $channel = $workflow->delivery_channel ?? 'notification';
        $user = $workflow->user;

        if ($output === '') {
            return;
        }

        $header = "[{$workflow->name}]\n\n";

        try {
            match ($channel) {
                'telegram' => $this->sendTelegram($user->telegram_chat_id ?? null, $header.$output),
                'whatsapp' => $this->sendWhatsApp($user->whatsapp_phone ?? null, $header.$output),
                'email' => Notification::route('mail', $user->email)->notify(new WorkflowOutputNotification($workflow, $output)),
                'notification' => $user->notify(new WorkflowOutputNotification($workflow, $output)),
                'none' => null,
                default => Log::warning('Unknown workflow delivery channel', ['channel' => $channel, 'workflow_id' => $workflow->id]),
            };
        } catch (\Throwable $e) {
            Log::warning('Workflow delivery failed', [
                'workflow_id' => $workflow->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendTelegram(?string $chatId, string $message): void
    {
        if (! $chatId) {
            return;
        }

        (new TelegramService)->send($chatId, $message);
    }

    private function sendWhatsApp(?string $phone, string $message): void
    {
        if (! $phone) {
            return;
        }

        (new WhatsAppService)->send($phone, $message);
    }
}
