<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain\Contracts;

interface AiProvider
{
    public function chat(array $messages, array $options = []): string;

    /** @return iterable<string> */
    public function streamChat(array $messages, array $options = []): iterable;
}
