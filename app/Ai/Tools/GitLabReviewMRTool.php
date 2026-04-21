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
        return 'Fetch the diff of a GitLab merge request, review the code with AI, and optionally post the review as a comment on the MR. Use when asked to review, critique, or check a merge request.';
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
                ->description('If true, post the review as a comment on the MR. Defaults to false.')
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
            $gitlab = new GitLabService($this->user);
            $mr = $gitlab->getMergeRequest($projectPath, $mrIid);
            $changes = $gitlab->getMergeRequestChanges($projectPath, $mrIid);
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        $diff = $this->buildDiff($changes['changes'] ?? []);

        if ($diff === '') {
            return "MR !{$mrIid} has no file changes to review.";
        }

        $review = $this->reviewDiff(
            title: $mr['title'] ?? "MR !{$mrIid}",
            description: $mr['description'] ?? '',
            diff: $diff,
            focus: $focus,
        );

        if ($postComment) {
            try {
                $gitlab->createMergeRequestNote($projectPath, $mrIid, $this->formatCommentBody($review));
            } catch (RuntimeException $e) {
                $review .= "\n\n⚠ Could not post comment: {$e->getMessage()}";
            }
        }

        return $review;
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

    private function reviewDiff(string $title, string $description, string $diff, ?string $focus): string
    {
        $focusInstruction = $focus
            ? "Focus specifically on: {$focus}."
            : 'Provide a general review covering bugs, security, performance, and style.';

        $reviewer = new class extends \stdClass implements Agent, HasStructuredOutput
        {
            use Promptable;

            public function instructions(): string
            {
                return 'You are an expert code reviewer. Analyze the provided merge request diff for bugs, security issues, performance problems, and style concerns. Be concise and actionable.';
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
                            'file' => $schema->string()->description('Affected file path, or null if general.')->nullable(),
                            'message' => $schema->string(),
                        ])->required(['severity', 'file', 'message']))
                        ->required(),
                    'suggestions' => $schema->array()
                        ->items($schema->string())
                        ->description('Positive suggestions for improvement.')
                        ->required(),
                ];
            }
        };

        $prompt = "MR title: {$title}\n\nDescription: {$description}\n\n{$focusInstruction}\n\nDiff:\n```diff\n{$diff}\n```";
        $response = $reviewer->prompt($prompt);

        $issues = $response['issues'] ?? [];
        $suggestions = $response['suggestions'] ?? [];
        $summary = $response['summary'] ?? 'Review complete.';

        $lines = ["**Code Review for MR**: {$summary}", ''];

        if (! empty($issues)) {
            $lines[] = '**Issues:**';
            foreach ($issues as $issue) {
                $file = $issue['file'] ? " (`{$issue['file']}`)" : '';
                $lines[] = "• [{$issue['severity']}]{$file} {$issue['message']}";
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

    private function formatCommentBody(string $review): string
    {
        return "🤖 **AI Code Review**\n\n{$review}";
    }
}
