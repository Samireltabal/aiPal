<?php

declare(strict_types=1);

namespace App\Modules\People\Services;

use App\Jobs\SummarizeInteractionJob;
use App\Models\Interaction;
use App\Models\Person;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Records an interaction for a person and keeps person.last_contact_at in
 * sync. Centralizing this matters because:
 *
 *   - last_contact_at must use max(current, occurred_at) so a back-dated
 *     manual note never moves the value backwards in time
 *   - dedup on (user_id, channel, external_id) lives at the DB level, but
 *     the caller wants a no-op return rather than a unique-violation when
 *     the same email is forwarded twice
 *   - kicking off the LLM-summary job belongs here so every recorded
 *     interaction goes through the same pipeline
 */
class InteractionRecorder
{
    /**
     * @param  array{
     *   channel: string,
     *   direction?: ?string,
     *   occurred_at?: \DateTimeInterface|string|null,
     *   subject?: ?string,
     *   summary?: ?string,
     *   raw_excerpt?: ?string,
     *   metadata?: array<string, mixed>,
     *   external_id?: ?string,
     * }  $payload
     */
    public function record(Person $person, array $payload): ?Interaction
    {
        $occurredAt = isset($payload['occurred_at'])
            ? Carbon::parse($payload['occurred_at'])
            : Carbon::now();

        $externalId = $payload['external_id'] ?? null;

        return DB::transaction(function () use ($person, $payload, $occurredAt, $externalId): ?Interaction {
            // Idempotency: same (user, channel, external_id) → return existing.
            if ($externalId !== null) {
                $existing = Interaction::query()
                    ->where('user_id', $person->user_id)
                    ->where('channel', $payload['channel'])
                    ->where('external_id', $externalId)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $rawExcerpt = $payload['raw_excerpt'] ?? null;
            if ($rawExcerpt !== null) {
                $rawExcerpt = mb_substr($rawExcerpt, 0, 2000);
            }

            $interaction = Interaction::create([
                'person_id' => $person->id,
                'user_id' => $person->user_id,
                'context_id' => $person->context_id,
                'channel' => $payload['channel'],
                'direction' => $payload['direction'] ?? Interaction::DIRECTION_NONE,
                'occurred_at' => $occurredAt,
                'subject' => $payload['subject'] ?? null,
                'summary' => $payload['summary'] ?? null,
                'raw_excerpt' => $rawExcerpt,
                'metadata' => $payload['metadata'] ?? [],
                'external_id' => $externalId,
            ]);

            // Use max() so back-dated manual notes don't overwrite a more
            // recent inbound email from the same person.
            $newLastContact = $person->last_contact_at === null
                ? $occurredAt
                : ($occurredAt->greaterThan($person->last_contact_at) ? $occurredAt : $person->last_contact_at);

            if ($person->last_contact_at?->equalTo($newLastContact) !== true) {
                $person->update(['last_contact_at' => $newLastContact]);
            }

            // Queue an LLM summary unless the caller already supplied one.
            if (
                ($payload['summary'] ?? null) === null
                && $rawExcerpt !== null
                && config('people.summarize.enabled')
            ) {
                SummarizeInteractionJob::dispatch($interaction->id);
            }

            return $interaction;
        });
    }
}
