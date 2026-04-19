<?php

declare(strict_types=1);

namespace App\Ai\Agents\Chat;

use App\Ai\Tools\SearchKnowledgeBase;
use App\Models\User;
use App\Services\EmbeddingService;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxSteps(10)]
class ChatAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    private string $systemPrompt = 'You are a helpful personal assistant. Be concise, accurate, and friendly.';

    private ?User $user = null;

    public function withSystemPrompt(string $prompt): static
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    public function withUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function instructions(): Stringable|string
    {
        return $this->systemPrompt;
    }

    public function tools(): iterable
    {
        if ($this->user === null) {
            return [];
        }

        return [
            new SearchKnowledgeBase($this->user, app(EmbeddingService::class)),
        ];
    }
}
