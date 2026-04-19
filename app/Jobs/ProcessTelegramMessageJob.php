<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\Chat\ChatAgent;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessTelegramMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly int $userId,
        public readonly string $chatId,
        public readonly string $text,
    ) {}

    public function handle(TelegramService $telegram): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $persona = $user->persona;

        $agent = (new ChatAgent)
            ->withUser($user)
            ->withSystemPrompt($persona?->system_prompt ?? 'You are a helpful personal assistant. Be concise, accurate, and friendly.');

        $conversationId = "telegram_{$this->chatId}";

        $reply = (string) $agent->remember($conversationId)->prompt($this->text);

        $telegram->send($this->chatId, $reply);
    }
}
