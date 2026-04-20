<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\JiraService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class JiraCreateIssueTool extends AiTool
{
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
        return 'Create a new Jira issue. Use when the user says "create a Jira ticket", "add an issue to project X", "log a bug in Jira", or similar.';
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
                ->description('Issue type: "Task", "Bug", "Story", "Epic", or "Subtask". Defaults to "Task".')
                ->enum(['Task', 'Bug', 'Story', 'Epic', 'Subtask'])
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
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        if (! $this->user->hasJiraConnected()) {
            return 'Jira is not connected. Please add your Jira credentials in Settings.';
        }

        try {
            $jira = new JiraService($this->user);
            $issue = $jira->createIssue(
                projectKey: $request['project_key'],
                summary: $request['summary'],
                issueType: $request['issue_type'] ?? 'Task',
                description: $request['description'] ?? null,
                priority: $request['priority'] ?? null,
            );
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        $key = $issue['key'] ?? 'unknown';
        $url = rtrim((string) $this->user->jira_host, '/')."/browse/{$key}";

        return "Jira issue created: {$key} — \"{$request['summary']}\" ({$url})";
    }
}
