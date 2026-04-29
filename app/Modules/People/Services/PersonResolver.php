<?php

declare(strict_types=1);

namespace App\Modules\People\Services;

use App\Models\Person;
use App\Models\PersonEmail;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Find-or-create a Person from a sender/recipient identifier.
 *
 * Why a dedicated service:
 *   - inbound email and outbound Gmail capture should produce identical
 *     dedup behavior, so the lookup logic must live in one place
 *   - transactional senders (noreply@, mailgun, etc) must NEVER create
 *     a Person row — that exclusion is centralized here
 *   - context attribution (which Context owns a new person) is shared
 *     across call sites
 */
class PersonResolver
{
    public function __construct() {}

    /**
     * Returns null if the address is missing, malformed, or transactional.
     * Otherwise returns the Person — either an existing one or a freshly
     * created stub with display_name + primary email seeded.
     */
    public function fromEmail(User $user, ?string $rawFrom, ?int $contextId = null): ?Person
    {
        $parsed = EmailAddress::parse($rawFrom);
        if ($parsed === null || $parsed->isTransactional()) {
            return null;
        }

        return DB::transaction(function () use ($user, $parsed, $contextId): Person {
            $existing = PersonEmail::query()
                ->where('user_id', $user->id)
                ->where('email', $parsed->address)
                ->first();

            if ($existing !== null) {
                return $existing->person()->withTrashed()->first()
                    ?? $this->createPerson($user, $parsed, $contextId);
            }

            $person = $this->createPerson($user, $parsed, $contextId);

            PersonEmail::create([
                'person_id' => $person->id,
                'user_id' => $user->id,
                'email' => $parsed->address,
                'is_primary' => true,
            ]);

            return $person;
        });
    }

    private function createPerson(User $user, EmailAddress $parsed, ?int $contextId): Person
    {
        return Person::create([
            'user_id' => $user->id,
            'context_id' => $contextId ?? $user->currentContext()?->id ?? $user->defaultContext()?->id,
            'display_name' => $parsed->displayName ?: $parsed->fallbackDisplayName(),
        ]);
    }
}
