<?php

declare(strict_types=1);

namespace Tests\Unit\Documents;

use App\Services\DocumentChunker;
use PHPUnit\Framework\TestCase;

class DocumentChunkerTest extends TestCase
{
    private DocumentChunker $chunker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chunker = new DocumentChunker;
    }

    public function test_short_text_returns_single_chunk(): void
    {
        $text = 'Hello world.';
        $chunks = $this->chunker->chunk($text);

        $this->assertCount(1, $chunks);
        $this->assertSame($text, $chunks[0]);
    }

    public function test_empty_string_returns_empty_array(): void
    {
        $chunks = $this->chunker->chunk('');

        $this->assertCount(0, $chunks);
    }

    public function test_multi_paragraph_text_splits_correctly(): void
    {
        $paragraphs = array_fill(0, 10, str_repeat('word ', 30));
        $text = implode("\n\n", $paragraphs);

        $chunks = $this->chunker->chunk($text);

        $this->assertGreaterThan(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertNotEmpty(trim($chunk));
        }
    }

    public function test_all_content_preserved_across_chunks(): void
    {
        $uniqueWords = [];
        for ($i = 0; $i < 20; $i++) {
            $uniqueWords[] = "UNIQUEWORD{$i}";
        }

        $paragraphs = array_map(fn ($w) => str_repeat('text ', 40).$w, $uniqueWords);
        $text = implode("\n\n", $paragraphs);

        $chunks = $this->chunker->chunk($text);
        $allText = implode(' ', $chunks);

        foreach ($uniqueWords as $word) {
            $this->assertStringContainsString($word, $allText);
        }
    }

    public function test_large_single_paragraph_is_split(): void
    {
        $text = str_repeat('a ', 1000);

        $chunks = $this->chunker->chunk($text);

        $this->assertGreaterThan(1, count($chunks));
    }
}
