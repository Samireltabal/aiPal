<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

class SwitchContextTool extends AiTool
{
    public function __construct(
        private readonly User $user,
    ) {}

    public static function toolName(): string
    {
        return 'switch_context';
    }

    public static function toolLabel(): string
    {
        return 'Switch Context';
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
        $names = $this->user->contexts()
            ->whereNull('archived_at')
            ->pluck('name')
            ->all();

        $list = empty($names) ? 'Personal' : implode(', ', $names);

        return 'Switch the active context for the rest of this conversation. '
            ."Available contexts: {$list}. "
            .'Use this when the user says "switch to work", "in my freelance context", "use Acme account", etc. '
            .'After switching, integration tools (gmail, calendar, github, gitlab, jira) automatically use that context\'s connections.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'context' => $schema->string()
                ->description('Context name, slug, or kind (work/freelance/personal). Case-insensitive. Example: "Acme", "work", "phoenix".')
                ->required(),
        ];
    }

    protected function execute(Request $request): Stringable|string
    {
        $hint = (string) $request['context'];

        $context = $this->user->findContextByHint($hint);

        if ($context === null) {
            $available = $this->user->contexts()
                ->whereNull('archived_at')
                ->pluck('name')
                ->all();

            return "No context found matching \"{$hint}\". Available: ".(empty($available) ? 'Personal (default)' : implode(', ', $available)).'.';
        }

        $this->user->setActiveContext($context);
        $this->user->markPendingContextSwitch($context);

        return "Switched to {$context->name} context. New tasks, reminders, and integration calls will use this context until you switch again.";
    }
}
