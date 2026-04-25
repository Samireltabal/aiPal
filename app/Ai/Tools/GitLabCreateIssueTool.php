<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\ResolvesContextHint;
use App\Models\User;
use App\Services\GitLabService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class GitLabCreateIssueTool extends AiTool
{
    use ResolvesContextHint;

    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'gitlab_create_issue';
    }

    public static function toolLabel(): string
    {
        return 'GitLab: Create Issue';
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
        return 'Create a new GitLab issue in a project. Use when the user says "create a GitLab issue", "open a ticket in project X", "log a bug in GitLab", or similar.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_path' => $schema->string()
                ->description('GitLab project path, e.g. "mygroup/myrepo".')
                ->required(),
            'title' => $schema->string()
                ->description('Issue title.')
                ->required(),
            'description' => $schema->string()
                ->description('Optional longer description (supports Markdown).')
                ->nullable()
                ->required(),
            'labels' => $schema->string()
                ->description('Comma-separated labels, e.g. "bug,backend". Pass null if none.')
                ->nullable()
                ->required(),
            'assignee_username' => $schema->string()
                ->description('GitLab username to assign the issue to. Pass null to leave unassigned.')
                ->nullable()
                ->required(),
            ...$this->contextSchema($schema),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasGitLabConnected()) {
            return 'GitLab is not connected. Please add your GitLab token in Settings.';
        }

        return $this->withRequestedContext($request, fn (): Stringable|string => $this->doExecute($request));
    }

    private function doExecute(Request $request): Stringable|string
    {
        try {
            $gitlab = GitLabService::forUser($this->user);
            $issue = $gitlab->createIssue(
                projectPath: $request['project_path'],
                title: $request['title'],
                description: $request['description'] ?? null,
                labels: $request['labels'] ?? null,
                assigneeUsername: $request['assignee_username'] ?? null,
            );
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        $iid = $issue['iid'] ?? '?';
        $url = $issue['web_url'] ?? '';

        return "GitLab issue #{$iid} created: \"{$request['title']}\" ({$url})";
    }
}
