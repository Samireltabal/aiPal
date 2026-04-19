<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\Memory\MemoryExtractorAgent;
use App\Models\Memory;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ExtractMemoriesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $userId,
        public readonly string $conversationId,
    ) {}

    public function handle(EmbeddingService $embeddings): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversationId)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get(['role', 'content']);

        if ($messages->isEmpty()) {
            return;
        }

        $transcript = $messages->map(fn ($m) => ucfirst($m->role).': '.$m->content)->join("\n");

        $response = (new MemoryExtractorAgent)->prompt($transcript);

        $facts = $response['facts'] ?? [];

        foreach ($facts as $fact) {
            if (! is_string($fact) || trim($fact) === '') {
                continue;
            }

            $alreadyKnown = Memory::query()
                ->where('user_id', $user->id)
                ->where('content', $fact)
                ->exists();

            if (! $alreadyKnown) {
                $embeddings->storeMemory($user, $fact, source: 'conversation:'.$this->conversationId);
            }
        }
    }
}
