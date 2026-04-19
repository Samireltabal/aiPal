<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchKnowledgeBase extends AiTool
{
    public function __construct(
        private readonly User $user,
        private readonly EmbeddingService $embeddings,
    ) {}

    public static function toolName(): string
    {
        return 'search_knowledge_base';
    }

    public static function toolLabel(): string
    {
        return 'Search Knowledge Base';
    }

    public static function toolCategory(): string
    {
        return 'knowledge';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Search the user\'s uploaded knowledge base documents for relevant information. Use this when the user asks about something that may be covered in their documents or uploaded files.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The search query to find relevant content in the knowledge base.')
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $query = $request['query'];

        $hasDocuments = Document::query()
            ->where('user_id', $this->user->id)
            ->where('status', 'ready')
            ->exists();

        if (! $hasDocuments) {
            return 'No relevant documents found in the knowledge base for this query.';
        }

        $embedding = $this->embeddings->embedText($query);

        $chunks = DocumentChunk::query()
            ->whereHas('document', fn ($q) => $q->where('user_id', $this->user->id)->where('status', 'ready'))
            ->whereVectorSimilarTo('embedding', $embedding, minSimilarity: 0.0)
            ->with('document:id,name')
            ->limit(5)
            ->get();

        if ($chunks->isEmpty()) {
            return 'No relevant documents found in the knowledge base for this query.';
        }

        return $chunks
            ->map(fn (DocumentChunk $chunk) => "Source: {$chunk->document->name}\n{$chunk->content}")
            ->join("\n\n---\n\n");
    }
}
