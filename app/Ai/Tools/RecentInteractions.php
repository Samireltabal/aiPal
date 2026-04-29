<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Interaction;
use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class RecentInteractions extends AiTool
{
    public function __construct(private readonly User $user) {}

    public static function toolName(): string
    {
        return 'recent_interactions';
    }

    public static function toolLabel(): string
    {
        return 'Recent Interactions';
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
        return 'Get recent interactions, optionally scoped to one person. '
            .'Use to answer "when did I last talk to X?" or "what did Sara say about the proposal?".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'person_id' => $schema->integer()
                ->description('Optional person ID. Pass null for all people.')
                ->nullable()
                ->required(),
            'limit' => $schema->integer()
                ->description('Max results, default 10, capped at 25.')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $personId = $request['person_id'] ?? null;
        $limit = max(1, min(25, (int) ($request['limit'] ?? 10)));

        $query = Interaction::query()
            ->where('user_id', $this->user->id)
            ->orderByDesc('occurred_at')
            ->limit($limit);

        if ($personId !== null) {
            $person = Person::query()->where('user_id', $this->user->id)->find($personId);
            if ($person === null) {
                return "No person #{$personId} found.";
            }
            $query->where('person_id', $personId);
        }

        $interactions = $query->with('person:id,display_name')->get();

        if ($interactions->isEmpty()) {
            return $personId !== null ? 'No interactions logged with this person.' : 'No interactions logged.';
        }

        return $interactions->map(function (Interaction $i) use ($personId) {
            $when = $i->occurred_at?->diffForHumans() ?? '?';
            $who = $personId === null ? "{$i->person?->display_name} — " : '';
            $summary = $i->summary ?: $i->subject ?: '(no summary)';
            $arrow = match ($i->direction) {
                'inbound' => '←',
                'outbound' => '→',
                default => '·',
            };

            return "{$when} {$arrow} [{$i->channel}] {$who}{$summary}";
        })->join("\n");
    }
}
