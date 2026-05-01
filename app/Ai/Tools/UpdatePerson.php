<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdatePerson extends AiTool
{
    public function __construct(private readonly User $user) {}

    public static function toolName(): string
    {
        return 'update_person';
    }

    public static function toolLabel(): string
    {
        return 'Update Person';
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
        return 'Update fields on an existing person. Pass only the fields you want to change. '
            .'For tags, the array fully replaces the existing tags; pass the merged set if you only want to add.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'person_id' => $schema->integer()->description('The person ID to update.')->required(),
            'display_name' => $schema->string()->nullable()->required(),
            'company' => $schema->string()->nullable()->required(),
            'title' => $schema->string()->nullable()->required(),
            'notes' => $schema->string()->nullable()->required(),
            'tags' => $schema->array()->items($schema->string())->nullable()->required(),
            'birthday' => $schema->string()->description('YYYY-MM-DD or null.')->nullable()->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $id = (int) ($request['person_id'] ?? 0);
        $person = Person::query()->where('user_id', $this->user->id)->find($id);
        if ($person === null) {
            return "No person #{$id} found.";
        }

        $updates = array_filter([
            'display_name' => $request['display_name'] ?? null,
            'company' => $request['company'] ?? null,
            'title' => $request['title'] ?? null,
            'notes' => $request['notes'] ?? null,
            'tags' => $request['tags'] ?? null,
            'birthday' => $request['birthday'] ?? null,
        ], fn ($v) => $v !== null);

        if (empty($updates)) {
            return 'No fields provided to update.';
        }

        $person->update($updates);

        return "Updated person #{$person->id}.";
    }
}
