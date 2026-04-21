<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class NotifyUserTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'notify_user';
    }

    public static function toolLabel(): string
    {
        return 'Notify Me';
    }

    public static function toolCategory(): string
    {
        return 'productivity';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Send a notification message to the user via WhatsApp or Telegram. Use this at the end of a long task when the user asked to be notified upon completion, e.g. "review this MR and notify me on WhatsApp when done".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()
                ->description('The notification message to send. Keep it concise and informative.')
                ->required(),
            'channel' => $schema->string()
                ->description('Delivery channel: "whatsapp", "telegram", or "auto" to use the user\'s default. Defaults to "auto".')
                ->enum(['whatsapp', 'telegram', 'auto'])
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $message = $request['message'];
        $channel = $request['channel'] ?? 'auto';

        if ($channel === 'auto') {
            $channel = $this->user->default_reminder_channel ?? 'telegram';
        }

        return match ($channel) {
            'whatsapp' => $this->sendWhatsApp($message),
            'telegram' => $this->sendTelegram($message),
            default => 'Unknown channel: '.$channel,
        };
    }

    private function sendWhatsApp(string $message): string
    {
        if (! $this->user->hasWhatsAppLinked()) {
            return 'WhatsApp is not connected. Please link your WhatsApp number in Settings.';
        }

        (new WhatsAppService)->send((string) $this->user->whatsapp_phone, $message);

        return 'Notification sent via WhatsApp.';
    }

    private function sendTelegram(string $message): string
    {
        if (! $this->user->hasTelegramLinked()) {
            return 'Telegram is not connected. Please link your Telegram account in Settings.';
        }

        (new TelegramService)->send((string) $this->user->telegram_chat_id, $message);

        return 'Notification sent via Telegram.';
    }
}
