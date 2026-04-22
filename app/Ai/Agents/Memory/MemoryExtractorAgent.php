<?php

declare(strict_types=1);

namespace App\Ai\Agents\Memory;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class MemoryExtractorAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
            You are a memory extraction assistant. Given a conversation transcript, extract durable facts about the user.
            Focus on: preferences, expertise, personal details, habits, goals, relationships, and recurring topics.
            Only extract facts that are clearly stated or strongly implied — never infer speculatively.
            Return an empty array if nothing worth remembering was shared.
            Keep each fact as a concise, self-contained sentence.
            PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'facts' => $schema->array()
                ->items($schema->string()->description('A single durable fact about the user.'))
                ->required()
                ->description('List of extracted facts. Empty array if none.'),
        ];
    }
}
