<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Connection;
use App\Models\User;

/**
 * Builds the system-prompt fragment that lists the user's contexts and the
 * integration accounts attached to each. Lets the LLM pick the right
 * provider tool (Gmail vs Outlook, Google Calendar vs Microsoft Calendar)
 * for a given context.
 */
class ContextHintBuilder
{
    public function build(User $user): string
    {
        $contexts = $user->contexts()
            ->whereNull('archived_at')
            ->with(['connections' => fn ($q) => $q->where('enabled', true)])
            ->orderByDesc('is_default')
            ->get();

        if ($contexts->count() < 2) {
            return '';
        }

        $list = $contexts->map(function ($ctx): string {
            $tag = $ctx->is_default ? ' (default)' : '';
            $providers = $ctx->connections
                ->map(fn (Connection $c) => $this->describeConnection($c))
                ->filter()
                ->unique()
                ->values()
                ->implode(', ');
            $providerNote = $providers !== '' ? " — accounts: {$providers}" : ' — no integrations attached';

            return "- {$ctx->name} [{$ctx->kind}]{$tag}{$providerNote}";
        })->implode("\n");

        $active = $user->currentContext();
        $activeNote = $active ? "Currently active: {$active->name}." : '';

        return "\n\nThe user has multiple contexts. Each context has its own set of integration accounts; pick the right tool for the right context:\n{$list}\n{$activeNote}\n"
            ."Tool selection rules:\n"
            ."- For mail/calendar in a context with a Microsoft account, use outlook / outlook_calendar (NOT gmail / google_calendar).\n"
            ."- For mail/calendar in a context with a Google account, use gmail / google_calendar (NOT outlook).\n"
            ."- If the user names a context the active context doesn't include, pass `context: \"name\"` to the tool call.\n"
            .'When the user wants to switch contexts for the rest of the conversation, call switch_context with the name.';
    }

    private function describeConnection(Connection $connection): ?string
    {
        return match ($connection->provider) {
            Connection::PROVIDER_GOOGLE => 'Google (Gmail + Calendar)',
            Connection::PROVIDER_MICROSOFT => 'Microsoft (Outlook + Calendar)',
            Connection::PROVIDER_GITHUB => 'GitHub',
            Connection::PROVIDER_GITLAB => 'GitLab',
            Connection::PROVIDER_JIRA => 'Jira',
            default => null,
        };
    }
}
