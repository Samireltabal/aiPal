<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\ResolvesContextHint;
use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class GitHubPRTool extends AiTool
{
    use ResolvesContextHint;

    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'github_pull_requests';
    }

    public static function toolLabel(): string
    {
        return 'GitHub: Pull Requests';
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
        return 'List or summarize GitHub pull requests. Use when asked about open PRs, pull requests, code reviews, or CI status assigned to the user or in a specific repository.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repo' => $schema->string()
                ->description('GitHub repository in "owner/repo" format, e.g. "torvalds/linux". Pass null to list PRs assigned to the user across all repos.')
                ->nullable()
                ->required(),
            'state' => $schema->string()
                ->description('PR state: "open", "closed", or "all". Defaults to "open".')
                ->enum(['open', 'closed', 'all'])
                ->nullable()
                ->required(),
            'max_results' => $schema->integer()
                ->description('Maximum number of PRs to return (1–20). Defaults to 10.')
                ->min(1)
                ->max(20)
                ->nullable()
                ->required(),
            ...$this->contextSchema($schema),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasGitHubConnected()) {
            return 'GitHub is not connected. Please add your GitHub token in Settings.';
        }

        return $this->withRequestedContext($request, fn (): Stringable|string => $this->doExecute($request));
    }

    private function doExecute(Request $request): Stringable|string
    {
        try {
            $github = GitHubService::forUser($this->user);
            $prs = $github->listPullRequests(
                repo: $request['repo'] ?? null,
                state: $request['state'] ?? 'open',
                perPage: $request['max_results'] ?? 10,
            );
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        if (empty($prs)) {
            $state = $request['state'] ?? 'open';

            return "No {$state} pull requests found.";
        }

        $lines = array_map(function (array $pr): string {
            $number = $pr['number'] ?? '?';
            $title = $pr['title'] ?? 'Untitled';
            $state = $pr['state'] ?? '';
            $draft = ($pr['draft'] ?? false) ? ' [Draft]' : '';
            $user = $pr['user']['login'] ?? 'Unknown';
            $repoUrl = (string) ($pr['repository_url'] ?? '');
            $repo = $pr['base']['repo']['full_name']
                ?? ($repoUrl !== '' ? implode('/', array_slice(explode('/', $repoUrl), -2)) : '');

            return "• #{$number}{$draft}: {$title} — {$state} | {$user} | {$repo}";
        }, $prs);

        return 'Pull requests ('.count($prs)."):\n".implode("\n", $lines);
    }
}
