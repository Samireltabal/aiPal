<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a Telegram inline-keyboard callback arrives via the bot webhook.
 * Listeners decide what to do based on the prefix of `callbackData`. This
 * event is the integration point for the premium addon's approval buttons.
 */
final class TelegramCallbackReceived
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly string $chatId,
        public readonly string $callbackData,
        public readonly string $callbackQueryId,
        public readonly ?int $messageId,
    ) {}
}
