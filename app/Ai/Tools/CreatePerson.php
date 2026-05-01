<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Person;
use App\Models\PersonEmail;
use App\Models\PersonPhone;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreatePerson extends AiTool
{
    public function __construct(private readonly User $user) {}

    public static function toolName(): string
    {
        return 'create_person';
    }

    public static function toolLabel(): string
    {
        return 'Create Person';
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
        return 'Create a new person in the CRM. Always call find_person first to avoid duplicates. '
            .'Use when the user explicitly says they want to add or save someone.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'display_name' => $schema->string()->description('Full name as it should appear.')->required(),
            'company' => $schema->string()->description('Company / org name.')->nullable()->required(),
            'title' => $schema->string()->description('Job title.')->nullable()->required(),
            'email' => $schema->string()->description('Primary email address.')->nullable()->required(),
            'phone' => $schema->string()->description('Primary phone in any format.')->nullable()->required(),
            'tags' => $schema->array()
                ->items($schema->string())
                ->description('Free-form tags (e.g. "client", "vc", "friend").')
                ->nullable()
                ->required(),
            'notes' => $schema->string()->description('Free-form notes.')->nullable()->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $name = trim((string) ($request['display_name'] ?? ''));
        if ($name === '') {
            return 'display_name is required.';
        }

        $turnCount = $this->user->incrementCreatedRecordsThisTurn();
        if ($turnCount > 3) {
            return 'Skipped: too many records being created in one turn. Ask the user to confirm before creating more.';
        }

        $person = DB::transaction(function () use ($request, $name): Person {
            $person = Person::create([
                'user_id' => $this->user->id,
                'context_id' => $this->user->currentContext()?->id ?? $this->user->defaultContext()?->id,
                'display_name' => $name,
                'company' => $request['company'] ?? null,
                'title' => $request['title'] ?? null,
                'notes' => $request['notes'] ?? null,
                'tags' => $request['tags'] ?? [],
                'custom' => [],
            ]);

            if (! empty($request['email'])) {
                PersonEmail::firstOrCreate(
                    ['user_id' => $this->user->id, 'email' => strtolower((string) $request['email'])],
                    ['person_id' => $person->id, 'is_primary' => true],
                );
            }
            if (! empty($request['phone'])) {
                PersonPhone::firstOrCreate(
                    ['user_id' => $this->user->id, 'phone' => (string) $request['phone']],
                    ['person_id' => $person->id, 'is_primary' => true],
                );
            }

            return $person;
        });

        return "Created person #{$person->id} ({$person->display_name}).";
    }
}
