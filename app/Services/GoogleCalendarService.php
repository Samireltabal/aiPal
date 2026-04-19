<?php

namespace App\Services;

use App\Models\GoogleToken;
use App\Models\User;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Support\Carbon;

class GoogleCalendarService
{
    public function __construct(private readonly GoogleClientFactory $clientFactory) {}

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
        $token = $this->getRefreshedToken($user);
        if ($token === null) {
            return [];
        }

        $client = $this->clientFactory->make();
        $client->setAccessToken($token->toGoogleArray());

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

    private function getRefreshedToken(User $user): ?GoogleToken
    {
        $token = $user->googleToken;
        if ($token === null) {
            return null;
        }

        if ($token->isExpired() && $token->refresh_token) {
            $client = $this->clientFactory->make();
            $client->setAccessToken($token->toGoogleArray());
            $newToken = $client->fetchAccessTokenWithRefreshToken($token->refresh_token);

            if (isset($newToken['access_token'])) {
                $token->update([
                    'access_token' => $newToken['access_token'],
                    'expires_at' => isset($newToken['expires_in'])
                        ? Carbon::now()->addSeconds($newToken['expires_in'])
                        : null,
                ]);
            }
        }

        return $token->refresh();
    }
}
