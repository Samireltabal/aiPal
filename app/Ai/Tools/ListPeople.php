<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListPeople extends AiTool
{
    public function __construct(private readonly User $user) {}

    public static function toolName(): string
    {
        return 'list_people';
    }

    public static function toolLabel(): string
    {
        return 'List People';
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
        return "List people from the user's CRM, optionally filtered by tag or recency. "
            .'Returns up to 25 entries.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tag' => $schema->string()
                ->description('Filter by tag (case-sensitive). Pass null for no tag filter.')
                ->nullable()
                ->required(),
            'recent_only' => $schema->boolean()
                ->description('If true, only people with last_contact_at within the last 30 days. Defaults to false.')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $tag = $request['tag'] ?? null;
        $recentOnly = (bool) ($request['recent_only'] ?? false);

        $query = Person::query()->where('user_id', $this->user->id);

        if ($tag !== null && $tag !== '') {
            $query->whereJsonContains('tags', $tag);
        }
        if ($recentOnly) {
            $query->where('last_contact_at', '>=', now()->subDays(30));
        }

        $people = $query->orderByDesc('last_contact_at')->limit(25)->get();

        if ($people->isEmpty()) {
            return 'No people match.';
        }

        return $people->map(function (Person $p) {
            $email = $p->primaryEmail() ?? '—';
            $last = $p->last_contact_at?->diffForHumans() ?? 'never';

            return "#{$p->id} {$p->display_name} <{$email}> — {$last}";
        })->join("\n");
    }
}
