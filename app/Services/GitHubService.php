<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GitHubService
{
    private const BASE_URL = 'https://api.github.com';

    private string $token;

    public function __construct(User $user)
    {
        if (! $user->hasGitHubConnected()) {
            throw new RuntimeException('GitHub is not configured. Please add your GitHub token in Settings.');
        }

        $this->token = (string) $user->github_token;
    }

    /** List pull requests for a repo or all assigned to the user. */
    public function listPullRequests(
        ?string $repo = null,
        string $state = 'open',
        int $perPage = 10,
    ): array {
        if ($repo !== null) {
            return $this->request('GET', "/repos/{$repo}/pulls", [
                'state' => $state,
                'per_page' => $perPage,
                'sort' => 'updated',
                'direction' => 'desc',
            ])->json();
        }

        $query = "is:pr is:$state assignee:@me";

        return $this->request('GET', '/search/issues', [
            'q' => $query,
            'per_page' => $perPage,
            'sort' => 'updated',
        ])->json('items', []);
    }

    /** Get a single pull request. */
    public function getPullRequest(string $repo, int $number): array
    {
        return $this->request('GET', "/repos/{$repo}/pulls/{$number}")->json();
    }

    /** Get diff for a pull request. */
    public function getPullRequestDiff(string $repo, int $number): string
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/vnd.github.v3.diff',
        ])->timeout(15)->get(self::BASE_URL."/repos/{$repo}/pulls/{$number}")->body();
    }

    /** List recent commits for a repo. */
    public function listCommits(string $repo, ?string $branch = null, int $perPage = 10): array
    {
        $params = ['per_page' => $perPage];

        if ($branch !== null) {
            $params['sha'] = $branch;
        }

        return $this->request('GET', "/repos/{$repo}/commits", $params)->json();
    }

    /** Create an issue in a repo. */
    public function createIssue(
        string $repo,
        string $title,
        ?string $body = null,
        ?string $labels = null,
        ?string $assignee = null,
    ): array {
        $payload = ['title' => $title];

        if ($body !== null) {
            $payload['body'] = $body;
        }

        if ($labels !== null) {
            $payload['labels'] = array_map('trim', explode(',', $labels));
        }

        if ($assignee !== null) {
            $payload['assignees'] = [$assignee];
        }

        return $this->request('POST', "/repos/{$repo}/issues", [], $payload)->json();
    }

    /** Get CI/workflow run status for a repo. */
    public function listWorkflowRuns(string $repo, int $perPage = 5): array
    {
        return $this->request('GET', "/repos/{$repo}/actions/runs", [
            'per_page' => $perPage,
        ])->json('workflow_runs', []);
    }

    /** Get authenticated user info. */
    public function currentUser(): array
    {
        return $this->request('GET', '/user')->json();
    }

    private function request(string $method, string $path, array $query = [], array $body = []): Response
    {
        $request = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->timeout(15);

        $url = self::BASE_URL.$path;

        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $query),
            'POST' => $request->post($url, $body),
            'PATCH' => $request->patch($url, $body),
            default => throw new RuntimeException("Unsupported HTTP method: {$method}"),
        };

        if ($response->failed()) {
            $error = $response->json('message') ?? "HTTP {$response->status()}";
            throw new RuntimeException("GitHub API error: {$error}");
        }

        return $response;
    }
}
