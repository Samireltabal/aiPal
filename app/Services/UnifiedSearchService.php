<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UnifiedSearchService
{
    public function __construct(private readonly EmbeddingService $embedder) {}

    /**
     * @return array<int, array{type: string, id: int|string, title: string, excerpt: string, url: string}>
     */
    public function search(User $user, string $query, int $limit = 12): array
    {
        if (trim($query) === '') {
            return [];
        }

        $embedding = $this->embedder->embedText($query);
        $vector = '['.implode(',', $embedding).']';

        $results = [];

        // Memories
        $memories = DB::select('
            SELECT id, content, embedding <=> ?::vector AS distance
            FROM memories
            WHERE user_id = ?
            ORDER BY distance ASC
            LIMIT 4
        ', [$vector, $user->id]);

        foreach ($memories as $row) {
            $results[] = [
                'type' => 'memory',
                'id' => $row->id,
                'title' => str($row->content)->limit(60)->toString(),
                'excerpt' => str($row->content)->limit(120)->toString(),
                'url' => '/memories',
                'distance' => (float) $row->distance,
            ];
        }

        // Notes
        $notes = DB::select('
            SELECT n.id, n.title, n.content, n.embedding <=> ?::vector AS distance
            FROM notes n
            WHERE n.user_id = ?
            ORDER BY distance ASC
            LIMIT 4
        ', [$vector, $user->id]);

        foreach ($notes as $row) {
            $results[] = [
                'type' => 'note',
                'id' => $row->id,
                'title' => $row->title ?: str($row->content)->limit(60)->toString(),
                'excerpt' => str($row->content)->limit(120)->toString(),
                'url' => '/productivity',
                'distance' => (float) $row->distance,
            ];
        }

        // Tasks — keyword match (no embeddings); kept separate, appended after semantic results
        $safeQuery = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
        $tasks = $user->tasks()
            ->where(fn ($q) => $q
                ->where('title', 'ilike', "%{$safeQuery}%")
                ->orWhere('description', 'ilike', "%{$safeQuery}%")
            )
            ->limit(4)
            ->get(['id', 'title', 'priority', 'completed_at']);

        $taskResults = [];
        foreach ($tasks as $task) {
            $taskResults[] = [
                'type' => 'task',
                'id' => $task->id,
                'title' => $task->title,
                'excerpt' => $task->completed_at ? 'Completed' : ucfirst($task->priority ?? 'normal').' priority',
                'url' => '/productivity',
                'distance' => null,
            ];
        }

        // Documents — search by chunk embedding
        $docs = DB::select('
            SELECT DISTINCT ON (d.id) d.id, d.name,
                dc.content AS chunk_content,
                dc.embedding <=> ?::vector AS distance
            FROM document_chunks dc
            JOIN documents d ON d.id = dc.document_id
            WHERE d.user_id = ?
            ORDER BY d.id, distance ASC
            LIMIT 4
        ', [$vector, $user->id]);

        foreach ($docs as $row) {
            $results[] = [
                'type' => 'document',
                'id' => $row->id,
                'title' => $row->name,
                'excerpt' => str($row->chunk_content)->limit(120)->toString(),
                'url' => '/documents',
                'distance' => (float) $row->distance,
            ];
        }

        // Sort semantic results by distance, then append keyword task matches
        usort($results, fn ($a, $b) => $a['distance'] <=> $b['distance']);
        $results = array_merge($results, $taskResults);

        return array_slice($results, 0, $limit);
    }
}
