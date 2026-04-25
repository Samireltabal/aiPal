<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\ResolvesContextHint;
use App\Models\User;
use App\Services\JiraService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class JiraCreateIssueTool extends AiTool
{
    use ResolvesContextHint;

    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'jira_create_issue';
    }

    public static function toolLabel(): string
    {
        return 'Jira: Create Issue';
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
        return 'Create a new Jira issue or sub-task. Use when the user says "create a Jira ticket", "add an issue to project X", "log a bug in Jira", "create sub-tasks under PROJ-123", or similar. Pass parent_issue_key to create a sub-task linked to a parent issue.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_key' => $schema->string()
                ->description('Jira project key, e.g. "MYPROJ" or "BACKEND".')
                ->required(),
            'summary' => $schema->string()
                ->description('Issue title / summary.')
                ->required(),
            'issue_type' => $schema->string()
                ->description('Issue type: "Task", "Bug", "Story", or "Epic". Ignored when parent_issue_key is set — the correct sub-task type is resolved automatically. Defaults to "Task".')
                ->enum(['Task', 'Bug', 'Story', 'Epic'])
                ->nullable()
                ->required(),
            'description' => $schema->string()
                ->description('Optional longer description of the issue.')
                ->nullable()
                ->required(),
            'priority' => $schema->string()
                ->description('Priority: "Highest", "High", "Medium", "Low", "Lowest". Pass null to use project default.')
                ->enum(['Highest', 'High', 'Medium', 'Low', 'Lowest'])
                ->nullable()
                ->required(),
            'parent_issue_key' => $schema->string()
                ->description('Parent issue key, e.g. "JOODDEV-1414". When provided, the issue is created as a sub-task under this parent. The correct sub-task type ID is fetched automatically from the project.')
                ->nullable()
                ->required(),
            ...$this->contextSchema($schema),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasJiraConnected()) {
            return 'Jira is not connected. Please add your Jira credentials in Settings.';
        }

        return $this->withRequestedContext($request, fn (): Stringable|string => $this->doExecute($request));
    }

    private function doExecute(Request $request): Stringable|string
    {
        try {
            $jira = JiraService::forUser($this->user);
            $issue = $jira->createIssue(
                projectKey: $request['project_key'],
                summary: $request['summary'],
                issueType: $request['issue_type'] ?? 'Task',
                description: $request['description'] ?? null,
                priority: $request['priority'] ?? null,
                parentIssueKey: $request['parent_issue_key'] ?? null,
            );
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        $key = $issue['key'] ?? 'unknown';
        $url = $jira->host()."/browse/{$key}";

        return "Jira issue created: {$key} — \"{$request['summary']}\" ({$url})";
    }
}
