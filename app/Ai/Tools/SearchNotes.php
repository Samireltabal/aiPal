<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Note;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchNotes extends AiTool
{
    public function __construct(
        private readonly User $user,
        private readonly EmbeddingService $embeddings,
    ) {}

    public static function toolName(): string
    {
        return 'search_notes';
    }

    public static function toolLabel(): string
    {
        return 'Search Notes';
    }

    public static function toolCategory(): string
    {
        return 'productivity';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Search the user\'s notes by semantic similarity. Use when the user asks to find, retrieve, or look up a note.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Search query to find relevant notes.')
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $query = $request['query'];

        $hasNotes = Note::query()
            ->where('user_id', $this->user->id)
            ->exists();

        if (! $hasNotes) {
            return 'No notes found.';
        }

        $embedding = $this->embeddings->embedText($query);

        $notes = Note::query()
            ->where('user_id', $this->user->id)
            ->whereNotNull('embedding')
            ->whereVectorSimilarTo('embedding', $embedding, minSimilarity: 0.0)
            ->limit(5)
            ->get();

        if ($notes->isEmpty()) {
            return 'No relevant notes found for that query.';
        }

        return $notes->map(function (Note $note) {
            $header = $note->title ? "**{$note->title}**" : '*(untitled)*';

            return "{$header}\n{$note->content}\n_(saved {$note->created_at?->diffForHumans()})_";
        })->join("\n\n---\n\n");
    }
}
