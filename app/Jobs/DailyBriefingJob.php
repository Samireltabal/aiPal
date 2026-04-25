<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\Briefing\DailyBriefingAgent;
use App\Models\Connection;
use App\Models\Context;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use App\Notifications\DailyBriefingNotification;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DailyBriefingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public readonly int $userId) {}

    public function handle(GoogleCalendarService $calendarService): void
    {
        $user = User::find($this->userId);
        if ($user === null || ! $user->briefing_enabled) {
            return;
        }

        $userNow = Carbon::now($user->briefing_timezone);
        $date = $userNow->toFormattedDateString();

        $contexts = $user->contexts()
            ->whereNull('archived_at')
            ->orderBy('is_default', 'desc')
            ->orderBy('kind')
            ->get();

        // Fallback: user with no contexts yet (pre-backfill edge case) — skip
        // briefing for this run; migration will backfill on next schedule.
        if ($contexts->isEmpty()) {
            return;
        }

        $perContext = $contexts->map(fn (Context $ctx) => [
            'context' => [
                'id' => $ctx->id,
                'kind' => $ctx->kind,
                'name' => $ctx->name,
                'is_default' => (bool) $ctx->is_default,
            ],
            'tasks' => $this->buildTasksContext($user, $ctx),
            'reminders' => $this->buildRemindersContext($user, $ctx, $userNow),
            'calendar' => $this->buildCalendarContext($user, $ctx, $calendarService),
        ])->all();

        $agent = new DailyBriefingAgent(
            userName: $user->name,
            date: $date,
            perContext: $perContext,
        );

        try {
            $briefingContent = (string) $agent->prompt(
                $agent->buildPrompt(),
                provider: config('ai.agents.daily_briefing.provider'),
                model: config('ai.agents.daily_briefing.model'),
            );
        } catch (\Throwable $e) {
            Log::warning('DailyBriefingAgent failed — check DAILY_BRIEFING_PROVIDER key', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
            ]);

            return;
        }

        $user->notify(new DailyBriefingNotification($briefingContent, $date));

        $user->update(['briefing_last_sent_at' => now()]);
    }

    private function buildTasksContext(User $user, Context $context): string
    {
        $tasks = Task::query()
            ->where('user_id', $user->id)
            ->where('context_id', $context->id)
            ->whereNull('completed_at')
            ->orderBy('priority', 'desc')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        if ($tasks->isEmpty()) {
            return 'No pending tasks.';
        }

        return $tasks->map(fn (Task $t) => "• [{$t->priority}] {$t->title}"
            .($t->due_date ? " (due {$t->due_date->toFormattedDateString()})" : '')
        )->join("\n");
    }

    private function buildRemindersContext(User $user, Context $context, Carbon $userNow): string
    {
        $reminders = Reminder::query()
            ->where('user_id', $user->id)
            ->where('context_id', $context->id)
            ->whereNull('sent_at')
            ->whereDate('remind_at', $userNow->toDateString())
            ->orderBy('remind_at')
            ->limit(10)
            ->get();

        if ($reminders->isEmpty()) {
            return 'No reminders scheduled for today.';
        }

        return $reminders->map(fn ($r) => "• {$r->title} at "
            .Carbon::parse($r->remind_at)->setTimezone($user->briefing_timezone)->format('g:i A')
        )->join("\n");
    }

    private function buildCalendarContext(User $user, Context $context, GoogleCalendarService $calendarService): string
    {
        // Only fetch calendar if this context has a calendar-capable connection.
        $hasCalendar = $context->connections()
            ->where('enabled', true)
            ->whereJsonContains('capabilities', Connection::CAPABILITY_CALENDAR)
            ->exists();

        if (! $hasCalendar) {
            return 'No calendar connected to this context.';
        }

        // Today: only Google is wired as a calendar provider. Microsoft joins
        // here (phase 3) by dispatching on connection provider.
        if (! $user->hasGoogleConnected()) {
            return 'Google Calendar not connected.';
        }

        $events = $calendarService->listTodayEvents($user);

        if (empty($events)) {
            return 'No calendar events today.';
        }

        return collect($events)->map(fn (array $e) => '• '.$e['summary']
            .' ('.Carbon::parse($e['start'])->setTimezone($user->briefing_timezone)->format('g:i A')
            .'–'.Carbon::parse($e['end'])->setTimezone($user->briefing_timezone)->format('g:i A').')'
        )->join("\n");
    }
}
