<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Memory;
use App\Models\User;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;

class EmbeddingService
{
    public function embedText(string $text): array
    {
        $response = Embeddings::for([$text])
            ->dimensions(1536)
            ->cache()
            ->generate(Lab::OpenAI, 'text-embedding-3-small');

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
