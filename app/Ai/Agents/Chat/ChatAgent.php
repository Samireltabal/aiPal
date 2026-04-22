<?php

declare(strict_types=1);

namespace App\Ai\Agents\Chat;

use App\Ai\Services\ToolRegistry;
use App\Models\User;
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

    /** @var array<int, string>|null Whitelist of tool names — when set, overrides user-level tool settings. */
    private ?array $toolNameFilter = null;

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

    /**
     * Restrict the agent to a specific whitelist of tool names.
     *
     * @param  array<int, string>  $toolNames
     */
    public function withToolNames(array $toolNames): static
    {
        $this->toolNameFilter = $toolNames;

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

        $tools = app(ToolRegistry::class)->forUser($this->user);

        if ($this->toolNameFilter === null) {
            return $tools;
        }

        $allowed = array_flip($this->toolNameFilter);

        return array_values(array_filter(
            $tools,
            fn ($tool) => isset($allowed[$tool::toolName()]),
        ));
    }
}
