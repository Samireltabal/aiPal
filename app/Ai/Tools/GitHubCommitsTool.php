<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class GitHubCommitsTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'github_commits';
    }

    public static function toolLabel(): string
    {
        return 'GitHub: Recent Commits';
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
        return 'List recent commits for a GitHub repository. Use when asked about recent changes, commit history, what was deployed, or what changed in a repo.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repo' => $schema->string()
                ->description('GitHub repository in "owner/repo" format, e.g. "torvalds/linux".')
                ->required(),
            'branch' => $schema->string()
                ->description('Branch name. Pass null to use the default branch.')
                ->nullable()
                ->required(),
            'max_results' => $schema->integer()
                ->description('Number of commits to return (1–20). Defaults to 10.')
                ->min(1)
                ->max(20)
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasGitHubConnected()) {
            return 'GitHub is not connected. Please add your GitHub token in Settings.';
        }

        try {
            $github = GitHubService::forUser($this->user);
            $commits = $github->listCommits(
                repo: $request['repo'],
                branch: $request['branch'] ?? null,
                perPage: $request['max_results'] ?? 10,
            );
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        if (empty($commits)) {
            return "No commits found for {$request['repo']}.";
        }

        $lines = array_map(function (array $commit): string {
            $sha = substr($commit['sha'], 0, 8);
            $rawMessage = $commit['commit']['message'] ?? '';
            $message = strtok($rawMessage !== '' ? $rawMessage : '(no message)', "\n");
            $author = $commit['commit']['author']['name'] ?? 'Unknown';
            $rawDate = $commit['commit']['author']['date'] ?? null;
            $date = $rawDate ? Carbon::parse($rawDate)->diffForHumans() : 'unknown date';

            return "• {$sha} {$date} by {$author}: {$message}";
        }, $commits);

        $branch = $request['branch'] ?? 'default branch';

        return "Recent commits on {$request['repo']} ({$branch}):\n".implode("\n", $lines);
    }
}
