<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Person;
use App\Models\User;
use App\Modules\People\Services\ContactStaleness;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class FindStaleContacts extends AiTool
{
    public function __construct(
        private readonly User $user,
        private readonly ContactStaleness $staleness,
    ) {}

    public static function toolName(): string
    {
        return 'find_stale_contacts';
    }

    public static function toolLabel(): string
    {
        return 'Find Stale Contacts';
    }

    public static function toolCategory(): string
    {
        return 'crm';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Find people you have not contacted recently (default: 90 days). '
            .'Useful for follow-up suggestions and "who should I reach out to?".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()
                ->description('Threshold in days (default 90). People with no last_contact older than this are returned.')
                ->nullable()
                ->required(),
            'limit' => $schema->integer()
                ->description('Max results (default 10, max 25).')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $days = $request['days'] !== null ? (int) $request['days'] : null;
        $limit = max(1, min(25, (int) ($request['limit'] ?? 10)));

        $people = $this->staleness->query($this->user, $days)->limit($limit)->get();

        if ($people->isEmpty()) {
            return 'No stale contacts — everyone has been touched recently.';
        }

        return $people->map(function (Person $p) {
            $last = $p->last_contact_at?->diffForHumans() ?? 'never';

            return "#{$p->id} {$p->display_name} — last contact: {$last}";
        })->join("\n");
    }
}
