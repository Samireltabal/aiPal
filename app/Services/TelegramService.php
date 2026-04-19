<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MessagingChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService implements MessagingChannel
{
    private string $apiBase;

    public function __construct()
    {
        $token = config('services.telegram.bot_token');
        $this->apiBase = "https://api.telegram.org/bot{$token}";
    }

    public function send(string $recipient, string $message): void
    {
        $response = Http::timeout(10)
            ->post("{$this->apiBase}/sendMessage", [
                'chat_id' => $recipient,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);

        if (! $response->successful()) {
            Log::warning('Telegram sendMessage failed', [
                'chat_id' => $recipient,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    public function channelName(): string
    {
        return 'telegram';
    }

    public function setWebhook(string $url, string $secret): bool
    {
        $response = Http::timeout(10)
            ->post("{$this->apiBase}/setWebhook", [
                'url' => $url,
                'secret_token' => $secret,
                'allowed_updates' => ['message'],
            ]);

        return $response->successful() && $response->json('ok') === true;
    }

    public function deleteWebhook(): bool
    {
        $response = Http::timeout(10)
            ->post("{$this->apiBase}/deleteWebhook");

        return $response->successful();
    }
}
