<?php

declare(strict_types=1);

namespace App\Ai\Agents\Productivity;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::OpenAI)]
#[Model('gpt-4o-mini')]
class ReminderParserAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        $now = now()->toIso8601String();

        return <<<PROMPT
            You are a reminder parser. Given a natural language reminder request, extract the structured details.
            Current date/time: {$now}

            Rules:
            - "remind_at" must be an ISO 8601 datetime string in UTC based on the current time above.
            - "title" is a short summary (max 10 words).
            - "body" is the full reminder message to send to the user (optional, can be null).
            - "channel" must be "email", "webhook", or "telegram". Default to "email" if not specified. Use "telegram" if the user says "via Telegram", "on Telegram", or similar.
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
                ->enum(['email', 'webhook', 'telegram'])
                ->description('Delivery channel.')
                ->required(),
        ];
    }
}
