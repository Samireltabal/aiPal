<?php

declare(strict_types=1);

namespace App\Modules\Extension\Services;

use App\Models\Context;
use App\Models\Memory;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Routes a polymorphic browser-extension capture payload to the right model.
 *
 * Why: keeps the controller dumb and concentrates the kind→model mapping
 * (plus its provenance/context fallback rules) in one place we can test.
 */
class CaptureService
{
    /**
     * @param  array{kind:string, url:string, title:string, prompt?:?string, selection?:?string, article?:?string, remind_at?:?string, context_id?:?int}  $data
     */
    public function capture(User $user, array $data): Model
    {
        $contextId = $data['context_id'] ?? $this->defaultContextId($user);

        return match ($data['kind']) {
            'memory' => $this->createMemory($user, $contextId, $data),
            'task' => $this->createTask($user, $contextId, $data),
            'note' => $this->createNote($user, $contextId, $data),
            'reminder' => $this->createReminder($user, $contextId, $data),
            default => throw new InvalidArgumentException("Unknown capture kind: {$data['kind']}"),
        };
    }

    private function defaultContextId(User $user): ?int
    {
        return $user->contexts()->where('is_default', true)->value('id')
            ?? $user->contexts()->orderBy('id')->value('id');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createMemory(User $user, ?int $contextId, array $data): Memory
    {
        $content = $this->composeContent($data, includeArticle: false);

        return Memory::create([
            'user_id' => $user->id,
            'context_id' => $contextId,
            'content' => $content,
            'source' => 'extension',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createTask(User $user, ?int $contextId, array $data): Task
    {
        $title = $this->prefer($data['prompt'] ?? null, $data['title']);
        $description = $this->composeContent($data, includeArticle: true);

        return Task::create([
            'user_id' => $user->id,
            'context_id' => $contextId,
            'title' => $title,
            'description' => $description,
            'priority' => 'normal',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createNote(User $user, ?int $contextId, array $data): Note
    {
        $content = $this->composeContent($data, includeArticle: true);

        return Note::create([
            'user_id' => $user->id,
            'context_id' => $contextId,
            'title' => $data['title'],
            'content' => $content,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createReminder(User $user, ?int $contextId, array $data): Reminder
    {
        $title = $this->prefer($data['prompt'] ?? null, $data['title']);
        $body = $this->composeContent($data, includeArticle: false);
        $remindAt = isset($data['remind_at'])
            ? Carbon::parse($data['remind_at'])
            : Carbon::now()->addHour();

        return Reminder::create([
            'user_id' => $user->id,
            'context_id' => $contextId,
            'title' => $title,
            'body' => $body,
            'remind_at' => $remindAt,
            'channel' => 'web',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function composeContent(array $data, bool $includeArticle): string
    {
        $parts = [];

        if (! empty($data['prompt'])) {
            $parts[] = $data['prompt'];
        }

        if (! empty($data['selection'])) {
            $parts[] = "> {$data['selection']}";
        }

        if ($includeArticle && ! empty($data['article'])) {
            $parts[] = $data['article'];
        }

        $parts[] = "\nSource: {$data['title']} — {$data['url']}";

        return trim(implode("\n\n", $parts));
    }

    private function prefer(?string $primary, string $fallback): string
    {
        $primary = $primary !== null ? trim($primary) : '';

        return $primary !== '' ? $primary : $fallback;
    }
}
