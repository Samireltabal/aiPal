<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTask extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'create_task';
    }

    public static function toolLabel(): string
    {
        return 'Create Task';
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
        return 'Create ONE task for the user. Use when the user says "add a task", "I need to do...", "create a to-do", or similar. '
            .'IMPORTANT: For requests that imply many tasks (e.g. "create a task for each item"), list them and ask the user to confirm before bulk-creating.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('Short task title.')
                ->required(),
            'description' => $schema->string()
                ->description('Optional longer description. Pass null if not provided.')
                ->nullable()
                ->required(),
            'priority' => $schema->string()
                ->description('Task priority: "low", "medium", or "high". Pass null to use default (medium).')
                ->nullable()
                ->required(),
            'due_date' => $schema->string()
                ->description('Optional due date, e.g. "2026-05-01" or "next Friday". Pass null if not provided.')
                ->nullable()
                ->required(),
        ];
    }

    private const MAX_RECORDS_PER_TURN = 3;

    protected function execute(Request $request): Stringable|string
    {
        if ($this->user->createdRecordsThisTurn() >= self::MAX_RECORDS_PER_TURN) {
            return 'GUARDRAIL: You have already created '.$this->user->createdRecordsThisTurn().' records in this turn. '
                .'Stop and ask the user to confirm before creating more.';
        }

        $task = Task::create([
            'user_id' => $this->user->id,
            'context_id' => $this->user->currentContext()?->id,
            'title' => $request['title'],
            'description' => $request['description'] ?? null,
            'priority' => $request['priority'] ?? 'medium',
            'due_date' => isset($request['due_date']) ? now()->parse($request['due_date'])->toDateString() : null,
        ]);

        $this->user->incrementCreatedRecordsThisTurn();

        $due = $task->due_date ? " Due: {$task->due_date->toFormattedDateString()}." : '';

        return "Task created: \"{$task->title}\" [{$task->priority} priority].{$due} (ID: {$task->id})";
    }
}
