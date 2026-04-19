<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Persona;
use App\Services\AvatarGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateAvatarJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public readonly int $personaId) {}

    public function handle(AvatarGenerator $generator): void
    {
        $persona = Persona::find($this->personaId);

        if (! $persona) {
            return;
        }

        $path = $generator->generate($persona);

        $persona->update(['avatar_path' => $path]);
    }
}
