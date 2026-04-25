<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read-only access to Microsoft Outlook mail via Graph API. Send is
 * intentionally unsupported per the integration plan.
 */
class MicrosoftGraphMailService
{
    private const GRAPH_BASE = 'https://graph.microsoft.com/v1.0';

    public function __construct(private readonly MicrosoftConnectionAuth $auth) {}

    /**
     * @return array<int, array{id: string, subject: string, from: string, received: string, snippet: string, isUnread: bool}>
     */
    public function listInbox(User $user, int $maxResults = 10): array
    {
        $token = $this->resolveToken($user);

        $response = Http::withToken($token)
            ->get(self::GRAPH_BASE.'/me/mailFolders/inbox/messages', [
                '$top' => $maxResults,
                '$orderby' => 'receivedDateTime DESC',
                '$select' => 'id,subject,from,receivedDateTime,bodyPreview,isRead',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Microsoft Graph error: '.$response->status().' '.$response->body());
        }

        $messages = $response->json('value') ?? [];

        return array_map(static fn (array $msg): array => [
            'id' => (string) $msg['id'],
            'subject' => $msg['subject'] ?: '(No subject)',
            'from' => self::formatAddress($msg['from'] ?? null),
            'received' => $msg['receivedDateTime'] ?? '',
            'snippet' => $msg['bodyPreview'] ?? '',
            'isUnread' => ! ($msg['isRead'] ?? true),
        ], $messages);
    }

    /**
     * @return array{id: string, subject: string, from: string, to: string, date: string, body: string}
     */
    public function getMessage(User $user, string $messageId): array
    {
        $token = $this->resolveToken($user);

        $response = Http::withToken($token)
            ->get(self::GRAPH_BASE.'/me/messages/'.urlencode($messageId), [
                '$select' => 'id,subject,from,toRecipients,receivedDateTime,body',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Microsoft Graph error: '.$response->status().' '.$response->body());
        }

        $msg = $response->json() ?? [];
        $body = $msg['body']['content'] ?? '';
        if (($msg['body']['contentType'] ?? 'text') === 'html') {
            $body = trim((string) preg_replace('/\s+/', ' ', strip_tags($body)));
        }

        return [
            'id' => (string) ($msg['id'] ?? $messageId),
            'subject' => $msg['subject'] ?: '(No subject)',
            'from' => self::formatAddress($msg['from'] ?? null),
            'to' => collect($msg['toRecipients'] ?? [])
                ->map(fn (array $r) => self::formatAddress($r))
                ->implode(', '),
            'date' => $msg['receivedDateTime'] ?? '',
            'body' => $body,
        ];
    }

    /**
     * Search across all folders.
     *
     * @return array<int, array{id: string, subject: string, from: string, received: string, snippet: string, isUnread: bool}>
     */
    public function search(User $user, string $query, int $maxResults = 10): array
    {
        $token = $this->resolveToken($user);

        $response = Http::withToken($token)
            ->get(self::GRAPH_BASE.'/me/messages', [
                '$search' => '"'.$query.'"',
                '$top' => $maxResults,
                '$select' => 'id,subject,from,receivedDateTime,bodyPreview,isRead',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Microsoft Graph error: '.$response->status().' '.$response->body());
        }

        $messages = $response->json('value') ?? [];

        return array_map(static fn (array $msg): array => [
            'id' => (string) $msg['id'],
            'subject' => $msg['subject'] ?: '(No subject)',
            'from' => self::formatAddress($msg['from'] ?? null),
            'received' => $msg['receivedDateTime'] ?? '',
            'snippet' => $msg['bodyPreview'] ?? '',
            'isUnread' => ! ($msg['isRead'] ?? true),
        ], $messages);
    }

    private function resolveToken(User $user): string
    {
        $connection = $this->auth->pickConnection($user);

        if ($connection === null) {
            throw new RuntimeException('No Microsoft account connected for this user.');
        }

        return $this->auth->accessTokenFor($connection);
    }

    /**
     * @param  array<string, mixed>|null  $address
     */
    private static function formatAddress(?array $address): string
    {
        if ($address === null) {
            return 'Unknown';
        }

        $email = $address['emailAddress']['address'] ?? '';
        $name = $address['emailAddress']['name'] ?? '';

        if ($name && $email) {
            return "{$name} <{$email}>";
        }

        return $email ?: ($name ?: 'Unknown');
    }
}
