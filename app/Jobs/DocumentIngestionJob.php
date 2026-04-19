<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentChunker;
use App\Services\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentIngestionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public readonly int $documentId) {}

    public function handle(DocumentChunker $chunker, EmbeddingService $embeddings): void
    {
        $document = Document::find($this->documentId);

        if ($document === null) {
            return;
        }

        $document->markProcessing();

        try {
            $content = Storage::disk('local')->get("documents/{$document->user_id}/{$document->file_name}");

            if ($content === null) {
                throw new \RuntimeException("Stored file not found: {$document->file_name}");
            }

            $chunks = $chunker->chunk($content);

            // Remove stale chunks in case of re-ingestion
            $document->chunks()->delete();

            foreach ($chunks as $index => $chunkText) {
                $embedding = $embeddings->embedText($chunkText);

                $document->chunks()->create([
                    'content' => $chunkText,
                    'chunk_index' => $index,
                    'embedding' => $embedding,
                ]);
            }

            $document->markReady();
        } catch (Throwable $e) {
            $document->markFailed($e->getMessage());
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $document = Document::find($this->documentId);
        $document?->markFailed($e->getMessage());
    }
}
