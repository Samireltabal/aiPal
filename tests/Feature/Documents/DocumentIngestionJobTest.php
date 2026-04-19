<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Jobs\DocumentIngestionJob;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentChunker;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentIngestionJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeDocument(string $content = "Hello world.\n\nThis is a test document."): Document
    {
        $user = User::factory()->create();
        Storage::fake('local');

        $fileName = 'doc_test.txt';
        Storage::disk('local')->put("documents/{$user->id}/{$fileName}", $content);

        return Document::create([
            'user_id' => $user->id,
            'name' => 'Test Document',
            'file_name' => $fileName,
            'mime_type' => 'text/plain',
            'size' => strlen($content),
            'status' => 'pending',
        ]);
    }

    public function test_ingestion_creates_chunks_and_marks_ready(): void
    {
        $document = $this->makeDocument("First paragraph.\n\nSecond paragraph.");

        $mockEmbeddings = $this->mock(EmbeddingService::class);
        $mockEmbeddings->shouldReceive('embedText')
            ->andReturn(array_fill(0, 1536, 0.0));

        (new DocumentIngestionJob($document->id))->handle(new DocumentChunker, $mockEmbeddings);

        $this->assertDatabaseHas('documents', ['id' => $document->id, 'status' => 'ready']);
        $this->assertGreaterThan(0, $document->chunks()->count());
    }

    public function test_ingestion_marks_failed_when_file_missing(): void
    {
        $user = User::factory()->create();
        Storage::fake('local');

        $document = Document::create([
            'user_id' => $user->id,
            'name' => 'Missing',
            'file_name' => 'does_not_exist.txt',
            'mime_type' => 'text/plain',
            'size' => 0,
            'status' => 'pending',
        ]);

        $this->expectException(\Throwable::class);

        (new DocumentIngestionJob($document->id))->handle(
            new DocumentChunker,
            $this->mock(EmbeddingService::class)
        );

        $this->assertDatabaseHas('documents', ['id' => $document->id, 'status' => 'failed']);
    }

    public function test_ingestion_does_nothing_when_document_not_found(): void
    {
        $mockEmbeddings = $this->mock(EmbeddingService::class);
        $mockEmbeddings->shouldReceive('embedText')->never();

        (new DocumentIngestionJob(999999))->handle(new DocumentChunker, $mockEmbeddings);

        $this->assertDatabaseCount('document_chunks', 0);
    }

    public function test_re_ingestion_replaces_old_chunks(): void
    {
        $document = $this->makeDocument("Paragraph one.\n\nParagraph two.");

        $mockEmbeddings = $this->mock(EmbeddingService::class);
        $mockEmbeddings->shouldReceive('embedText')
            ->andReturn(array_fill(0, 1536, 0.0));

        $job = new DocumentIngestionJob($document->id);
        $chunker = new DocumentChunker;

        $job->handle($chunker, $mockEmbeddings);
        $firstCount = $document->chunks()->count();

        $job->handle($chunker, $mockEmbeddings);
        $secondCount = $document->chunks()->count();

        $this->assertEquals($firstCount, $secondCount);
    }
}
