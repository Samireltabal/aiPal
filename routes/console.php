<?php

use App\Jobs\DailyBriefingJob;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dispatch briefings every minute; the job checks each user's configured time.
Schedule::call(function () {
    $now = Carbon::now('UTC');

    User::query()
        ->where('briefing_enabled', true)
        ->whereNotNull('briefing_time')
        ->get()
        ->each(function (User $user) use ($now): void {
            $userNow = $now->copy()->setTimezone($user->briefing_timezone);
            $configuredTime = Carbon::parse($user->briefing_time, $user->briefing_timezone);

            $isCorrectMinute = $userNow->format('H:i') === $configuredTime->format('H:i');
            $notSentToday = $user->briefing_last_sent_at === null
                || $user->briefing_last_sent_at->copy()->setTimezone($user->briefing_timezone)->toDateString() !== $userNow->toDateString();

            if ($isCorrectMinute && $notSentToday) {
                DailyBriefingJob::dispatch($user->id);
            }
        });
})->everyMinute()->name('daily-briefings')->withoutOverlapping();

Schedule::command('reminders:dispatch')->everyMinute();

Schedule::command('workflows:dispatch-due')->everyMinute()->withoutOverlapping();
