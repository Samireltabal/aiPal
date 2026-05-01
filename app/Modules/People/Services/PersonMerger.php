<?php

declare(strict_types=1);

namespace App\Modules\People\Services;

use App\Models\Interaction;
use App\Models\Person;
use App\Models\PersonEmail;
use App\Models\PersonPhone;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PersonMerger
{
    /**
     * Merge $duplicate into $primary, then soft-delete $duplicate.
     * - Both rows must belong to the same user.
     * - Scalar fields on $primary are kept; missing fields backfilled from $duplicate.
     * - Tags are union-merged.
     * - Emails/phones/interactions are reassigned to $primary.
     * - last_contact_at = max of both.
     * - Returns the refreshed $primary.
     */
    public function merge(Person $primary, Person $duplicate): Person
    {
        if ($primary->id === $duplicate->id) {
            throw new InvalidArgumentException('Cannot merge a person into itself.');
        }

        if ($primary->user_id !== $duplicate->user_id) {
            throw new InvalidArgumentException('Cannot merge people belonging to different users.');
        }

        return DB::transaction(function () use ($primary, $duplicate): Person {
            $updates = [];

            foreach (['company', 'title', 'notes', 'birthday', 'photo_url'] as $field) {
                if (blank($primary->{$field}) && filled($duplicate->{$field})) {
                    $updates[$field] = $duplicate->{$field};
                }
            }

            $primaryTags = $primary->tags ?? [];
            $duplicateTags = $duplicate->tags ?? [];
            $mergedTags = array_values(array_unique(array_merge($primaryTags, $duplicateTags)));
            if ($mergedTags !== $primaryTags) {
                $updates['tags'] = $mergedTags;
            }

            $primaryCustom = $primary->custom ?? [];
            $duplicateCustom = $duplicate->custom ?? [];
            $mergedCustom = array_merge($duplicateCustom, $primaryCustom);
            if ($mergedCustom !== $primaryCustom) {
                $updates['custom'] = $mergedCustom;
            }

            $latestContact = collect([$primary->last_contact_at, $duplicate->last_contact_at])
                ->filter()
                ->max();
            if ($latestContact !== null && (
                $primary->last_contact_at === null
                || $latestContact->greaterThan($primary->last_contact_at)
            )) {
                $updates['last_contact_at'] = $latestContact;
            }

            if ($updates !== []) {
                $primary->update($updates);
            }

            $primaryEmailSet = PersonEmail::query()
                ->where('person_id', $primary->id)
                ->pluck('email')
                ->all();
            PersonEmail::query()
                ->where('person_id', $duplicate->id)
                ->whereNotIn('email', $primaryEmailSet)
                ->update(['person_id' => $primary->id, 'is_primary' => false]);
            PersonEmail::query()->where('person_id', $duplicate->id)->delete();

            $primaryPhoneSet = PersonPhone::query()
                ->where('person_id', $primary->id)
                ->pluck('phone')
                ->all();
            PersonPhone::query()
                ->where('person_id', $duplicate->id)
                ->whereNotIn('phone', $primaryPhoneSet)
                ->update(['person_id' => $primary->id, 'is_primary' => false]);
            PersonPhone::query()->where('person_id', $duplicate->id)->delete();

            Interaction::query()
                ->where('person_id', $duplicate->id)
                ->update(['person_id' => $primary->id]);

            $duplicate->delete();

            return $primary->fresh(['emails', 'phones']);
        });
    }
}
