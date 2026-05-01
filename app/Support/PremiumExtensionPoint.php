<?php

declare(strict_types=1);

namespace App\Support;

interface PremiumExtensionPoint
{
    public function isEnabled(): bool;

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array;
}
