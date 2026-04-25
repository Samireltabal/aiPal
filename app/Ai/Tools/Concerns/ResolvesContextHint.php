<?php

declare(strict_types=1);

namespace App\Ai\Tools\Concerns;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

/**
 * Adds a uniform optional `context` argument to integration tools so the LLM
 * can scope a single call ("read my work inbox") without changing the
 * conversation's persisted context.
 *
 * @property User $user
 */
trait ResolvesContextHint
{
    /**
     * Schema fragment to merge into a tool's `schema()` array.
     *
     * @return array<string, mixed>
     */
    protected function contextSchema(JsonSchema $schema): array
    {
        return [
            'context' => $schema->string()
                ->description('Optional context name/slug to scope this call (e.g. "work", "Acme", "personal"). Omit to use the current conversation context.')
                ->nullable()
                ->required(),
        ];
    }

    /**
     * Run $fn with the requested context active. Falls back to no-op when
     * the hint is empty or doesn't resolve to a known context.
     */
    protected function withRequestedContext(Request $request, callable $fn): mixed
    {
        $hint = $request['context'] ?? null;
        $context = ($hint !== null && trim((string) $hint) !== '')
            ? $this->user->findContextByHint((string) $hint)
            : null;

        return $this->user->withActiveContext($context, $fn);
    }
}
