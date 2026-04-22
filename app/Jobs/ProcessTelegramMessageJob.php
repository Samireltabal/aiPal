<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\Chat\ChatAgent;
use App\Models\User;
use App\Services\Location\MessageLocationHandler;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Ai\Transcription;

class ProcessTelegramMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly int $userId,
        public readonly string $chatId,
        public readonly ?string $text,
        public readonly ?string $voiceFileId = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
    ) {}

    public function handle(TelegramService $telegram, MessageLocationHandler $locationHandler): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        // 1. Native location share
        if ($this->latitude !== null && $this->longitude !== null) {
            $confirmation = $locationHandler->handleNativeShare($user, $this->latitude, $this->longitude, 'telegram');
            if ($confirmation !== null) {
                $telegram->send($this->chatId, $confirmation);

                return;
            }
        }

        $persona = $user->persona;

        $text = $this->resolveText($telegram);

        if ($text === null) {
            return;
        }

        // 2. Maps URL in text
        $urlConfirmation = $locationHandler->handleTextMaybeContainingUrl($user, $text, 'maps_url');
        if ($urlConfirmation !== null) {
            $telegram->send($this->chatId, $urlConfirmation);

            return;
        }

        $agent = (new ChatAgent)
            ->withUser($user)
            ->withSystemPrompt($persona?->system_prompt ?? 'You are a helpful personal assistant. Be concise, accurate, and friendly.');

        if ($user->telegram_conversation_id) {
            $response = $agent->continue($user->telegram_conversation_id, as: $user)->prompt($text);
        } else {
            $response = $agent->forUser($user)->prompt($text);
            $user->update(['telegram_conversation_id' => $response->conversationId]);
        }

        $reply = (string) $response;

        $telegram->send($this->chatId, $reply);
    }

    private function resolveText(TelegramService $telegram): ?string
    {
        if ($this->text !== null) {
            return $this->text;
        }

        if ($this->voiceFileId === null) {
            return null;
        }

        $tmpPath = $telegram->downloadVoice($this->voiceFileId);

        if ($tmpPath === null) {
            return null;
        }

        try {
            return (string) Transcription::fromPath($tmpPath)->generate();
        } finally {
            @unlink($tmpPath);
        }
    }
}
