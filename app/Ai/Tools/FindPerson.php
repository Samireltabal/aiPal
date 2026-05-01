<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
use Stringable;

class FindPerson extends AiTool
{
    public function __construct(private readonly User $user) {}

    public static function toolName(): string
    {
        return 'find_person';
    }

    public static function toolLabel(): string
    {
        return 'Find Person';
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
        return "Find a person in the user's CRM by name, email, or phone. "
            .'Returns up to 5 matches with last-contact date. Use this BEFORE creating a new person to avoid duplicates.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Search query — full or partial name, email, or phone.')
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $query = trim((string) ($request['query'] ?? ''));
        if ($query === '') {
            return 'Provide a search query.';
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], strtolower($query)).'%';
        $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $people = Person::query()
            ->where('user_id', $this->user->id)
            ->where(function ($w) use ($like, $likeOp) {
                $w->whereRaw('LOWER(display_name) '.$likeOp.' ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(company, \'\')) '.$likeOp.' ?', [$like])
                    ->orWhereExists(function ($s) use ($like, $likeOp) {
                        $s->select(DB::raw(1))->from('person_emails')
                            ->whereColumn('person_emails.person_id', 'people.id')
                            ->whereRaw('LOWER(email) '.$likeOp.' ?', [$like]);
                    })
                    ->orWhereExists(function ($s) use ($like, $likeOp) {
                        $s->select(DB::raw(1))->from('person_phones')
                            ->whereColumn('person_phones.person_id', 'people.id')
                            ->whereRaw('LOWER(phone) '.$likeOp.' ?', [$like]);
                    });
            })
            ->orderByDesc('last_contact_at')
            ->limit(5)
            ->get();

        if ($people->isEmpty()) {
            return "No people matching '{$query}'.";
        }

        return $people->map(function (Person $p) {
            $email = $p->primaryEmail() ?? '—';
            $last = $p->last_contact_at?->diffForHumans() ?? 'never';
            $company = $p->company !== null ? " ({$p->company})" : '';

            return "#{$p->id} {$p->display_name}{$company} <{$email}> — last contact: {$last}";
        })->join("\n");
    }
}
