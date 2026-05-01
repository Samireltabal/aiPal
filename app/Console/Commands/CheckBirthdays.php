<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Person;
use App\Models\Reminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Walks all soft-active People with a birthday and creates a Reminder for
 * each upcoming birthday inside the lookahead window. Idempotent per
 * (person, year): the reminder body's metadata-style title prefix carries
 * the year so re-running on the same day doesn't duplicate.
 */
class CheckBirthdays extends Command
{
    protected $signature = 'people:birthday-check {--days= : Lookahead window override}';

    protected $description = 'Create reminders for upcoming birthdays';

    public function handle(): int
    {
        $lookahead = (int) ($this->option('days') ?? config('people.birthday_lookahead_days', 7));
        $today = Carbon::today();
        $created = 0;

        Person::query()
            ->whereNotNull('birthday')
            ->with('user')
            ->chunkById(200, function ($people) use ($today, $lookahead, &$created): void {
                foreach ($people as $person) {
                    if ($this->createForPerson($person, $today, $lookahead)) {
                        $created++;
                    }
                }
            });

        $this->info("Birthday check complete: {$created} reminder(s) created.");

        return self::SUCCESS;
    }

    private function createForPerson(Person $person, Carbon $today, int $lookahead): bool
    {
        $birthday = $person->birthday;
        if ($birthday === null) {
            return false;
        }

        $thisYear = $today->copy()->setDate($today->year, (int) $birthday->format('n'), (int) $birthday->format('j'));
        $target = $thisYear->isBefore($today) ? $thisYear->copy()->addYear() : $thisYear;

        $daysUntil = $today->diffInDays($target, false);
        if ($daysUntil < 0 || $daysUntil > $lookahead) {
            return false;
        }

        $year = $target->year;
        $title = "Birthday: {$person->display_name}";
        $idempotencyMarker = "[bday:{$person->id}:{$year}]";

        $existing = Reminder::query()
            ->where('user_id', $person->user_id)
            ->where('title', $title)
            ->where('body', 'like', '%'.$idempotencyMarker.'%')
            ->exists();

        if ($existing) {
            return false;
        }

        $remindAt = $target->copy()->setTime(9, 0);
        $channel = $person->user?->default_reminder_channel ?: 'email';

        Reminder::create([
            'user_id' => $person->user_id,
            'context_id' => $person->context_id,
            'title' => $title,
            'body' => "It's {$person->display_name}'s birthday on {$target->toFormattedDateString()}. {$idempotencyMarker}",
            'remind_at' => $remindAt,
            'channel' => $channel,
        ]);

        return true;
    }
}
