<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read/write access to Microsoft Outlook Calendar via Graph API.
 */
class MicrosoftGraphCalendarService
{
    private const GRAPH_BASE = 'https://graph.microsoft.com/v1.0';

    public function __construct(private readonly MicrosoftConnectionAuth $auth) {}

    /**
     * @return array<int, array{id: string, summary: string, start: string, end: string, location: ?string, organizer: ?string, isAllDay: bool}>
     */
    public function listEvents(User $user, Carbon $from, Carbon $to): array
    {
        $token = $this->resolveToken($user);

        $response = Http::withToken($token)
            ->withHeaders([
                'Prefer' => 'outlook.timezone="UTC"',
            ])
            ->get(self::GRAPH_BASE.'/me/calendarview', [
                'startDateTime' => $from->toIso8601String(),
                'endDateTime' => $to->toIso8601String(),
                '$orderby' => 'start/dateTime',
                '$top' => 50,
                '$select' => 'id,subject,start,end,location,organizer,isAllDay',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Microsoft Graph error: '.$response->status().' '.$response->body());
        }

        return array_map(static fn (array $event): array => [
            'id' => (string) $event['id'],
            'summary' => $event['subject'] ?: '(No subject)',
            'start' => $event['start']['dateTime'] ?? '',
            'end' => $event['end']['dateTime'] ?? '',
            'location' => $event['location']['displayName'] ?? null,
            'organizer' => $event['organizer']['emailAddress']['address'] ?? null,
            'isAllDay' => (bool) ($event['isAllDay'] ?? false),
        ], $response->json('value') ?? []);
    }

    /**
     * Create a new event on the user's primary calendar.
     *
     * @param  array<int, string>|null  $attendees  email addresses
     * @return array{id: string, web_link: ?string}
     */
    public function createEvent(
        User $user,
        string $subject,
        Carbon $start,
        Carbon $end,
        ?string $location = null,
        ?string $body = null,
        ?array $attendees = null,
    ): array {
        $token = $this->resolveToken($user);

        $payload = [
            'subject' => $subject,
            'start' => [
                'dateTime' => $start->toIso8601String(),
                'timeZone' => 'UTC',
            ],
            'end' => [
                'dateTime' => $end->toIso8601String(),
                'timeZone' => 'UTC',
            ],
        ];

        if ($location !== null && $location !== '') {
            $payload['location'] = ['displayName' => $location];
        }

        if ($body !== null && $body !== '') {
            $payload['body'] = ['contentType' => 'text', 'content' => $body];
        }

        if ($attendees !== null && $attendees !== []) {
            $payload['attendees'] = array_map(static fn (string $email): array => [
                'emailAddress' => ['address' => $email],
                'type' => 'required',
            ], $attendees);
        }

        $response = Http::withToken($token)
            ->asJson()
            ->post(self::GRAPH_BASE.'/me/events', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Microsoft Graph error creating event: '.$response->status().' '.$response->body());
        }

        $event = $response->json() ?? [];

        return [
            'id' => (string) ($event['id'] ?? ''),
            'web_link' => $event['webLink'] ?? null,
        ];
    }

    private function resolveToken(User $user): string
    {
        $connection = $this->auth->pickConnection($user);

        if ($connection === null) {
            throw new RuntimeException('No Microsoft account connected for this user.');
        }

        return $this->auth->accessTokenFor($connection);
    }
}
