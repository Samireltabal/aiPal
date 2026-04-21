<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class GitHubReviewPRTool extends AiTool
{
    private const MAX_DIFF_CHARS = 12_000;

    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'github_review_pr';
    }

    public static function toolLabel(): string
    {
        return 'GitHub: Review Pull Request';
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
        return 'Fetch the diff of a GitHub pull request, review the code with AI, and optionally post inline comments on each affected file. Use when asked to review, critique, or check a pull request.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repo' => $schema->string()
                ->description('GitHub repository in "owner/repo" format, e.g. "myorg/myrepo".')
                ->required(),
            'pr_number' => $schema->integer()
                ->description('The pull request number shown in the GitHub UI.')
                ->required(),
            'focus' => $schema->string()
                ->description('Optional review focus: "security", "performance", "style", "bugs". Pass null for a general review.')
                ->nullable()
                ->required(),
            'post_comment' => $schema->boolean()
                ->description('If true, post inline review comments on the PR and a summary. Defaults to false.')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasGitHubConnected()) {
            return 'GitHub is not connected. Please add your GitHub token in Settings.';
        }

        $repo = $request['repo'];
        $prNumber = (int) $request['pr_number'];
        $focus = $request['focus'] ?? null;
        $postComment = (bool) ($request['post_comment'] ?? false);

        try {
            $github = new GitHubService($this->user);
            $pr = $github->getPullRequest($repo, $prNumber);
            $diff = $github->getPullRequestDiff($repo, $prNumber);
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        if (trim($diff) === '') {
            return "PR #{$prNumber} has no file changes to review.";
        }

        $truncatedDiff = $this->truncateDiff($diff);

        $result = $this->runReview(
            title: $pr['title'] ?? "PR #{$prNumber}",
            description: $pr['body'] ?? '',
            diff: $truncatedDiff,
            focus: $focus,
        );

        $issues = $result['issues'] ?? [];
        $suggestions = $result['suggestions'] ?? [];
        $summary = $result['summary'] ?? 'Review complete.';

        if ($postComment) {
            $commitId = $pr['head']['sha'] ?? null;

            if ($commitId) {
                $this->postReview($github, $repo, $prNumber, $commitId, $summary, $issues, $suggestions);
            }
        }

        return $this->formatReviewText($summary, $issues, $suggestions);
    }

    private function truncateDiff(string $diff): string
    {
        if (strlen($diff) <= self::MAX_DIFF_CHARS) {
            return $diff;
        }

        return substr($diff, 0, self::MAX_DIFF_CHARS)."\n[diff truncated]";
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
                return 'You are an expert code reviewer. Analyze the provided pull request diff for bugs, security issues, performance problems, and style concerns. For each issue, identify the exact file path and the new line number in the diff where the problem occurs. Be concise and actionable.';
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
                            'file' => $schema->string()->description('Affected file path, or null if general.')->nullable()->required(),
                            'line' => $schema->integer()->description('New line number in the file. Null if not line-specific.')->nullable()->required(),
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

        $prompt = "PR title: {$title}\n\nDescription: {$description}\n\n{$focusInstruction}\n\nDiff:\n```diff\n{$diff}\n```";

        return $reviewer->prompt($prompt)->toArray();
    }

    private function postReview(
        GitHubService $github,
        string $repo,
        int $prNumber,
        string $commitId,
        string $summary,
        array $issues,
        array $suggestions,
    ): void {
        $inlineComments = [];
        $generalIssues = [];

        foreach ($issues as $issue) {
            $file = $issue['file'] ?? null;
            $line = isset($issue['line']) ? (int) $issue['line'] : null;

            if ($file !== null && $line !== null) {
                $severity = strtoupper($issue['severity']);
                $inlineComments[] = [
                    'path' => $file,
                    'line' => $line,
                    'body' => "🤖 **[{$severity}]** {$issue['message']}",
                ];
            } else {
                $generalIssues[] = $issue;
            }
        }

        $summaryBody = $this->formatSummaryNote($summary, $generalIssues, $suggestions);

        try {
            $github->createPullRequestReview($repo, $prNumber, $commitId, $summaryBody, $inlineComments);
        } catch (RuntimeException) {
            // Non-fatal — review text is still returned to chat.
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
            $lines[] = 'No issues found. LGTM!';
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
