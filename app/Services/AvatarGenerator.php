<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Persona;
use Laravel\Ai\Image;

class AvatarGenerator
{
    public function generate(Persona $persona): string
    {
        $prompt = $this->buildPrompt($persona);

        $image = Image::of($prompt)
            ->square()
            ->quality('high')
            ->timeout(120)
            ->generate();

        $filename = 'avatars/persona-'.$persona->user_id.'.png';

        $image->storePubliclyAs($filename, disk: 'public');

        return $filename;
    }

    private function buildPrompt(Persona $persona): string
    {
        $tone = $persona->tone;
        $formality = $persona->formality;
        $name = $persona->assistant_name;
        $backstory = $persona->backstory
            ? ' '.$persona->backstory.'.'
            : '';

        return "A professional avatar portrait for an AI assistant named {$name}.{$backstory} "
            ."Personality: {$tone}, {$formality}. "
            .'Digital art style, clean background, friendly and approachable, high quality, no text.';
    }
}
