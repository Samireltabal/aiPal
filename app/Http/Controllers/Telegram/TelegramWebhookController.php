<?php

declare(strict_types=1);

namespace App\Http\Controllers\Telegram;

use App\Jobs\ProcessTelegramMessageJob;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class TelegramWebhookController
{
    public function __invoke(Request $request, TelegramService $telegram): Response
    {
        if (! $this->isValidSecret($request)) {
            return response('Unauthorized', 401);
        }

        $update = $request->json()->all();
        $message = $update['message'] ?? null;

        if (! $message) {
            return response('OK', 200);
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = trim($message['text'] ?? '');
        $voiceFileId = $message['voice']['file_id'] ?? null;
        $latitude = isset($message['location']['latitude']) ? (float) $message['location']['latitude'] : null;
        $longitude = isset($message['location']['longitude']) ? (float) $message['location']['longitude'] : null;

        if ($chatId === '') {
            return response('OK', 200);
        }

        if ($text === '' && $voiceFileId === null && $latitude === null) {
            return response('OK', 200);
        }

        // /start command: return the chat_id so the user can link their account
        if ($text === '/start') {
            $telegram->send($chatId, "Your chat ID is: `{$chatId}`\n\nEnter this in *Settings → Telegram* to link your account.");

            return response('OK', 200);
        }

        if (RateLimiter::tooManyAttempts("telegram:{$chatId}", 10)) {
            $telegram->send($chatId, 'Too many messages. Please wait a minute.');

            return response('OK', 200);
        }

        RateLimiter::hit("telegram:{$chatId}", 60);

        $user = User::query()->where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            $telegram->send($chatId, 'Your account is not linked. Start the bot with /start to get your chat ID, then enter it in Settings.');

            return response('OK', 200);
        }

        ProcessTelegramMessageJob::dispatch(
            $user->id,
            $chatId,
            $text ?: null,
            $voiceFileId,
            $latitude,
            $longitude,
        );

        return response('OK', 200);
    }

    private function isValidSecret(Request $request): bool
    {
        $expected = config('services.telegram.webhook_secret');

        if (! $expected) {
            return true;
        }

        return $request->header('X-Telegram-Bot-Api-Secret-Token') === $expected;
    }
}
