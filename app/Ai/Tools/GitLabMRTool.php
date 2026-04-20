<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\GitLabService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class GitLabMRTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'gitlab_merge_requests';
    }

    public static function toolLabel(): string
    {
        return 'GitLab: Merge Requests';
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
        return 'List or summarize GitLab merge requests. Use when asked about open MRs, pull requests, code reviews, or merge requests assigned to the user or in a specific project.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_path' => $schema->string()
                ->description('GitLab project path, e.g. "mygroup/myrepo". Pass null to list MRs across all assigned projects.')
                ->nullable()
                ->required(),
            'state' => $schema->string()
                ->description('MR state: "opened", "closed", "merged", or "all". Defaults to "opened".')
                ->enum(['opened', 'closed', 'merged', 'all'])
                ->nullable()
                ->required(),
            'max_results' => $schema->integer()
                ->description('Maximum number of MRs to return (1–20). Defaults to 10.')
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
            $mrs = $gitlab->listMergeRequests(
                projectPath: $request['project_path'] ?? null,
                state: $request['state'] ?? 'opened',
                perPage: $request['max_results'] ?? 10,
            );
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        if (empty($mrs)) {
            $state = $request['state'] ?? 'opened';

            return "No {$state} merge requests found.";
        }

        $lines = array_map(function (array $mr): string {
            $project = $mr['references']['full'] ?? $mr['web_url'];
            $author = $mr['author']['name'] ?? 'Unknown';
            $draft = ($mr['draft'] ?? false) ? ' [Draft]' : '';
            $conflicts = ($mr['has_conflicts'] ?? false) ? ' ⚠ conflicts' : '';

            return "• !{$mr['iid']}{$draft}: {$mr['title']} — {$mr['state']} | {$author} | {$project}{$conflicts}";
        }, $mrs);

        return 'Merge requests ('.count($mrs)."):\n".implode("\n", $lines);
    }
}
