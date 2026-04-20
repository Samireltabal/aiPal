<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Agents\Productivity\ReminderParserAgent;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateReminder extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'create_reminder';
    }

    public static function toolLabel(): string
    {
        return 'Create Reminder';
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
        return 'Create a reminder for the user. Use when the user says "remind me to...", "set a reminder for...", or similar. Accepts natural language time expressions like "tomorrow at 9am" or "in 2 hours".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'natural_language' => $schema->string()
                ->description('The reminder request in natural language, e.g. "remind me to review the PR tomorrow at 9am".')
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $naturalLanguage = $request['natural_language'];

        $defaultChannel = $this->user->default_reminder_channel ?? 'email';
        $parsed = (new ReminderParserAgent($defaultChannel))->prompt($naturalLanguage);

        $remindAt = now()->parse($parsed['remind_at']);

        if ($remindAt->isPast()) {
            return "I couldn't parse a future date from that. Could you provide a more specific time?";
        }

        $body = trim((string) ($parsed['body'] ?? ''));

        $reminder = Reminder::create([
            'user_id' => $this->user->id,
            'title' => $parsed['title'],
            'body' => $body !== '' ? $body : null,
            'remind_at' => $remindAt,
            'channel' => $parsed['channel'],
        ]);

        return "Reminder set: \"{$reminder->title}\" — I'll notify you on {$remindAt->toDayDateTimeString()} via {$reminder->channel}. (ID: {$reminder->id})";
    }
}
