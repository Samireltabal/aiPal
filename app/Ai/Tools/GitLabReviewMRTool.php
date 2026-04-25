<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\GitLabService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class GitLabReviewMRTool extends AiTool
{
    private const MAX_DIFF_CHARS = 12_000;

    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'gitlab_review_mr';
    }

    public static function toolLabel(): string
    {
        return 'GitLab: Review Merge Request';
    }

    public static function toolCategory(): string
    {
        return 'integrations';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Fetch the diff of a GitLab merge request, review the code with AI, and optionally post inline comments on each affected file. Use when asked to review, critique, or check a merge request.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_path' => $schema->string()
                ->description('GitLab project path, e.g. "mygroup/myrepo".')
                ->required(),
            'mr_iid' => $schema->integer()
                ->description('The internal ID of the merge request (the number shown in the GitLab UI).')
                ->required(),
            'focus' => $schema->string()
                ->description('Optional review focus: "security", "performance", "style", "bugs". Pass null for a general review.')
                ->nullable()
                ->required(),
            'post_comment' => $schema->boolean()
                ->description('If true, post inline comments per file on the MR and a summary note. Defaults to false.')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasGitLabConnected()) {
            return 'GitLab is not connected. Please add your GitLab token in Settings.';
        }

        $projectPath = $request['project_path'];
        $mrIid = (int) $request['mr_iid'];
        $focus = $request['focus'] ?? null;
        $postComment = (bool) ($request['post_comment'] ?? false);

        try {
            $gitlab = GitLabService::forUser($this->user);
            $mr = $gitlab->getMergeRequest($projectPath, $mrIid);
            $changes = $gitlab->getMergeRequestChanges($projectPath, $mrIid);
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        $fileChanges = $changes['changes'] ?? [];
        $diffRefs = $changes['diff_refs'] ?? null;
        $diff = $this->buildDiff($fileChanges);

        if ($diff === '') {
            return "MR !{$mrIid} has no file changes to review.";
        }

        $result = $this->runReview(
            title: $mr['title'] ?? "MR !{$mrIid}",
            description: $mr['description'] ?? '',
            diff: $diff,
            focus: $focus,
        );

        $issues = $result['issues'] ?? [];
        $suggestions = $result['suggestions'] ?? [];
        $summary = $result['summary'] ?? 'Review complete.';

        if ($postComment && $diffRefs) {
            $this->postInlineComments($gitlab, $projectPath, $mrIid, $issues, $suggestions, $summary, $diffRefs);
        } elseif ($postComment) {
            try {
                $gitlab->createMergeRequestNote($projectPath, $mrIid, $this->formatSummaryNote($summary, $issues, $suggestions));
            } catch (RuntimeException $e) {
                // Non-fatal — review is still returned below.
            }
        }

        return $this->formatReviewText($summary, $issues, $suggestions);
    }

    /** Concatenate file diffs into a single string, capped at MAX_DIFF_CHARS. */
    private function buildDiff(array $changes): string
    {
        $parts = [];
        $total = 0;

        foreach ($changes as $file) {
            $fileDiff = "--- {$file['old_path']}\n+++ {$file['new_path']}\n{$file['diff']}";
            $len = strlen($fileDiff);

            if ($total + $len > self::MAX_DIFF_CHARS) {
                $parts[] = substr($fileDiff, 0, self::MAX_DIFF_CHARS - $total);
                $parts[] = "\n[diff truncated — ".count($changes).' files total]';
                break;
            }

            $parts[] = $fileDiff;
            $total += $len;
        }

        return implode("\n\n", $parts);
    }

    /** @return array{summary: string, issues: array, suggestions: array} */
    private function runReview(string $title, string $description, string $diff, ?string $focus): array
    {
        $focusInstruction = $focus
            ? "Focus specifically on: {$focus}."
            : 'Provide a general review covering bugs, security, performance, and style.';

        $reviewer = new class extends \stdClass implements Agent, HasStructuredOutput
        {
            use Promptable;

            public function instructions(): string
            {
                return 'You are an expert code reviewer. Analyze the provided merge request diff for bugs, security issues, performance problems, and style concerns. For each issue, identify the exact file path and the new line number in the diff where the problem occurs. Be concise and actionable.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'summary' => $schema->string()
                        ->description('One-sentence overall verdict.')
                        ->required(),
                    'issues' => $schema->array()
                        ->items($schema->object([
                            'severity' => $schema->string()->enum(['critical', 'high', 'medium', 'low'])->required(),
                            'file' => $schema->string()->description('Affected file path (new_path from the diff), or null if general.')->nullable()->required(),
                            'line' => $schema->integer()->description('The new line number in the file where the issue occurs. Null if not applicable to a specific line.')->nullable()->required(),
                            'message' => $schema->string()->required(),
                        ]))
                        ->required(),
                    'suggestions' => $schema->array()
                        ->items($schema->string())
                        ->description('Positive suggestions for improvement.')
                        ->required(),
                ];
            }
        };

        $prompt = "MR title: {$title}\n\nDescription: {$description}\n\n{$focusInstruction}\n\nDiff:\n```diff\n{$diff}\n```";

        return $reviewer->prompt($prompt)->toArray();
    }

    /**
     * Post an inline discussion for each issue that has a file + line.
     * Issues without a line, and suggestions, go into a single summary note.
     */
    private function postInlineComments(
        GitLabService $gitlab,
        string $projectPath,
        int $mrIid,
        array $issues,
        array $suggestions,
        string $summary,
        array $diffRefs,
    ): void {
        $generalIssues = [];

        foreach ($issues as $issue) {
            $file = $issue['file'] ?? null;
            $line = isset($issue['line']) ? (int) $issue['line'] : null;

            if ($file === null || $line === null) {
                $generalIssues[] = $issue;

                continue;
            }

            $severity = strtoupper($issue['severity']);
            $body = "🤖 **[{$severity}]** {$issue['message']}";

            try {
                $gitlab->createMergeRequestDiscussion($projectPath, $mrIid, $body, [
                    'position_type' => 'text',
                    'base_sha' => $diffRefs['base_sha'],
                    'start_sha' => $diffRefs['start_sha'],
                    'head_sha' => $diffRefs['head_sha'],
                    'new_path' => $file,
                    'new_line' => $line,
                ]);
            } catch (RuntimeException) {
                $generalIssues[] = $issue;
            }
        }

        $hasSummaryContent = ! empty($generalIssues) || ! empty($suggestions) || $summary;

        if ($hasSummaryContent) {
            try {
                $gitlab->createMergeRequestNote(
                    $projectPath,
                    $mrIid,
                    $this->formatSummaryNote($summary, $generalIssues, $suggestions),
                );
            } catch (RuntimeException) {
                // Non-fatal.
            }
        }
    }

    private function formatReviewText(string $summary, array $issues, array $suggestions): string
    {
        $lines = ["**Code Review**: {$summary}", ''];

        if (! empty($issues)) {
            $lines[] = '**Issues:**';
            foreach ($issues as $issue) {
                $file = $issue['file'] ? " (`{$issue['file']}`" : '';
                $line = ($issue['file'] && isset($issue['line'])) ? " line {$issue['line']}`" : ($issue['file'] ? '`' : '');
                $lines[] = "• [{$issue['severity']}]{$file}{$line} {$issue['message']}";
            }
        }

        if (! empty($suggestions)) {
            $lines[] = '';
            $lines[] = '**Suggestions:**';
            foreach ($suggestions as $s) {
                $lines[] = "• {$s}";
            }
        }

        if (empty($issues) && empty($suggestions)) {
            $lines[] = 'No issues found.';
        }

        return implode("\n", $lines);
    }

    private function formatSummaryNote(string $summary, array $issues, array $suggestions): string
    {
        $lines = ["🤖 **AI Code Review Summary**\n\n{$summary}"];

        if (! empty($issues)) {
            $lines[] = "\n**General Issues:**";
            foreach ($issues as $issue) {
                $file = $issue['file'] ? " (`{$issue['file']}`)" : '';
                $lines[] = "• [{$issue['severity']}]{$file} {$issue['message']}";
            }
        }

        if (! empty($suggestions)) {
            $lines[] = "\n**Suggestions:**";
            foreach ($suggestions as $s) {
                $lines[] = "• {$s}";
            }
        }

        return implode("\n", $lines);
    }
}
