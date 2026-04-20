<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\Request;
use Stringable;

class CodeReviewTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'code_review';
    }

    public static function toolLabel(): string
    {
        return 'Code Review';
    }

    public static function toolCategory(): string
    {
        return 'productivity';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Review a code snippet or diff and return structured feedback with issues and suggestions. Use when the user asks to review, critique, or check code quality.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()
                ->description('The code snippet or diff to review. Paste the full content.')
                ->required(),
            'language' => $schema->string()
                ->description('Programming language, e.g. "php", "javascript", "python". Pass null to auto-detect.')
                ->nullable()
                ->required(),
            'focus' => $schema->string()
                ->description('Optional focus area: "security", "performance", "style", "bugs". Pass null for a general review.')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $language = $request['language'] ?? 'auto-detect';
        $focus = $request['focus'] ? "Focus specifically on: {$request['focus']}." : 'Provide a general review.';

        $reviewer = new class extends \stdClass implements Agent, HasStructuredOutput
        {
            use Promptable;

            public function instructions(): string
            {
                return 'You are an expert code reviewer. Analyze code for bugs, security issues, performance problems, and style concerns. Be concise and actionable.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'summary' => $schema->string()
                        ->description('One-sentence overall verdict.')
                        ->required(),
                    'issues' => $schema->array()
                        ->items($schema->object()->properties([
                            'severity' => $schema->string()->enum(['critical', 'high', 'medium', 'low']),
                            'line' => $schema->string()->description('Line or range, e.g. "12" or "12-15". null if not applicable.')->nullable(),
                            'message' => $schema->string(),
                        ])->required(['severity', 'line', 'message']))
                        ->required(),
                    'suggestions' => $schema->array()
                        ->items($schema->string())
                        ->description('Positive suggestions for improvement.')
                        ->required(),
                ];
            }
        };

        $prompt = "Language: {$language}. {$focus}\n\nCode to review:\n```\n{$request['code']}\n```";
        $response = $reviewer->prompt($prompt);

        $issues = $response['issues'] ?? [];
        $suggestions = $response['suggestions'] ?? [];
        $summary = $response['summary'] ?? 'Review complete.';

        if (empty($issues) && empty($suggestions)) {
            return "Code Review: {$summary}\n\nNo issues found.";
        }

        $lines = ["**Code Review**: {$summary}", ''];

        if (! empty($issues)) {
            $lines[] = '**Issues:**';
            foreach ($issues as $issue) {
                $line = $issue['line'] ? " (line {$issue['line']})" : '';
                $lines[] = "• [{$issue['severity']}]{$line} {$issue['message']}";
            }
        }

        if (! empty($suggestions)) {
            $lines[] = '';
            $lines[] = '**Suggestions:**';
            foreach ($suggestions as $s) {
                $lines[] = "• {$s}";
            }
        }

        return implode("\n", $lines);
    }
}
