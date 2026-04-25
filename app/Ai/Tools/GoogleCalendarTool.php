<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\ResolvesContextHint;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Tools\Request;
use Stringable;

class GoogleCalendarTool extends AiTool
{
    use ResolvesContextHint;

    public function __construct(
        private readonly User $user,
        private readonly GoogleCalendarService $calendarService,
    ) {}

    public static function toolName(): string
    {
        return 'google_calendar';
    }

    public static function toolLabel(): string
    {
        return 'Google Calendar';
    }

    public static function toolCategory(): string
    {
        return 'calendar';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Query the user\'s Google Calendar. Use when asked about today\'s schedule, upcoming events, meetings, availability, or calendar events.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'range' => $schema->string()
                ->description('Date range to query: "today", "tomorrow", "this_week", or "next_7_days". Defaults to "today".')
                ->enum(['today', 'tomorrow', 'this_week', 'next_7_days'])
                ->nullable()
                ->required(),
            ...$this->contextSchema($schema),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasGoogleConnected()) {
            return 'Google Calendar is not connected. Please go to Settings and connect your Google account.';
        }

        $range = $request['range'] ?? 'today';
        [$from, $to] = match ($range) {
            'tomorrow' => [Carbon::tomorrow(), Carbon::tomorrow()->endOfDay()],
            'this_week' => [Carbon::now(), Carbon::now()->endOfWeek()],
            'next_7_days' => [Carbon::now(), Carbon::now()->addDays(7)->endOfDay()],
            default => [Carbon::today(), Carbon::today()->endOfDay()],
        };

        $events = $this->withRequestedContext(
            $request,
            fn () => $this->calendarService->listEvents($this->user, $from, $to)
        );

        if (empty($events)) {
            return "No events found for {$range}.";
        }

        $lines = array_map(function (array $event) {
            $start = Carbon::parse($event['start'])->format('g:i A');
            $end = Carbon::parse($event['end'])->format('g:i A');
            $location = $event['location'] ? " @ {$event['location']}" : '';

            return "• {$event['summary']} ({$start}–{$end}{$location})";
        }, $events);

        $label = match ($range) {
            'tomorrow' => 'Tomorrow',
            'this_week' => 'This week',
            'next_7_days' => 'Next 7 days',
            default => 'Today',
        };

        return $label."'s events:\n".implode("\n", $lines);
    }
}
