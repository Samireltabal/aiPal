<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Jobs\RunWorkflowJob;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class RunWorkflowTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'run_workflow';
    }

    public static function toolLabel(): string
    {
        return 'Run a Saved Workflow';
    }

    public static function toolCategory(): string
    {
        return 'workflows';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Run one of the user\'s saved workflows by name. Use this when the user asks to run, execute, or trigger a named automation (e.g. "run my morning brief", "execute weekly review"). The workflow runs asynchronously; you acknowledge that it started.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The name of the saved workflow to run (case-insensitive partial match is acceptable).')
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $name = trim((string) $request['name']);

        if ($name === '') {
            return 'Please specify a workflow name.';
        }

        $needle = strtolower($name);

        $candidates = Workflow::query()
            ->where('user_id', $this->user->id)
            ->where('enabled', true)
            ->whereRaw('LOWER(name) LIKE ?', ['%'.$needle.'%'])
            ->get();

        $workflow = $candidates->firstWhere(fn ($w) => strtolower($w->name) === $needle);

        if (! $workflow && $candidates->count() === 1) {
            $workflow = $candidates->first();
        }

        if (! $workflow) {
            if ($candidates->count() > 1) {
                $names = $candidates->pluck('name')->implode(', ');

                return "Multiple workflows match \"{$name}\": {$names}. Please specify the exact name.";
            }

            $available = Workflow::query()
                ->where('user_id', $this->user->id)
                ->where('enabled', true)
                ->pluck('name')
                ->implode(', ');

            if ($available === '') {
                return 'No enabled workflows found. Create one at /workflows first.';
            }

            return "No workflow named \"{$name}\" found. Available workflows: {$available}";
        }

        RunWorkflowJob::dispatch($workflow->id, 'manual');

        return "Workflow \"{$workflow->name}\" has been dispatched. Results will be delivered via {$workflow->delivery_channel}.";
    }
}
