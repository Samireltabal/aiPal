<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoogleToken;
use App\Models\User;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Draft;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\MessagePart;
use Illuminate\Support\Carbon;
use RuntimeException;

class GmailService
{
    public function __construct(private readonly GoogleClientFactory $clientFactory) {}

    /**
     * @return array<int, array{id: string, threadId: string, subject: string, from: string, date: string, snippet: string, isUnread: bool}>
     */
    public function listInbox(User $user, int $maxResults = 10): array
    {
        $client = $this->authenticatedClient($user);
        $gmail = new Gmail($client);

        $messages = $gmail->users_messages->listUsersMessages('me', [
            'labelIds' => ['INBOX'],
            'maxResults' => $maxResults,
        ]);

        $items = $messages->getMessages() ?? [];

        return array_map(function ($msg) use ($gmail): array {
            $full = $gmail->users_messages->get('me', $msg->getId(), ['format' => 'metadata', 'metadataHeaders' => ['Subject', 'From', 'Date']]);
            $headers = collect($full->getPayload()->getHeaders())
                ->mapWithKeys(fn ($h) => [strtolower($h->getName()) => $h->getValue()])
                ->all();

            $labelIds = $full->getLabelIds() ?? [];

            return [
                'id' => $full->getId(),
                'threadId' => $full->getThreadId(),
                'subject' => $headers['subject'] ?? '(No subject)',
                'from' => $headers['from'] ?? 'Unknown',
                'date' => $headers['date'] ?? '',
                'snippet' => $full->getSnippet() ?? '',
                'isUnread' => in_array('UNREAD', $labelIds, true),
            ];
        }, $items);
    }

    /**
     * @return array{id: string, subject: string, from: string, to: string, date: string, body: string}
     */
    public function getMessage(User $user, string $messageId): array
    {
        $client = $this->authenticatedClient($user);
        $gmail = new Gmail($client);

        $msg = $gmail->users_messages->get('me', $messageId, ['format' => 'full']);
        $payload = $msg->getPayload();
        $headers = collect($payload->getHeaders())
            ->mapWithKeys(fn ($h) => [strtolower($h->getName()) => $h->getValue()])
            ->all();

        $body = $this->extractBody($payload);

        return [
            'id' => $msg->getId(),
            'subject' => $headers['subject'] ?? '(No subject)',
            'from' => $headers['from'] ?? 'Unknown',
            'to' => $headers['to'] ?? '',
            'date' => $headers['date'] ?? '',
            'body' => $body,
        ];
    }

    public function createDraft(User $user, string $to, string $subject, string $body): string
    {
        $client = $this->authenticatedClient($user);
        $gmail = new Gmail($client);

        $rawMessage = implode("\r\n", [
            "To: {$to}",
            "Subject: {$subject}",
            'Content-Type: text/plain; charset=UTF-8',
            '',
            $body,
        ]);

        $encoded = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $message = new Message;
        $message->setRaw($encoded);

        $draft = new Draft;
        $draft->setMessage($message);

        $created = $gmail->users_drafts->create('me', $draft);

        return $created->getId();
    }

    private function authenticatedClient(User $user): Client
    {
        $token = $this->refreshedToken($user);

        $client = $this->clientFactory->make();
        $client->setAccessToken($token->toGoogleArray());

        return $client;
    }

    private function refreshedToken(User $user): GoogleToken
    {
        $token = $user->googleToken;

        if ($token === null) {
            throw new RuntimeException('Google account is not connected. Please connect in Settings.');
        }

        if (! $token->hasScope(Gmail::GMAIL_READONLY)) {
            throw new RuntimeException('Gmail permission not granted. Please reconnect your Google account in Settings to allow email access.');
        }

        if ($token->isExpired() && $token->refresh_token) {
            $client = $this->clientFactory->make();
            $client->setAccessToken($token->toGoogleArray());
            $new = $client->fetchAccessTokenWithRefreshToken($token->refresh_token);

            if (isset($new['access_token'])) {
                $token->update([
                    'access_token' => $new['access_token'],
                    'expires_at' => isset($new['expires_in'])
                        ? Carbon::now()->addSeconds($new['expires_in'])
                        : null,
                ]);
            }
        }

        return $token->refresh();
    }

    private function extractBody(MessagePart $payload): string
    {
        $mimeType = $payload->getMimeType() ?? '';

        if (in_array($mimeType, ['text/plain', 'text/html'], true)) {
            $data = $payload->getBody()?->getData();
            if ($data) {
                $decoded = base64_decode(strtr($data, '-_', '+/'));

                return $mimeType === 'text/html' ? strip_tags($decoded) : $decoded;
            }
        }

        foreach ($payload->getParts() ?? [] as $part) {
            $result = $this->extractBody($part);
            if ($result !== '') {
                return $result;
            }
        }

        return '';
    }
}
