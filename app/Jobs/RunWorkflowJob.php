<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Workflow;
use App\Services\WorkflowRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunWorkflowJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    /**
     * @param  array<string, mixed>|null  $triggerPayload
     */
    public function __construct(
        public readonly int $workflowId,
        public readonly string $triggeredBy,
        public readonly ?array $triggerPayload = null,
    ) {}

    public function handle(WorkflowRunner $runner): void
    {
        $workflow = Workflow::find($this->workflowId);

        if (! $workflow || ! $workflow->enabled) {
            return;
        }

        $runner->run($workflow, $this->triggeredBy, $this->triggerPayload);
    }
}
