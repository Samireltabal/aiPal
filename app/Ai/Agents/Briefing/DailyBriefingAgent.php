<?php

declare(strict_types=1);

namespace App\Ai\Agents\Briefing;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class DailyBriefingAgent implements Agent
{
    use Promptable;

    /**
     * @param  array<int, array{context: array{id:int,kind:string,name:string,is_default:bool}, tasks:string, reminders:string, calendar:string}>  $perContext
     */
    public function __construct(
        private readonly string $userName,
        private readonly string $date,
        private readonly array $perContext,
    ) {}

    public function instructions(): string
    {
        return <<<'PROMPT'
            You are a personal chief-of-staff writing a morning focus cast.
            Your job is to tell the user what matters TODAY — not summarize everything.

            Rules:
            - One-line intro (≤15 words), grounded in the actual day ahead.
            - Then EXACTLY 3 bullets, each a single actionable sentence (≤20 words).
            - If content comes from multiple contexts (work / freelance / personal), prefix each bullet with "[Context Name]".
              Single-context users: no prefix.
            - Prioritize: scheduled meetings, reminders firing today, top-priority or overdue tasks.
            - Balance contexts — don't stack all 3 bullets on one context if others have content.
            - No greeting, no sign-off, no emojis, no disclaimers, no filler.
            - If all contexts are thin, still return 3 bullets — include "protect deep-work block" or similar honest defaults.
            PROMPT;
    }

    public function buildPrompt(): string
    {
        $activeContexts = array_filter($this->perContext, fn (array $section): bool => $this->hasContent($section));
        $prefixMode = count($activeContexts) >= 2 ? 'with [Context] prefix' : 'no prefix';

        $sections = [];
        foreach ($this->perContext as $section) {
            $label = $section['context']['name'];
            $kind = $section['context']['kind'];
            $sections[] = <<<SECTION
                ### Context: {$label} ({$kind})
                CALENDAR (today):
                {$section['calendar']}

                REMINDERS (firing today):
                {$section['reminders']}

                TASKS (pending, highest priority first):
                {$section['tasks']}
                SECTION;
        }

        $body = implode("\n\n", $sections);

        return <<<PROMPT
            Write today's focus cast for {$this->userName} — {$this->date}.
            Bullet prefix mode: {$prefixMode}.

            {$body}

            Output format (plain text, nothing else):
            <one-line intro>
            • <bullet 1>
            • <bullet 2>
            • <bullet 3>
            PROMPT;
    }

    /** @param array{tasks:string, reminders:string, calendar:string} $section */
    private function hasContent(array $section): bool
    {
        $empty = ['No pending tasks.', 'No reminders scheduled for today.', 'No calendar events today.', 'No calendar connected to this context.', 'Google Calendar not connected.'];

        foreach (['tasks', 'reminders', 'calendar'] as $field) {
            if (! in_array($section[$field], $empty, true)) {
                return true;
            }
        }

        return false;
    }
}
