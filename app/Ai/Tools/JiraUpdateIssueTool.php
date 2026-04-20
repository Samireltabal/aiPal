<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\JiraService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class JiraUpdateIssueTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'jira_update_issue';
    }

    public static function toolLabel(): string
    {
        return 'Jira: Update / Transition Issue';
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
        return 'Update a Jira issue\'s status (transition), summary, or priority. Use when the user says "move ticket X to In Progress", "close PROJ-123", "change priority of PROJ-456 to High", or similar.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'issue_key' => $schema->string()
                ->description('Jira issue key, e.g. "PROJ-123".')
                ->required(),
            'transition_name' => $schema->string()
                ->description('Name of the transition/status to move to, e.g. "In Progress", "Done", "To Do", "In Review". Pass null to skip.')
                ->nullable()
                ->required(),
            'summary' => $schema->string()
                ->description('New summary/title for the issue. Pass null to keep current.')
                ->nullable()
                ->required(),
            'priority' => $schema->string()
                ->description('New priority: "Highest", "High", "Medium", "Low", "Lowest". Pass null to keep current.')
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

        $issueKey = $request['issue_key'];
        $actions = [];

        try {
            $jira = new JiraService($this->user);

            $fields = [];

            if (! empty($request['summary'])) {
                $fields['summary'] = $request['summary'];
            }

            if (! empty($request['priority'])) {
                $fields['priority'] = ['name' => $request['priority']];
            }

            if (! empty($fields)) {
                $jira->updateIssue($issueKey, $fields);
                if (isset($fields['summary'])) {
                    $actions[] = "summary updated to \"{$fields['summary']}\"";
                }
                if (isset($fields['priority'])) {
                    $actions[] = "priority set to {$request['priority']}";
                }
            }

            if (! empty($request['transition_name'])) {
                $transitions = $jira->getTransitions($issueKey);
                $match = collect($transitions)->first(
                    fn ($t) => str_contains(strtolower($t['name']), strtolower($request['transition_name']))
                );

                if ($match === null) {
                    $available = implode(', ', array_column($transitions, 'name'));

                    return "Could not find transition \"{$request['transition_name']}\" for {$issueKey}. Available: {$available}";
                }

                $jira->transitionIssue($issueKey, $match['id']);
                $actions[] = "status → {$match['name']}";
            }
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        if (empty($actions)) {
            return "No changes applied to {$issueKey} — please specify a transition, summary, or priority.";
        }

        return "{$issueKey} updated: ".implode(', ', $actions).'.';
    }
}
