<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\Request;
use Stringable;

class MeetingNotesTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'meeting_notes';
    }

    public static function toolLabel(): string
    {
        return 'Meeting Notes → Tasks';
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
        return 'Extract action items from meeting notes and create tasks. Use when the user pastes meeting notes, a summary, or says "process my meeting notes" or "extract action items".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'notes' => $schema->string()
                ->description('The raw meeting notes or transcript to extract action items from.')
                ->required(),
            'create_tasks' => $schema->boolean()
                ->description('Whether to automatically create tasks for the extracted action items. Defaults to true.')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $extractor = new class extends \stdClass implements Agent, HasStructuredOutput
        {
            use Promptable;

            public function instructions(): string
            {
                return 'You are a meeting assistant. Extract clear, concrete action items from meeting notes. Ignore general discussion and focus only on specific commitments, tasks, or follow-ups.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'action_items' => $schema->array()
                        ->items($schema->object()->properties([
                            'title' => $schema->string()->description('Short task title (under 80 chars).'),
                            'owner' => $schema->string()->description('Person responsible. Use "me" if it refers to the note-taker. null if unclear.')->nullable(),
                            'due_date' => $schema->string()->description('Due date as YYYY-MM-DD if mentioned. null otherwise.')->nullable(),
                            'priority' => $schema->string()->enum(['low', 'medium', 'high'])->description('Infer priority from urgency language. Default: medium.'),
                            'notes' => $schema->string()->description('Optional additional context from the meeting.')->nullable(),
                        ])->required(['title', 'owner', 'due_date', 'priority', 'notes']))
                        ->required(),
                    'summary' => $schema->string()
                        ->description('One-sentence meeting summary.')
                        ->required(),
                ];
            }
        };

        $response = $extractor->prompt("Extract action items from these meeting notes:\n\n{$request['notes']}");

        $actionItems = $response['action_items'] ?? [];
        $summary = $response['summary'] ?? 'Meeting processed.';

        if (empty($actionItems)) {
            return "Meeting Summary: {$summary}\n\nNo action items found.";
        }

        $shouldCreate = $request['create_tasks'] !== false;
        $created = [];

        if ($shouldCreate) {
            foreach ($actionItems as $item) {
                if (strtolower($item['owner'] ?? 'me') !== 'me' && $item['owner'] !== null) {
                    continue;
                }

                Task::create([
                    'user_id' => $this->user->id,
                    'title' => $item['title'],
                    'description' => $item['notes'] ?? null,
                    'priority' => $item['priority'] ?? 'medium',
                    'due_date' => $item['due_date'] ?? null,
                ]);

                $created[] = $item['title'];
            }
        }

        $lines = ["**Meeting Summary:** {$summary}", ''];
        $lines[] = '**Action Items:**';

        foreach ($actionItems as $item) {
            $owner = $item['owner'] ? " [@{$item['owner']}]" : '';
            $due = $item['due_date'] ? " — due {$item['due_date']}" : '';
            $lines[] = "• [{$item['priority']}]{$owner} {$item['title']}{$due}";
        }

        if (! empty($created)) {
            $lines[] = '';
            $lines[] = '**Tasks created:** '.count($created).' task(s) added to your list.';
        }

        return implode("\n", $lines);
    }
}
