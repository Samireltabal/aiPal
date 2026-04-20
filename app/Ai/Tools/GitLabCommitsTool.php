<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\GitLabService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class GitLabCommitsTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'gitlab_commits';
    }

    public static function toolLabel(): string
    {
        return 'GitLab: Recent Commits';
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
        return 'List recent commits for a GitLab project. Use when asked about recent changes, commit history, what was deployed, or what changed in a repository.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_path' => $schema->string()
                ->description('GitLab project path, e.g. "mygroup/myrepo".')
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
        if (! $this->user->hasGitLabConnected()) {
            return 'GitLab is not connected. Please add your GitLab token in Settings.';
        }

        try {
            $gitlab = new GitLabService($this->user);
            $commits = $gitlab->listCommits(
                projectPath: $request['project_path'],
                branch: $request['branch'] ?? null,
                perPage: $request['max_results'] ?? 10,
            );
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        if (empty($commits)) {
            return "No commits found for {$request['project_path']}.";
        }

        $lines = array_map(function (array $commit): string {
            $date = Carbon::parse($commit['committed_date'])->diffForHumans();
            $author = $commit['author_name'];
            $short = substr($commit['id'], 0, 8);
            $message = strtok($commit['title'], "\n");

            return "• {$short} {$date} by {$author}: {$message}";
        }, $commits);

        $branch = $request['branch'] ?? 'default branch';

        return "Recent commits on {$request['project_path']} ({$branch}):\n".implode("\n", $lines);
    }
}
