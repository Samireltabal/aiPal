<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RunWorkflowJob;
use App\Models\Workflow;
use Cron\CronExpression;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('workflows:dispatch-due')]
#[Description('Dispatch scheduled workflows whose cron expression is due this minute')]
class DispatchDueWorkflows extends Command
{
    public function handle(): int
    {
        $now = now();
        $dispatched = 0;

        Workflow::query()
            ->where('trigger_type', 'schedule')
            ->where('enabled', true)
            ->whereNotNull('cron_expression')
            ->chunkById(100, function ($workflows) use ($now, &$dispatched): void {
                foreach ($workflows as $workflow) {
                    try {
                        $cron = new CronExpression($workflow->cron_expression);

                        if ($cron->isDue($now)) {
                            RunWorkflowJob::dispatch($workflow->id, 'schedule');
                            $dispatched++;
                        }
                    } catch (Throwable $e) {
                        $this->warn("Invalid cron for workflow #{$workflow->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("Dispatched {$dispatched} workflow(s).");

        return self::SUCCESS;
    }
}
