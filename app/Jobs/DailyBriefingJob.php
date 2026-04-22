<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\Briefing\DailyBriefingAgent;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use App\Notifications\DailyBriefingNotification;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

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

        $tasksContext = $this->buildTasksContext($user);
        $remindersContext = $this->buildRemindersContext($user, $userNow);
        $calendarContext = $this->buildCalendarContext($user, $calendarService);

        $agent = new DailyBriefingAgent(
            userName: $user->name,
            date: $date,
            tasksContext: $tasksContext,
            remindersContext: $remindersContext,
            calendarContext: $calendarContext,
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

    private function buildTasksContext(User $user): string
    {
        $tasks = Task::query()
            ->where('user_id', $user->id)
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

    private function buildRemindersContext(User $user, Carbon $userNow): string
    {
        $reminders = Reminder::query()
            ->where('user_id', $user->id)
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

    private function buildCalendarContext(User $user, GoogleCalendarService $calendarService): string
    {
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
