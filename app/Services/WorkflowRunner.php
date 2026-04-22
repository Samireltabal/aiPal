<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\Chat\ChatAgent;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\Workflow\WorkflowDeliveryDispatcher;
use Illuminate\Support\Facades\Log;
use Throwable;

class WorkflowRunner
{
    public function __construct(
        private readonly WorkflowDeliveryDispatcher $delivery,
    ) {}

    /**
     * @param  array<string, mixed>|null  $triggerPayload
     */
    public function run(Workflow $workflow, string $triggeredBy, ?array $triggerPayload = null): WorkflowRun
    {
        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'triggered_by' => $triggeredBy,
            'trigger_payload' => $triggerPayload,
            'started_at' => now(),
        ]);

        $startedAt = microtime(true);

        try {
            $prompt = $this->buildPrompt($workflow, $triggerPayload);

            $agent = (new ChatAgent)
                ->withUser($workflow->user)
                ->withSystemPrompt('You are executing a scheduled workflow for the user. Use the available tools as needed and produce a concise, useful final output.')
                ->withToolNames($workflow->enabled_tool_names ?? []);

            $response = $agent->forUser($workflow->user)->prompt($prompt);
            $output = (string) $response;

            $this->delivery->deliver($workflow, $output);

            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

            $run->update([
                'status' => 'success',
                'output' => $output,
                'duration_ms' => $durationMs,
                'finished_at' => now(),
            ]);

            $workflow->update(['last_run_at' => now()]);

            return $run->fresh();
        } catch (Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

            Log::warning('Workflow run failed', [
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage(),
            ]);

            $run->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'finished_at' => now(),
            ]);

            return $run->fresh();
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function buildPrompt(Workflow $workflow, ?array $payload): string
    {
        $prompt = $workflow->prompt;

        if ($payload !== null) {
            $prompt .= "\n\n---\nTrigger payload (JSON):\n".json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return $prompt;
    }
}
