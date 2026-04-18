<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain\Contracts;

interface EmbeddingProvider
{
    /** @return float[] */
    public function embed(string $text): array;

    /**
     * @param  string[]  $texts
     * @return float[][]
     */
    public function embedBatch(array $texts): array;
}
