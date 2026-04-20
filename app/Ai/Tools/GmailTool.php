<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\GmailService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class GmailTool extends AiTool
{
    public function __construct(
        private readonly User $user,
        private readonly GmailService $gmailService,
    ) {}

    public static function toolName(): string
    {
        return 'gmail';
    }

    public static function toolLabel(): string
    {
        return 'Gmail';
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
        return 'Interact with the user\'s Gmail. Use for: listing recent inbox emails, reading a specific email, or drafting a reply. Always list first to get message IDs before reading or replying.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->description('Action to perform: "list" (recent inbox), "read" (full email body), "draft" (create a draft reply).')
                ->enum(['list', 'read', 'draft'])
                ->required(),
            'message_id' => $schema->string()
                ->description('Required for "read" and "draft" actions. The Gmail message ID.')
                ->nullable()
                ->required(),
            'max_results' => $schema->integer()
                ->description('For "list": number of emails to return (1–20). Defaults to 10.')
                ->min(1)
                ->max(20)
                ->nullable()
                ->required(),
            'reply_body' => $schema->string()
                ->description('For "draft": the body text of the reply to draft.')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasGoogleConnected()) {
            return 'Gmail is not connected. Please go to Settings and connect your Google account.';
        }

        try {
            return match ($request['action']) {
                'list' => $this->listEmails($request),
                'read' => $this->readEmail($request),
                'draft' => $this->draftReply($request),
                default => 'Unknown action.',
            };
        } catch (\Throwable $e) {
            return 'Gmail is unavailable: '.$e->getMessage();
        }
    }

    private function listEmails(Request $request): string
    {
        $emails = $this->gmailService->listInbox($this->user, $request['max_results'] ?? 10);

        if (empty($emails)) {
            return 'Your inbox is empty.';
        }

        $lines = array_map(function (array $email): string {
            return "• [{$email['id']}] From: {$email['from']} | Subject: {$email['subject']} | {$email['snippet']}";
        }, $emails);

        return 'Recent inbox emails:'."\n".implode("\n", $lines);
    }

    private function readEmail(Request $request): string
    {
        $id = $request['message_id'] ?? null;
        if (! $id) {
            return 'Please provide a message_id to read.';
        }

        $email = $this->gmailService->getMessage($this->user, $id);

        $body = mb_substr($email['body'], 0, 3000);

        return implode("\n", [
            "From: {$email['from']}",
            "To: {$email['to']}",
            "Date: {$email['date']}",
            "Subject: {$email['subject']}",
            '',
            $body,
        ]);
    }

    private function draftReply(Request $request): string
    {
        $id = $request['message_id'] ?? null;
        $replyBody = $request['reply_body'] ?? null;

        if (! $id) {
            return 'Please provide a message_id to reply to.';
        }

        if (! $replyBody) {
            return 'Please provide a reply_body for the draft.';
        }

        $original = $this->gmailService->getMessage($this->user, $id);
        $draftId = $this->gmailService->createDraft(
            user: $this->user,
            to: $original['from'],
            subject: 'Re: '.$original['subject'],
            body: $replyBody,
        );

        return "Draft created (ID: {$draftId}). It is saved in your Gmail Drafts folder — review and send it from Gmail when ready.";
    }
}
