<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Interaction;
use App\Models\Person;
use App\Models\User;
use App\Modules\People\Services\InteractionRecorder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class LogInteraction extends AiTool
{
    public function __construct(
        private readonly User $user,
        private readonly InteractionRecorder $recorder,
    ) {}

    public static function toolName(): string
    {
        return 'log_interaction';
    }

    public static function toolLabel(): string
    {
        return 'Log Interaction';
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
        return 'Log an interaction with a person — meeting, manual note, or any touch the user mentions. '
            .'Updates last_contact_at automatically.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'person_id' => $schema->integer()->description('The person ID.')->required(),
            'channel' => $schema->string()
                ->enum(Interaction::CHANNELS)
                ->description('Channel: meeting, note, email, whatsapp, telegram, chat.')
                ->required(),
            'direction' => $schema->string()
                ->enum(Interaction::DIRECTIONS)
                ->description('inbound | outbound | none.')
                ->nullable()
                ->required(),
            'subject' => $schema->string()->description('Short subject/title.')->nullable()->required(),
            'summary' => $schema->string()->description('What happened, in one or two sentences.')->required(),
            'occurred_at' => $schema->string()
                ->description('ISO 8601 timestamp of when this happened. Defaults to now.')
                ->nullable()
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $person = Person::query()->where('user_id', $this->user->id)->find((int) ($request['person_id'] ?? 0));
        if ($person === null) {
            return 'Person not found.';
        }

        $turnCount = $this->user->incrementCreatedRecordsThisTurn();
        if ($turnCount > 3) {
            return 'Skipped: too many records being created in one turn.';
        }

        $interaction = $this->recorder->record($person, [
            'channel' => (string) $request['channel'],
            'direction' => $request['direction'] ?? Interaction::DIRECTION_NONE,
            'subject' => $request['subject'] ?? null,
            'summary' => (string) $request['summary'],
            'occurred_at' => $request['occurred_at'] ?? null,
        ]);

        return "Logged interaction #{$interaction->id} for {$person->display_name}.";
    }
}
