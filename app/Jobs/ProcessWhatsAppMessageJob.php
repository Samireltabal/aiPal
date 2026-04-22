<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\Chat\ChatAgent;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Services\Workflow\WorkflowMessageMatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Ai\Transcription;

class ProcessWhatsAppMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly int $userId,
        public readonly string $phone,
        public readonly ?string $text,
        public readonly ?string $audioMediaId = null,
    ) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $persona = $user->persona;

        $text = $this->resolveText($whatsApp);

        if ($text === null) {
            return;
        }

        $matcher = app(WorkflowMessageMatcher::class);
        $workflow = $matcher->match($user, 'whatsapp', $text);

        if ($workflow !== null) {
            $whatsApp->send($this->phone, "Running workflow \"{$workflow->name}\"...");

            RunWorkflowJob::dispatch($workflow->id, 'message', [
                'channel' => 'whatsapp',
                'text' => $text,
                'from' => $this->phone,
            ]);

            return;
        }

        $agent = (new ChatAgent)
            ->withUser($user)
            ->withSystemPrompt($persona?->system_prompt ?? 'You are a helpful personal assistant. Be concise, accurate, and friendly.');

        if ($user->whatsapp_conversation_id) {
            $response = $agent->continue($user->whatsapp_conversation_id, as: $user)->prompt($text);
        } else {
            $response = $agent->forUser($user)->prompt($text);
            $user->update(['whatsapp_conversation_id' => $response->conversationId]);
        }

        $reply = (string) $response;

        $whatsApp->send($this->phone, $reply);
    }

    private function resolveText(WhatsAppService $whatsApp): ?string
    {
        if ($this->text !== null) {
            return $this->text;
        }

        if ($this->audioMediaId === null) {
            return null;
        }

        $tmpPath = $whatsApp->downloadAudio($this->audioMediaId);

        if ($tmpPath === null) {
            return null;
        }

        try {
            return (string) Transcription::fromPath($tmpPath)->generate();
        } finally {
            @unlink($tmpPath);
        }
    }
}
