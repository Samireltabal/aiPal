<?php

declare(strict_types=1);

namespace App\Ai\Agents\Productivity;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class ReminderParserAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly string $defaultChannel = 'email',
    ) {}

    public function instructions(): string
    {
        $now = now()->toIso8601String();
        $default = $this->defaultChannel;

        return <<<PROMPT
            You are a reminder parser. Given a natural language reminder request, extract the structured details.
            Current date/time: {$now}

            Rules:
            - "remind_at" must be an ISO 8601 datetime string in UTC based on the current time above.
            - "title" is a short summary (max 10 words).
            - "body" is the full reminder message to send to the user (optional, can be null).
            - "channel" must be "email", "webhook", "telegram", or "whatsapp".
            - Default channel is "{$default}" — use it unless the user explicitly names a different channel.
            - Use "telegram" only if the user says "via Telegram", "on Telegram", or similar.
            - Use "whatsapp" only if the user says "via WhatsApp", "on WhatsApp", or similar.
            - Use "email" only if the user says "via email", "by email", or similar.
            PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('Short title for the reminder.')
                ->required(),
            'body' => $schema->string()
                ->description('Full reminder message to send to the user. Empty string if not provided.')
                ->required(),
            'remind_at' => $schema->string()
                ->description('ISO 8601 UTC datetime when the reminder should fire.')
                ->required(),
            'channel' => $schema->string()
                ->enum(['email', 'webhook', 'telegram', 'whatsapp'])
                ->description('Delivery channel.')
                ->required(),
        ];
    }
}
