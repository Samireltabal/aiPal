<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Note;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateNote extends AiTool
{
    public function __construct(
        private readonly User $user,
        private readonly EmbeddingService $embeddings,
    ) {}

    public static function toolName(): string
    {
        return 'create_note';
    }

    public static function toolLabel(): string
    {
        return 'Create Note';
    }

    public static function toolCategory(): string
    {
        return 'productivity';
    }

    protected function userId(): ?int
    {
        return $this->user->id;
    }

    public function description(): Stringable|string
    {
        return 'Create a new note for the user. Use when the user asks to save, write down, or note something.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('Optional short title for the note. Pass null if not provided.')
                ->nullable()
                ->required(),
            'content' => $schema->string()
                ->description('The full content of the note.')
                ->required(),
        ];
    }

    private const MAX_RECORDS_PER_TURN = 3;

    protected function execute(Request $request): Stringable|string
    {
        if ($this->user->createdRecordsThisTurn() >= self::MAX_RECORDS_PER_TURN) {
            return 'GUARDRAIL: You have already created '.$this->user->createdRecordsThisTurn().' records in this turn. '
                .'Stop and ask the user to confirm before creating more.';
        }

        $title = $request['title'] ?? null;
        $content = $request['content'];

        $embedding = $this->embeddings->embedText($content);

        $note = Note::create([
            'user_id' => $this->user->id,
            'context_id' => $this->user->currentContext()?->id,
            'title' => $title,
            'content' => $content,
            'embedding' => $embedding,
        ]);

        $this->user->incrementCreatedRecordsThisTurn();

        return 'Note saved'.(($title !== null && $title !== '') ? " as \"{$title}\"" : '').". (ID: {$note->id})";
    }
}
