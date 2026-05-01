<?php

declare(strict_types=1);

namespace App\Modules\People\Services;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * "Who haven't I talked to in a while?" query helper.
 *
 * Surfaced via:
 *   - dashboard widget ("X people you haven't contacted in 90 days")
 *   - find_stale_contacts AI tool
 *   - /people page filter chip
 */
class ContactStaleness
{
    /**
     * @return Builder<Person>
     */
    public function query(User $user, ?int $days = null): Builder
    {
        $threshold = Carbon::now()->subDays($days ?? (int) config('people.staleness_days', 90));

        return Person::query()
            ->where('user_id', $user->id)
            ->where(function (Builder $q) use ($threshold): void {
                $q->whereNull('last_contact_at')
                    ->orWhere('last_contact_at', '<', $threshold);
            })
            ->orderByRaw('last_contact_at IS NULL DESC')
            ->orderBy('last_contact_at', 'asc');
    }

    public function count(User $user, ?int $days = null): int
    {
        return $this->query($user, $days)->count();
    }
}
