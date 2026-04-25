<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\ResolvesContextHint;
use App\Models\User;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class OutlookTool extends AiTool
{
    use ResolvesContextHint;

    public function __construct(
        private readonly User $user,
        private readonly MicrosoftGraphMailService $mailService,
    ) {}

    public static function toolName(): string
    {
        return 'outlook';
    }

    public static function toolLabel(): string
    {
        return 'Outlook';
    }

    public static function toolCategory(): string
    {
        return 'integrations';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Read the user\'s Microsoft Outlook mailbox (work / personal Microsoft 365). Use for: listing recent inbox emails, reading a specific message, or searching the mailbox. This tool is read-only — sending mail is intentionally not supported.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->description('Action to perform: "list" (recent inbox), "read" (full message body), "search" (full-text search across folders).')
                ->enum(['list', 'read', 'search'])
                ->required(),
            'message_id' => $schema->string()
                ->description('Required for "read". The Outlook message ID returned by "list" or "search".')
                ->nullable()
                ->required(),
            'query' => $schema->string()
                ->description('Required for "search". Free-text query (e.g. "invoice from Acme").')
                ->nullable()
                ->required(),
            'max_results' => $schema->integer()
                ->description('For "list" / "search": number of messages to return (1–20). Defaults to 10.')
                ->min(1)
                ->max(20)
                ->nullable()
                ->required(),
            ...$this->contextSchema($schema),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasMicrosoftConnected()) {
            return 'Outlook is not connected. Please go to Settings and connect your Microsoft account.';
        }

        return $this->withRequestedContext($request, function () use ($request) {
            try {
                return match ($request['action']) {
                    'list' => $this->listInbox($request),
                    'read' => $this->readMessage($request),
                    'search' => $this->searchMailbox($request),
                    default => 'Unknown action.',
                };
            } catch (\Throwable $e) {
                return 'Outlook is unavailable: '.$e->getMessage();
            }
        });
    }

    private function listInbox(Request $request): string
    {
        $messages = $this->mailService->listInbox($this->user, $request['max_results'] ?? 10);

        if (empty($messages)) {
            return 'Your Outlook inbox is empty.';
        }

        return 'Recent Outlook messages:'."\n".$this->formatList($messages);
    }

    private function readMessage(Request $request): string
    {
        $id = $request['message_id'] ?? null;
        if (! $id) {
            return 'Please provide a message_id to read.';
        }

        $msg = $this->mailService->getMessage($this->user, $id);
        $body = mb_substr($msg['body'], 0, 3000);

        return implode("\n", [
            "From: {$msg['from']}",
            "To: {$msg['to']}",
            "Date: {$msg['date']}",
            "Subject: {$msg['subject']}",
            '',
            $body,
        ]);
    }

    private function searchMailbox(Request $request): string
    {
        $query = $request['query'] ?? null;
        if (! $query) {
            return 'Please provide a query to search for.';
        }

        $messages = $this->mailService->search(
            $this->user,
            (string) $query,
            $request['max_results'] ?? 10,
        );

        if (empty($messages)) {
            return 'No Outlook messages match "'.$query.'".';
        }

        return 'Outlook search results for "'.$query.'":'."\n".$this->formatList($messages);
    }

    /**
     * @param  array<int, array{id: string, subject: string, from: string, received: string, snippet: string, isUnread: bool}>  $messages
     */
    private function formatList(array $messages): string
    {
        $lines = array_map(static function (array $msg): string {
            $unread = $msg['isUnread'] ? '● ' : '';

            return $unread."[{$msg['id']}] From: {$msg['from']} | Subject: {$msg['subject']} | {$msg['snippet']}";
        }, $messages);

        return implode("\n", $lines);
    }
}
