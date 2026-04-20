<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GitLabService
{
    private string $baseUrl;

    private string $token;

    public function __construct(User $user)
    {
        if (! $user->hasGitLabConnected()) {
            throw new RuntimeException('GitLab is not configured. Please add your GitLab token in Settings.');
        }

        $this->baseUrl = rtrim((string) $user->gitlab_host, '/').'/api/v4';
        $this->token = (string) $user->gitlab_token;
    }

    /** List merge requests across all projects or a specific one. */
    public function listMergeRequests(
        ?string $projectPath = null,
        string $state = 'opened',
        int $perPage = 10,
    ): array {
        if ($projectPath !== null) {
            $encodedPath = urlencode($projectPath);
            $endpoint = "/projects/{$encodedPath}/merge_requests";
        } else {
            $endpoint = '/merge_requests';
        }

        return $this->request('GET', $endpoint, [
            'state' => $state,
            'per_page' => $perPage,
            'order_by' => 'updated_at',
            'sort' => 'desc',
            'scope' => 'assigned_to_me',
        ])->json();
    }

    /** Get a single merge request. */
    public function getMergeRequest(string $projectPath, int $mrIid): array
    {
        $encodedPath = urlencode($projectPath);

        return $this->request('GET', "/projects/{$encodedPath}/merge_requests/{$mrIid}")->json();
    }

    /** List recent commits for a project. */
    public function listCommits(string $projectPath, ?string $branch = null, int $perPage = 10): array
    {
        $encodedPath = urlencode($projectPath);
        $params = ['per_page' => $perPage];

        if ($branch !== null) {
            $params['ref_name'] = $branch;
        }

        return $this->request('GET', "/projects/{$encodedPath}/repository/commits", $params)->json();
    }

    /** Create an issue in a project. */
    public function createIssue(
        string $projectPath,
        string $title,
        ?string $description = null,
        ?string $labels = null,
        ?string $assigneeUsername = null,
    ): array {
        $encodedPath = urlencode($projectPath);

        $body = ['title' => $title];

        if ($description !== null) {
            $body['description'] = $description;
        }

        if ($labels !== null) {
            $body['labels'] = $labels;
        }

        if ($assigneeUsername !== null) {
            $users = $this->request('GET', '/users', ['username' => $assigneeUsername])->json();
            if (! empty($users)) {
                $body['assignee_ids'] = [$users[0]['id']];
            }
        }

        return $this->request('POST', "/projects/{$encodedPath}/issues", [], $body)->json();
    }

    /** Search projects by name. */
    public function searchProjects(string $query, int $perPage = 5): array
    {
        return $this->request('GET', '/projects', [
            'search' => $query,
            'per_page' => $perPage,
            'membership' => true,
            'order_by' => 'last_activity_at',
        ])->json();
    }

    /** Get authenticated user info. */
    public function currentUser(): array
    {
        return $this->request('GET', '/user')->json();
    }

    private function request(string $method, string $path, array $query = [], array $body = []): Response
    {
        $request = Http::withHeaders([
            'PRIVATE-TOKEN' => $this->token,
            'Accept' => 'application/json',
        ])->timeout(15);

        $url = $this->baseUrl.$path;

        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $query),
            'POST' => $request->post($url, $body),
            'PUT' => $request->put($url, $body),
            default => throw new RuntimeException("Unsupported HTTP method: {$method}"),
        };

        if ($response->failed()) {
            $error = $response->json('message') ?? "HTTP {$response->status()}";
            throw new RuntimeException("GitLab API error: {$error}");
        }

        return $response;
    }
}
