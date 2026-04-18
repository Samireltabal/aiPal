<?php

declare(strict_types=1);

namespace App\Ai\Agents\Chat;

use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

class ChatAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    private string $systemPrompt = 'You are a helpful personal assistant. Be concise, accurate, and friendly.';

    public function withSystemPrompt(string $prompt): static
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    public function instructions(): Stringable|string
    {
        return $this->systemPrompt;
    }
}
