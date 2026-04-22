<?php

declare(strict_types=1);

namespace App\Ai\Agents\Briefing;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class DailyBriefingAgent implements Agent
{
    use Promptable;

    public function __construct(
        private readonly string $userName,
        private readonly string $date,
        private readonly string $tasksContext,
        private readonly string $remindersContext,
        private readonly string $calendarContext,
    ) {}

    public function instructions(): string
    {
        return <<<'PROMPT'
            You are a personal assistant writing a concise morning briefing email.
            Be warm, direct, and organized. Use bullet points for lists. Keep it under 250 words.
            Do not add disclaimers or unnecessary filler.
            PROMPT;
    }

    public function buildPrompt(): string
    {
        return <<<PROMPT
            Write a morning briefing for {$this->userName} for {$this->date}.

            TASKS (pending):
            {$this->tasksContext}

            REMINDERS (today):
            {$this->remindersContext}

            CALENDAR (today):
            {$this->calendarContext}

            Format it as a friendly morning summary email body (no subject line, no greeting header—just the body content).
            PROMPT;
    }
}
