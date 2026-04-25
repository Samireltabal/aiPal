<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class GitHubCreateIssueTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'github_create_issue';
    }

    public static function toolLabel(): string
    {
        return 'GitHub: Create Issue';
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
        return 'Create a new GitHub issue in a repository. Use when the user says "create a GitHub issue", "open a ticket in repo X", "log a bug on GitHub", or similar.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repo' => $schema->string()
                ->description('GitHub repository in "owner/repo" format, e.g. "myorg/myrepo".')
                ->required(),
            'title' => $schema->string()
                ->description('Issue title.')
                ->required(),
            'body' => $schema->string()
                ->description('Optional longer description (supports Markdown).')
                ->nullable()
                ->required(),
            'labels' => $schema->string()
                ->description('Comma-separated labels, e.g. "bug,backend". Pass null if none.')
                ->nullable()
                ->required(),
            'assignee' => $schema->string()
                ->description('GitHub username to assign the issue to. Pass null to leave unassigned.')
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
            $issue = $github->createIssue(
                repo: $request['repo'],
                title: $request['title'],
                body: $request['body'] ?? null,
                labels: $request['labels'] ?? null,
                assignee: $request['assignee'] ?? null,
            );
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        $number = $issue['number'] ?? '?';
        $url = $issue['html_url'] ?? '';

        return "GitHub issue #{$number} created: \"{$request['title']}\" ({$url})";
    }
}
