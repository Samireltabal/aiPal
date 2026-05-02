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

    /**
     * Send a message with an inline keyboard. Used for approval prompts.
     *
     * @param  list<list<array{text: string, callback_data: string}>>  $keyboard
     */
    public function sendWithKeyboard(string $recipient, string $message, array $keyboard): ?int
    {
        $response = Http::timeout(10)
            ->post("{$this->apiBase}/sendMessage", [
                'chat_id' => $recipient,
                'text' => $message,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            ]);

        if (! $response->successful()) {
            Log::warning('Telegram sendMessage (keyboard) failed', [
                'chat_id' => $recipient,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('result.message_id');
    }

    /**
     * Answer a callback_query so the spinner on the button stops.
     */
    public function answerCallback(string $callbackQueryId, ?string $text = null): void
    {
        $payload = ['callback_query_id' => $callbackQueryId];
        if ($text !== null) {
            $payload['text'] = $text;
        }

        Http::timeout(10)->post("{$this->apiBase}/answerCallbackQuery", $payload);
    }

    /**
     * Edit a previously-sent message. Used after a callback decision to remove
     * the buttons and reflect the outcome.
     */
    public function editMessage(string $chatId, int $messageId, string $text): void
    {
        Http::timeout(10)
            ->post("{$this->apiBase}/editMessageText", [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);
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
                'allowed_updates' => ['message', 'callback_query'],
            ]);

        return $response->successful() && $response->json('ok') === true;
    }

    public function deleteWebhook(): bool
    {
        $response = Http::timeout(10)
            ->post("{$this->apiBase}/deleteWebhook");

        return $response->successful();
    }

    /**
     * Download a voice file and save it to a temp path. Returns the path or null on failure.
     */
    public function downloadVoice(string $fileId): ?string
    {
        $response = Http::timeout(10)
            ->get("{$this->apiBase}/getFile", ['file_id' => $fileId]);

        if (! $response->successful()) {
            Log::warning('Telegram getFile failed', ['file_id' => $fileId]);

            return null;
        }

        $filePath = $response->json('result.file_path');

        if (! $filePath) {
            return null;
        }

        $token = config('services.telegram.bot_token');
        $download = Http::timeout(30)->get("https://api.telegram.org/file/bot{$token}/{$filePath}");

        if (! $download->successful()) {
            Log::warning('Telegram file download failed', ['file_path' => $filePath]);

            return null;
        }

        $tmpPath = sys_get_temp_dir().'/tg_voice_'.uniqid().'.ogg';
        file_put_contents($tmpPath, $download->body());

        return $tmpPath;
    }
}
