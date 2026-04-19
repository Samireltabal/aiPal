<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class DocumentParser
{
    private const SUPPORTED_MIME_TYPES = [
        'text/plain',
        'text/markdown',
        'text/x-markdown',
        'text/html',
        'text/css',
        'text/javascript',
        'application/javascript',
        'application/json',
        'application/x-yaml',
        'text/x-php',
        'application/x-php',
        'text/x-python',
        'text/x-java-source',
        'text/x-go',
        'text/x-rustsrc',
        'text/csv',
        'text/xml',
        'application/xml',
        'application/x-sh',
    ];

    private const SUPPORTED_EXTENSIONS = [
        'txt', 'md', 'markdown', 'rst',
        'php', 'js', 'ts', 'jsx', 'tsx', 'mjs',
        'py', 'java', 'go', 'rs', 'rb', 'swift',
        'css', 'html', 'htm', 'json', 'yaml', 'yml',
        'xml', 'csv', 'sh', 'bash', 'zsh',
        'sql', 'graphql', 'gql', 'toml', 'env',
    ];

    public function canParse(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType() ?? '';

        return in_array($extension, self::SUPPORTED_EXTENSIONS, true)
            || in_array($mime, self::SUPPORTED_MIME_TYPES, true);
    }

    public function parse(UploadedFile $file): string
    {
        if (! $this->canParse($file)) {
            throw new RuntimeException(
                "Unsupported file type: {$file->getClientOriginalExtension()}. Supported: text, markdown, code files (PHP, JS, Python, etc.)"
            );
        }

        $content = file_get_contents($file->getRealPath());

        if ($content === false) {
            throw new RuntimeException("Failed to read file: {$file->getClientOriginalName()}");
        }

        // Strip HTML tags for HTML files, keep content readable
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['html', 'htm'], true)) {
            $content = strip_tags($content);
        }

        return $this->normalizeWhitespace($content);
    }

    private function normalizeWhitespace(string $content): string
    {
        // Normalize line endings
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);

        // Collapse excessive blank lines (more than 2 consecutive)
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        return trim($content);
    }
}
