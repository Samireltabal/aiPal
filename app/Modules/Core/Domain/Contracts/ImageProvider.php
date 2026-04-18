<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain\Contracts;

interface ImageProvider
{
    /** Returns URL or local path of the generated image */
    public function generate(string $prompt, array $options = []): string;
}
