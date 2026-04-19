<?php

declare(strict_types=1);

namespace App\Services;

class DocumentChunker
{
    private const CHUNK_SIZE = 800;

    private const OVERLAP = 100;

    /**
     * Split text into overlapping chunks.
     *
     * Strategy: split by paragraphs first, then sentences, then fixed size.
     * Overlapping context helps the model answer questions that span chunk boundaries.
     *
     * @return string[]
     */
    public function chunk(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        if (mb_strlen($text) <= self::CHUNK_SIZE) {
            return [$text];
        }

        $paragraphs = preg_split('/\n{2,}/', $text);
        $paragraphs = array_filter(array_map('trim', $paragraphs));

        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            if (mb_strlen($paragraph) > self::CHUNK_SIZE) {
                // Paragraph itself is too large — flush current and split the paragraph
                if ($current !== '') {
                    $chunks[] = trim($current);
                    $current = '';
                }

                foreach ($this->splitLargeParagraph($paragraph) as $subChunk) {
                    $chunks[] = $subChunk;
                }

                continue;
            }

            $candidate = $current === '' ? $paragraph : $current."\n\n".$paragraph;

            if (mb_strlen($candidate) > self::CHUNK_SIZE) {
                $chunks[] = trim($current);
                // Start next chunk with overlap from end of previous
                $current = $this->tailOverlap($current).$paragraph;
            } else {
                $current = $candidate;
            }
        }

        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }

        return array_values(array_filter($chunks, static fn (string $c) => trim($c) !== ''));
    }

    /**
     * Split a large paragraph by fixed size with overlap.
     *
     * @return string[]
     */
    private function splitLargeParagraph(string $text): array
    {
        $chunks = [];
        $offset = 0;
        $length = mb_strlen($text);

        while ($offset < $length) {
            $chunk = mb_substr($text, $offset, self::CHUNK_SIZE);
            $chunks[] = trim($chunk);
            $offset += self::CHUNK_SIZE - self::OVERLAP;
        }

        return $chunks;
    }

    /**
     * Extract the last OVERLAP characters from a chunk for context continuity.
     */
    private function tailOverlap(string $text): string
    {
        $tail = mb_substr($text, -self::OVERLAP);

        // Try to start at a word boundary
        $spacePos = mb_strpos($tail, ' ');
        if ($spacePos !== false && $spacePos < self::OVERLAP - 20) {
            $tail = mb_substr($tail, $spacePos + 1);
        }

        return $tail !== '' ? $tail."\n\n" : '';
    }
}
