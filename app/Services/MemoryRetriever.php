<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Memory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MemoryRetriever
{
    public function __construct(private readonly EmbeddingService $embeddings) {}

    /** @return Collection<int, Memory> */
    public function retrieve(User $user, string $query, int $limit = 8): Collection
    {
        try {
            $embedding = $this->embeddings->embedText($query);
        } catch (\Throwable $e) {
            Log::warning('MemoryRetriever: embedding failed, skipping memory context', [
                'error' => $e->getMessage(),
                'provider' => config('ai.default_for_embeddings'),
            ]);

            return collect();
        }

        return Memory::query()
            ->where('user_id', $user->id)
            ->whereVectorSimilarTo('embedding', $embedding, minSimilarity: 0.0)
            ->limit($limit)
            ->get();
    }

    public function buildContextBlock(User $user, string $query): string
    {
        $memories = $this->retrieve($user, $query);

        if ($memories->isEmpty()) {
            return '';
        }

        $list = $memories->map(fn (Memory $m) => '- '.$m->content)->join("\n");

        return "\n\n## What you know about this user\n".$list;
    }
}
