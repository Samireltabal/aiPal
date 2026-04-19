<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Notifications\ReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reminders:dispatch')]
#[Description('Send all due, unsent reminders to users.')]
class DispatchReminders extends Command
{
    public function handle(): int
    {
        $due = Reminder::query()
            ->whereNull('sent_at')
            ->where('remind_at', '<=', now())
            ->with('user')
            ->get();

        foreach ($due as $reminder) {
            $reminder->user->notify(new ReminderNotification($reminder));
            $reminder->update(['sent_at' => now()]);
        }

        $this->info("Dispatched {$due->count()} reminder(s).");

        return self::SUCCESS;
    }
}
