<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain\Contracts;

interface TtsProvider
{
    /** Returns path to generated audio file */
    public function synthesize(string $text, string $voice = 'alloy'): string;
}
