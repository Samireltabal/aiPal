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
        return 'Create ONE reminder for the user. Use when the user says "remind me to...", "set a reminder for...", or similar. Accepts natural language time expressions like "tomorrow at 9am" or "in 2 hours". '
            ."IMPORTANT: If the user's request would produce multiple reminders (e.g. \"remind me 5 minutes before each meeting\" with several meetings), DO NOT call this tool multiple times silently. "
            .'Instead, list what you would create and ASK the user to confirm before proceeding. Bulk creation without confirmation is a usability bug.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'natural_language' => $schema->string()
                ->description('The reminder request in natural language, e.g. "remind me to review the PR tomorrow at 9am".')
                ->required(),
        ];
    }

    /**
     * Hard cap on reminders/tasks/notes a single user turn can create before
     * the LLM is forced to stop and confirm. Defends against the failure mode
     * where the model loops "remind me 5 min before each meeting" into 7+
     * silent inserts.
     */
    private const MAX_RECORDS_PER_TURN = 3;

    protected function execute(Request $request): Stringable|string
    {
        if ($this->user->createdRecordsThisTurn() >= self::MAX_RECORDS_PER_TURN) {
            return 'GUARDRAIL: You have already created '.$this->user->createdRecordsThisTurn().' records in this turn. '
                .'Stop creating more and ask the user to confirm before continuing. '
                .'Summarize what you would create and wait for explicit approval.';
        }

        $naturalLanguage = $request['natural_language'];

        $defaultChannel = $this->user->default_reminder_channel ?? 'email';

        try {
            $parsed = (new ReminderParserAgent($defaultChannel))->prompt(
                $naturalLanguage,
                provider: config('ai.agents.reminder_parser.provider'),
                model: config('ai.agents.reminder_parser.model'),
            );
        } catch (\Throwable $e) {
            return __('Sorry, I couldn\'t set that reminder — the reminder service is unavailable. Please check your AI provider configuration (REMINDER_PARSER_PROVIDER).');
        }

        $remindAt = now()->parse($parsed['remind_at']);

        if ($remindAt->isPast()) {
            return "I couldn't parse a future date from that. Could you provide a more specific time?";
        }

        $body = trim((string) ($parsed['body'] ?? ''));

        $reminder = Reminder::create([
            'user_id' => $this->user->id,
            'context_id' => $this->user->currentContext()?->id,
            'title' => $parsed['title'],
            'body' => $body !== '' ? $body : null,
            'remind_at' => $remindAt,
            'channel' => $parsed['channel'],
        ]);

        $count = $this->user->incrementCreatedRecordsThisTurn();
        $remaining = max(0, self::MAX_RECORDS_PER_TURN - $count);
        $hint = $remaining === 0
            ? ' (You have hit the per-turn limit; confirm with the user before any further creates.)'
            : '';

        return "Reminder set: \"{$reminder->title}\" — I'll notify you on {$remindAt->toDayDateTimeString()} via {$reminder->channel}. (ID: {$reminder->id})".$hint;
    }
}
