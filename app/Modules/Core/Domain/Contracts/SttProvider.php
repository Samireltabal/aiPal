<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain\Contracts;

interface SttProvider
{
    public function transcribe(string $audioPath, string $language = 'en'): string;
}
