<?php

declare(strict_types=1);

namespace App\Modules\Chat\Http\Controllers;

use App\Ai\Agents\Chat\ChatAgent;
use App\Http\Controllers\Controller;
use App\Jobs\ExtractMemoriesJob;
use App\Modules\Chat\Http\Requests\ChatRequest;
use App\Services\Location\MessageLocationHandler;
use App\Services\MemoryRetriever;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly MemoryRetriever $memories,
        private readonly MessageLocationHandler $locationHandler,
    ) {}

    /**
     * Stream a chat response via Server-Sent Events.
     *
     * SSE protocol:
     *   event: delta   — text chunk
     *   event: done    — stream ended, includes conversation_id
     *   event: error   — something went wrong
     */
    public function __invoke(ChatRequest $request): StreamedResponse
    {
        $user = $request->user();
        $message = (string) $request->input('message');

        // Short-circuit: if the message contains a recognizable maps URL, save the location and reply inline.
        $locationConfirmation = $this->locationHandler->handleTextMaybeContainingUrl($user, $message, 'maps_url');
        if ($locationConfirmation !== null) {
            return response()->stream(function () use ($locationConfirmation): void {
                echo 'event: delta'."\n";
                echo 'data: '.json_encode(['text' => $locationConfirmation])."\n\n";
                echo 'event: done'."\n";
                echo 'data: '.json_encode(['conversation_id' => null])."\n\n";
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        $agent = new ChatAgent;

        $systemPrompt = $user->persona?->system_prompt
            ?? 'You are a helpful personal assistant. Be concise, accurate, and friendly.';

        $memoryContext = $this->memories->buildContextBlock($user, $message);

        $kbHint = $user->documents()->where('status', 'ready')->exists()
            ? "\n\nYou have access to the user's personal knowledge base via the search_knowledge_base tool. Use it proactively when the user asks about anything that might be covered in their uploaded documents."
            : '';

        $agent->withSystemPrompt($systemPrompt.$memoryContext.$kbHint);
        $agent->withUser($user);

        if ($conversationId = $request->input('conversation_id')) {
            $agent->continue($conversationId, as: $user);
        } else {
            $agent->forUser($user);
        }

        $provider = $request->input('provider') ?: null;
        $model = $request->input('model') ?: null;

        $stream = $agent->stream($message, provider: $provider, model: $model);

        return response()->stream(function () use ($agent, $stream, $user): void {
            $flush = static function (): void {
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            try {
                foreach ($stream as $event) {
                    if ($event instanceof TextDelta) {
                        echo 'event: delta'."\n";
                        echo 'data: '.json_encode(['text' => $event->delta])."\n\n";
                        $flush();
                    }
                }

                $conversationId = $agent->currentConversation();

                echo 'event: done'."\n";
                echo 'data: '.json_encode(['conversation_id' => $conversationId])."\n\n";

                ExtractMemoriesJob::dispatch($user->id, $conversationId);
            } catch (\Throwable $e) {
                Log::error('ChatAgent stream error', ['message' => $e->getMessage()]);
                echo 'event: error'."\n";
                echo 'data: '.json_encode(['message' => $e->getMessage()])."\n\n";
            } finally {
                $flush();
            }
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
