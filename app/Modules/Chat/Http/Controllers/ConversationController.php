<?php

declare(strict_types=1);

namespace App\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * List the authenticated user's conversations, most recent first.
     */
    public function index(Request $request): JsonResponse
    {
        $conversations = DB::table('agent_conversations')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->paginate(20);

        return response()->json($conversations);
    }

    public function messages(Request $request, string $id): JsonResponse
    {
        $conversation = DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $conversation) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $id)
            ->orderBy('created_at')
            ->get(['role', 'content', 'created_at']);

        return response()->json(['data' => $messages]);
    }

    /**
     * Search conversations by title or message content.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '' || strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $safeQuery = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
        $like = "%{$safeQuery}%";
        $op = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $conversations = DB::table('agent_conversations as ac')
            ->where('ac.user_id', $request->user()->id)
            ->where(function ($q) use ($like, $op): void {
                $q->where('ac.title', $op, $like)
                    ->orWhereExists(function ($sub) use ($like, $op): void {
                        $sub->select(DB::raw(1))
                            ->from('agent_conversation_messages as m')
                            ->whereColumn('m.conversation_id', 'ac.id')
                            ->where('m.content', $op, $like);
                    });
            })
            ->orderByDesc('ac.updated_at')
            ->limit(20)
            ->get(['ac.id', 'ac.title']);

        return response()->json(['data' => $conversations]);
    }

    /**
     * Delete a conversation and all its messages.
     */
    public function destroy(Request $request, string $id): Response
    {
        $deleted = DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if (! $deleted) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->noContent();
    }
}
