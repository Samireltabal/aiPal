<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Memory;
use App\Models\User;
use Laravel\Ai\Embeddings;

class EmbeddingService
{
    public function embedText(string $text): array
    {
        $provider = config('ai.default_for_embeddings');
        $model = config('ai.embedding_model') ?: null;
        $dimensions = (int) config('ai.embedding_dimensions', 1536);

        $response = Embeddings::for([$text])
            ->dimensions($dimensions)
            ->cache()
            ->generate($provider, $model);

        return $response->embeddings[0];
    }

    public function storeMemory(User $user, string $content, ?string $source = null): Memory
    {
        $embedding = $this->embedText($content);

        return Memory::create([
            'user_id' => $user->id,
            'content' => $content,
            'embedding' => $embedding,
            'source' => $source,
        ]);
    }
}
