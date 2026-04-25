<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\Inbound\ForwardedEmailClassifierAgent;
use App\Ai\Agents\Productivity\ReminderParserAgent;
use App\Models\Context;
use App\Models\Memory;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class ForwardedEmailProcessor
{
    private ?Context $context = null;

    /**
     * Classify a forwarded email and persist it as a task/reminder/memory/note.
     *
     * Returns a one-line human-readable confirmation suitable for an email reply.
     */
    public function process(User $user, Context $context, string $subject, string $body, ?string $fromAddress): string
    {
        $this->context = $context;
        $excerpt = $this->buildExcerpt($subject, $body);

        try {
            $response = (new ForwardedEmailClassifierAgent)->prompt(
                $excerpt,
                provider: config('ai.agents.memory_extractor.provider'),
                model: config('ai.agents.memory_extractor.model'),
            );
            $parsed = method_exists($response, 'toArray') ? $response->toArray() : (array) $response;
        } catch (Throwable $e) {
            Log::warning('ForwardedEmailClassifierAgent failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return 'We received your email but could not classify it right now. It has been dropped.';
        }

        return match ($parsed['kind']) {
            'task' => $this->saveTask($user, $parsed),
            'reminder' => $this->saveReminder($user, $parsed, $excerpt),
            'memory' => $this->saveMemory($user, $parsed, $fromAddress),
            'note' => $this->saveNote($user, $parsed, $body),
            default => 'We received your email but could not classify it.',
        };
    }

    private function buildExcerpt(string $subject, string $body): string
    {
        $body = trim($body);
        $maxChars = 4000;

        if (mb_strlen($body) > $maxChars) {
            $body = mb_substr($body, 0, $maxChars)."\n…[truncated]";
        }

        return "Subject: {$subject}\n\nBody:\n{$body}";
    }

    /** @param array<string, mixed> $parsed */
    private function saveTask(User $user, array $parsed): string
    {
        $task = Task::create([
            'user_id' => $user->id,
            'context_id' => $this->context?->id ?? $user->defaultContext()?->id,
            'title' => (string) $parsed['title'],
            'description' => (string) $parsed['summary'],
            'priority' => (string) ($parsed['priority'] ?? 'medium'),
        ]);

        return "Saved as a task: \"{$task->title}\" (priority: {$task->priority}).";
    }

    /** @param array<string, mixed> $parsed */
    private function saveReminder(User $user, array $parsed, string $excerpt): string
    {
        $defaultChannel = $user->default_reminder_channel ?? 'email';

        try {
            $timedResponse = (new ReminderParserAgent($defaultChannel))->prompt(
                $excerpt,
                provider: config('ai.agents.reminder_parser.provider'),
                model: config('ai.agents.reminder_parser.model'),
            );
            $timed = method_exists($timedResponse, 'toArray') ? $timedResponse->toArray() : (array) $timedResponse;
        } catch (Throwable $e) {
            Log::warning('ReminderParserAgent failed during inbound-email processing', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->saveTask($user, $parsed);
        }

        $remindAt = now()->parse($timed['remind_at']);

        if ($remindAt->isPast()) {
            return $this->saveTask($user, $parsed);
        }

        $reminder = Reminder::create([
            'user_id' => $user->id,
            'context_id' => $this->context?->id ?? $user->defaultContext()?->id,
            'title' => (string) $timed['title'],
            'body' => trim((string) ($timed['body'] ?? '')) ?: null,
            'remind_at' => $remindAt,
            'channel' => (string) $timed['channel'],
        ]);

        return "Saved as a reminder: \"{$reminder->title}\" — firing {$remindAt->toDayDateTimeString()}.";
    }

    /** @param array<string, mixed> $parsed */
    private function saveMemory(User $user, array $parsed, ?string $fromAddress): string
    {
        Memory::create([
            'user_id' => $user->id,
            'context_id' => $this->context?->id ?? $user->defaultContext()?->id,
            'content' => (string) $parsed['summary'],
            'source' => $fromAddress !== null ? "forwarded-email:{$fromAddress}" : 'forwarded-email',
        ]);

        return "Saved to memory: {$parsed['title']}.";
    }

    /** @param array<string, mixed> $parsed */
    private function saveNote(User $user, array $parsed, string $body): string
    {
        $note = Note::create([
            'user_id' => $user->id,
            'context_id' => $this->context?->id ?? $user->defaultContext()?->id,
            'title' => (string) $parsed['title'],
            'content' => $body,
        ]);

        return "Saved as a note: \"{$note->title}\".";
    }
}
