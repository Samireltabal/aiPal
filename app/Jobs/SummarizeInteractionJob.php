<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\People\InteractionSummaryAgent;
use App\Models\Interaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generate a concise one-line summary for an interaction's raw_excerpt.
 *
 * Runs on the queue so inbound webhooks stay fast. Uses a configurable
 * cheap model (PEOPLE_SUMMARIZE_PROVIDER / _MODEL); on any failure we
 * fall back to the first ~280 chars of the raw excerpt — not great, but
 * never blocks the timeline UI.
 */
class SummarizeInteractionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(public readonly int $interactionId) {}

    public function handle(): void
    {
        $interaction = Interaction::find($this->interactionId);
        if ($interaction === null || $interaction->summary !== null) {
            return;
        }

        $excerpt = (string) $interaction->raw_excerpt;
        if ($excerpt === '') {
            return;
        }

        $maxInput = (int) config('people.summarize.max_input_chars', 8000);
        $maxOut = (int) config('people.summarize.max_summary_chars', 280);
        $excerpt = mb_substr($excerpt, 0, $maxInput);

        $summary = $this->llmSummary($excerpt, $interaction->subject)
            ?? $this->truncatedFallback($excerpt, $maxOut);

        $interaction->update(['summary' => mb_substr($summary, 0, $maxOut)]);
    }

    private function llmSummary(string $excerpt, ?string $subject): ?string
    {
        try {
            $provider = config('people.summarize.provider') ?: null;
            $model = config('people.summarize.model') ?: null;

            $input = ($subject !== null ? "Subject: {$subject}\n\n" : '')."Body:\n".$excerpt;

            $response = (new InteractionSummaryAgent)->prompt(
                $input,
                provider: $provider,
                model: $model,
            );

            $text = trim((string) $response);

            return $text !== '' ? $text : null;
        } catch (Throwable $e) {
            Log::warning('SummarizeInteractionJob: LLM call failed', [
                'interaction_id' => $this->interactionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function truncatedFallback(string $excerpt, int $max): string
    {
        $oneLine = preg_replace('/\s+/', ' ', $excerpt) ?? $excerpt;

        return trim(mb_substr($oneLine, 0, $max));
    }
}
