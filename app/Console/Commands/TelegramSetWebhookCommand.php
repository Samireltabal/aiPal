<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook {--delete : Remove the webhook instead}';

    protected $description = 'Register (or remove) the Telegram bot webhook';

    public function handle(TelegramService $telegram): int
    {
        if ($this->option('delete')) {
            $ok = $telegram->deleteWebhook();
            $ok ? $this->info('Webhook deleted.') : $this->error('Failed to delete webhook.');

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        $url = rtrim(config('app.url'), '/').'/webhooks/telegram';
        $secret = config('services.telegram.webhook_secret', '');

        $ok = $telegram->setWebhook($url, $secret);

        if ($ok) {
            $this->info("Webhook set to: {$url}");
        } else {
            $this->error('Failed to set webhook. Check TELEGRAM_BOT_TOKEN and that APP_URL is publicly reachable.');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
