<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\User;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    public function __construct(private readonly GoogleConnectionAuth $auth) {}

    /**
     * @return array<int, array{id: string, summary: string, start: string, end: string, location: ?string, description: ?string}>
     */
    public function listTodayEvents(User $user): array
    {
        return $this->listEvents($user, Carbon::today(), Carbon::today()->endOfDay());
    }

    /**
     * @return array<int, array{id: string, summary: string, start: string, end: string, location: ?string, description: ?string}>
     */
    public function listUpcomingEvents(User $user, int $days = 7): array
    {
        return $this->listEvents($user, Carbon::now(), Carbon::now()->addDays($days)->endOfDay());
    }

    /**
     * @return array<int, array{id: string, summary: string, start: string, end: string, location: ?string, description: ?string}>
     */
    public function listEvents(User $user, Carbon $from, Carbon $to): array
    {
        $connection = $this->auth->pickConnection($user);
        if ($connection === null) {
            return [];
        }

        return $this->listEventsForConnection($connection, $from, $to);
    }

    /**
     * @return array<int, array{id: string, summary: string, start: string, end: string, location: ?string, description: ?string}>
     */
    public function listEventsForConnection(Connection $connection, Carbon $from, Carbon $to): array
    {
        $client = $this->auth->authenticatedClient($connection);
        $service = new Calendar($client);

        $events = $service->events->listEvents('primary', [
            'timeMin' => $from->toRfc3339String(),
            'timeMax' => $to->toRfc3339String(),
            'singleEvents' => true,
            'orderBy' => 'startTime',
            'maxResults' => 50,
        ]);

        return collect($events->getItems())
            ->map(fn (Event $event) => [
                'id' => $event->getId(),
                'summary' => $event->getSummary() ?? '(No title)',
                'start' => $event->getStart()->getDateTime() ?? $event->getStart()->getDate(),
                'end' => $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate(),
                'location' => $event->getLocation(),
                'description' => $event->getDescription(),
            ])
            ->values()
            ->all();
    }
}
