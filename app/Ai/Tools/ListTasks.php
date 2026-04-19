<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListTasks extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'list_tasks';
    }

    public static function toolLabel(): string
    {
        return 'List Tasks';
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
        return 'List the user\'s tasks. Use when asked about pending tasks, to-dos, or what needs to be done.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'include_completed' => $schema->boolean()
                ->description('Whether to include completed tasks. Defaults to false. Pass null to use default.')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $includeCompleted = (bool) ($request['include_completed'] ?? false);

        $query = Task::query()->where('user_id', $this->user->id);

        if (! $includeCompleted) {
            $query->whereNull('completed_at');
        }

        $tasks = $query->orderBy('priority', 'desc')->orderBy('due_date')->limit(20)->get();

        if ($tasks->isEmpty()) {
            return $includeCompleted ? 'No tasks found.' : 'No pending tasks.';
        }

        return $tasks->map(function (Task $task) {
            $status = $task->isCompleted() ? '[done]' : '[ ]';
            $due = $task->due_date ? " — due {$task->due_date->toFormattedDateString()}" : '';
            $priority = "[{$task->priority}]";

            return "{$status} {$priority} {$task->title}{$due}";
        })->join("\n");
    }
}
