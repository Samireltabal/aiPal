<?php

declare(strict_types=1);

namespace App\Services;

use function Laravel\Ai\agent;

class PersonaGenerator
{
    /**
     * Generate a system prompt from the user's onboarding answers.
     *
     * @param  array{
     *   assistant_name: string,
     *   tone: string,
     *   formality: string,
     *   humor_level: string,
     *   backstory: string,
     * }  $answers
     */
    public function generate(array $answers): string
    {
        $prompt = <<<PROMPT
        Create a concise but rich system prompt for a personal AI assistant based on these attributes:

        Name: {$answers['assistant_name']}
        Tone: {$answers['tone']}
        Formality: {$answers['formality']}
        Humor: {$answers['humor_level']}
        Backstory/Role: {$answers['backstory']}

        Write the system prompt in second person ("You are..."). It should define the assistant's personality,
        communication style, and role. Keep it under 200 words. Do not include instructions about safety or ethics.
        Output only the system prompt text, nothing else.
        PROMPT;

        $response = agent(
            instructions: 'You are an expert prompt engineer. You write concise, effective system prompts.',
        )->prompt($prompt);

        return (string) $response;
    }
}
