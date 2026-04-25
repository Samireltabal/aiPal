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

class JiraSearchTool extends AiTool
{
    use ResolvesContextHint;

    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'jira_search';
    }

    public static function toolLabel(): string
    {
        return 'Jira: Search Issues';
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
        return 'Search Jira issues using JQL. Use when the user asks about Jira tickets, issues, sprint, backlog, open bugs, or anything related to their Jira projects.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'jql' => $schema->string()
                ->description('JQL query string. Examples: "project = MYPROJ AND status = Open", "assignee = currentUser() AND sprint in openSprints()", "priority = High AND status != Done ORDER BY created DESC".')
                ->required(),
            'max_results' => $schema->integer()
                ->description('Maximum number of issues to return (1–20). Defaults to 10.')
                ->min(1)
                ->max(20)
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
            $issues = $jira->searchIssues($request['jql'], $request['max_results'] ?? 10);
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        if (empty($issues)) {
            return "No issues found for JQL: {$request['jql']}";
        }

        $lines = array_map(function (array $issue): string {
            $f = $issue['fields'];
            $status = $f['status']['name'] ?? 'Unknown';
            $priority = $f['priority']['name'] ?? null;
            $assignee = $f['assignee']['displayName'] ?? 'Unassigned';
            $due = isset($f['duedate']) ? " | Due: {$f['duedate']}" : '';
            $priorityStr = $priority ? " [{$priority}]" : '';

            return "• {$issue['key']}{$priorityStr}: {$f['summary']} — {$status} | Assignee: {$assignee}{$due}";
        }, $issues);

        return 'Jira issues ('.count($issues)."):\n".implode("\n", $lines);
    }
}
