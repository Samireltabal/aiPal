<?php

declare(strict_types=1);

namespace App\Modules\Chat\Http\Controllers;

use App\Ai\Agents\Chat\ChatAgent;
use App\Http\Controllers\Controller;
use App\Modules\Chat\Http\Requests\ChatRequest;
use Illuminate\Http\Response;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
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
        $agent = new ChatAgent;

        if ($persona = $user->persona) {
            $agent->withSystemPrompt($persona->system_prompt);
        }

        if ($conversationId = $request->input('conversation_id')) {
            $agent->continue($conversationId, as: $user);
        } else {
            $agent->forUser($user);
        }

        $provider = $request->input('provider') ?: null;
        $model = $request->input('model') ?: null;
        $message = $request->input('message');

        $stream = $agent->stream($message, provider: $provider, model: $model);

        return response()->stream(function () use ($agent, $stream): void {
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

                echo 'event: done'."\n";
                echo 'data: '.json_encode(['conversation_id' => $agent->currentConversation()])."\n\n";
            } catch (\Throwable $e) {
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
