<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JiraService
{
    private string $baseUrl;

    private string $email;

    private string $token;

    public function __construct(User $user)
    {
        if (! $user->hasJiraConnected()) {
            throw new RuntimeException('Jira is not configured. Please add your Jira credentials in Settings.');
        }

        $this->baseUrl = rtrim((string) $user->jira_host, '/').'/rest/api/3';
        $this->email = (string) $user->jira_email;
        $this->token = (string) $user->jira_token;
    }

    /** Search issues using JQL. */
    public function searchIssues(string $jql, int $maxResults = 10): array
    {
        $response = $this->request('GET', '/search/jql', [
            'jql' => $jql,
            'maxResults' => $maxResults,
            'fields' => 'summary,status,assignee,priority,issuetype,created,updated,duedate,description',
        ]);

        return $response->json('issues', []);
    }

    /** Get a single issue by key (e.g. "PROJ-123"). */
    public function getIssue(string $issueKey): array
    {
        return $this->request('GET', "/issue/{$issueKey}")->json();
    }

    /** Create a new issue, optionally as a sub-task under a parent. */
    public function createIssue(
        string $projectKey,
        string $summary,
        string $issueType = 'Task',
        ?string $description = null,
        ?string $priority = null,
        ?string $parentIssueKey = null,
    ): array {
        $fields = [
            'project' => ['key' => $projectKey],
            'summary' => $summary,
        ];

        if ($parentIssueKey !== null) {
            $subTaskType = $this->resolveSubTaskType($projectKey);
            $fields['issuetype'] = ['id' => $subTaskType['id']];
            $fields['parent'] = ['key' => $parentIssueKey];
        } else {
            $fields['issuetype'] = ['name' => $issueType];
        }

        if ($description !== null) {
            $fields['description'] = [
                'type' => 'doc',
                'version' => 1,
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => $description]],
                ]],
            ];
        }

        if ($priority !== null) {
            $fields['priority'] = ['name' => $priority];
        }

        return $this->request('POST', '/issue', [], ['fields' => $fields])->json();
    }

    /** Get available issue types for a project. */
    public function getIssueTypes(string $projectKey): array
    {
        $projects = $this->request('GET', '/issue/createmeta', [
            'projectKeys' => $projectKey,
            'expand' => 'projects.issuetypes',
        ])->json('projects', []);

        return $projects[0]['issuetypes'] ?? [];
    }

    /** Resolve the sub-task issue type for a project, or throw if unsupported. */
    private function resolveSubTaskType(string $projectKey): array
    {
        $types = $this->getIssueTypes($projectKey);

        foreach ($types as $type) {
            if (($type['subtask'] ?? false) === true) {
                return $type;
            }
        }

        throw new RuntimeException("Project {$projectKey} does not support sub-tasks. Available types: ".implode(', ', array_column($types, 'name')));
    }

    /** Transition an issue to a new status. */
    public function transitionIssue(string $issueKey, string $transitionId): void
    {
        $this->request('POST', "/issue/{$issueKey}/transitions", [], [
            'transition' => ['id' => $transitionId],
        ]);
    }

    /** List available transitions for an issue. */
    public function getTransitions(string $issueKey): array
    {
        return $this->request('GET', "/issue/{$issueKey}/transitions")->json('transitions', []);
    }

    /** Update an issue's fields. */
    public function updateIssue(string $issueKey, array $fields): void
    {
        $this->request('PUT', "/issue/{$issueKey}", [], ['fields' => $fields]);
    }

    /** Get available projects. */
    public function getProjects(): array
    {
        return $this->request('GET', '/project')->json();
    }

    private function request(string $method, string $path, array $query = [], array $body = []): Response
    {
        $request = Http::withBasicAuth($this->email, $this->token)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(15);

        $url = $this->baseUrl.$path;

        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $query),
            'POST' => $request->post($url, $body),
            'PUT' => $request->put($url, $body),
            default => throw new RuntimeException("Unsupported HTTP method: {$method}"),
        };

        if ($response->failed()) {
            $error = $response->json('errorMessages.0') ?? $response->json('message') ?? "HTTP {$response->status()}";
            throw new RuntimeException("Jira API error: {$error}");
        }

        return $response;
    }
}
