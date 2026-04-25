<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\ResolvesContextHint;
use App\Models\User;
use App\Services\MicrosoftGraphCalendarService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Tools\Request;
use Stringable;

class OutlookCalendarTool extends AiTool
{
    use ResolvesContextHint;

    private const MAX_RECORDS_PER_TURN = 3;

    public function __construct(
        private readonly User $user,
        private readonly MicrosoftGraphCalendarService $calendarService,
    ) {}

    public static function toolName(): string
    {
        return 'outlook_calendar';
    }

    public static function toolLabel(): string
    {
        return 'Outlook Calendar';
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
        return 'Read and create events on the user\'s Microsoft Outlook Calendar (work / personal Microsoft 365). Use action="list" to query schedules, action="create" to add a new event. Always confirm with the user before creating multiple events in one turn.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->description('"list" to query events, "create" to add a new event.')
                ->enum(['list', 'create'])
                ->required(),
            'range' => $schema->string()
                ->description('For "list": "today", "tomorrow", "this_week", or "next_7_days". Defaults to "today".')
                ->enum(['today', 'tomorrow', 'this_week', 'next_7_days'])
                ->nullable()
                ->required(),
            'subject' => $schema->string()
                ->description('For "create": the event title.')
                ->nullable()
                ->required(),
            'start' => $schema->string()
                ->description('For "create": ISO 8601 start datetime (e.g. "2026-04-26T14:00:00Z" or "tomorrow 2pm").')
                ->nullable()
                ->required(),
            'end' => $schema->string()
                ->description('For "create": ISO 8601 end datetime. If omitted, defaults to start + 30 minutes.')
                ->nullable()
                ->required(),
            'location' => $schema->string()
                ->description('For "create": optional location/venue.')
                ->nullable()
                ->required(),
            'body' => $schema->string()
                ->description('For "create": optional event description / body text.')
                ->nullable()
                ->required(),
            'attendees' => $schema->array()
                ->description('For "create": optional list of attendee email addresses.')
                ->items($schema->string())
                ->nullable()
                ->required(),
            ...$this->contextSchema($schema),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasMicrosoftConnected()) {
            return 'Outlook Calendar is not connected. Please go to Settings and connect your Microsoft account.';
        }

        return $this->withRequestedContext($request, function () use ($request) {
            try {
                return match ($request['action']) {
                    'list' => $this->listEvents($request),
                    'create' => $this->createEvent($request),
                    default => 'Unknown action.',
                };
            } catch (\Throwable $e) {
                return 'Outlook Calendar is unavailable: '.$e->getMessage();
            }
        });
    }

    private function listEvents(Request $request): string
    {
        $range = $request['range'] ?? 'today';
        [$from, $to] = match ($range) {
            'tomorrow' => [Carbon::tomorrow(), Carbon::tomorrow()->endOfDay()],
            'this_week' => [Carbon::now(), Carbon::now()->endOfWeek()],
            'next_7_days' => [Carbon::now(), Carbon::now()->addDays(7)->endOfDay()],
            default => [Carbon::today(), Carbon::today()->endOfDay()],
        };

        $events = $this->calendarService->listEvents($this->user, $from, $to);

        if (empty($events)) {
            return "No Outlook events found for {$range}.";
        }

        $lines = array_map(static function (array $event): string {
            $start = Carbon::parse($event['start'])->format('g:i A');
            $end = Carbon::parse($event['end'])->format('g:i A');
            $location = $event['location'] ? " @ {$event['location']}" : '';
            $allDay = $event['isAllDay'] ? ' (all-day)' : '';

            return "• {$event['summary']} ({$start}–{$end}{$location}){$allDay}";
        }, $events);

        $label = match ($range) {
            'tomorrow' => 'Tomorrow',
            'this_week' => 'This week',
            'next_7_days' => 'Next 7 days',
            default => 'Today',
        };

        return $label."'s Outlook events:\n".implode("\n", $lines);
    }

    private function createEvent(Request $request): string
    {
        if ($this->user->createdRecordsThisTurn() >= self::MAX_RECORDS_PER_TURN) {
            return 'GUARDRAIL: You have already created '.$this->user->createdRecordsThisTurn().' records in this turn. '
                .'Stop and ask the user to confirm before creating more.';
        }

        $subject = $request['subject'] ?? null;
        $startInput = $request['start'] ?? null;

        if (! $subject) {
            return 'Please provide a subject for the event.';
        }

        if (! $startInput) {
            return 'Please provide a start datetime for the event.';
        }

        try {
            $start = Carbon::parse((string) $startInput);
        } catch (\Throwable $e) {
            return 'Could not parse start time: '.$e->getMessage();
        }

        $endInput = $request['end'] ?? null;
        try {
            $end = $endInput ? Carbon::parse((string) $endInput) : $start->copy()->addMinutes(30);
        } catch (\Throwable $e) {
            return 'Could not parse end time: '.$e->getMessage();
        }

        if ($end->lessThanOrEqualTo($start)) {
            return 'End time must be after start time.';
        }

        $event = $this->calendarService->createEvent(
            user: $this->user,
            subject: (string) $subject,
            start: $start,
            end: $end,
            location: $request['location'] ?? null,
            body: $request['body'] ?? null,
            attendees: $request['attendees'] ?? null,
        );

        $this->user->incrementCreatedRecordsThisTurn();

        $when = $start->format('D, M j · g:i A').'–'.$end->format('g:i A');

        return "Outlook event created: \"{$subject}\" ({$when}). ID: {$event['id']}";
    }
}
