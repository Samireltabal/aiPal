<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'is_admin', 'briefing_enabled', 'briefing_time', 'briefing_timezone', 'briefing_last_sent_at', 'telegram_chat_id', 'telegram_conversation_id', 'whatsapp_phone', 'whatsapp_conversation_id', 'jira_host', 'jira_email', 'jira_token', 'default_reminder_channel', 'gitlab_host', 'gitlab_token', 'github_token', 'push_notifications_enabled', 'latitude', 'longitude', 'location_name', 'location_source', 'location_updated_at', 'inbound_email_token'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'briefing_last_sent_at' => 'datetime',
            'location_updated_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'briefing_enabled' => 'boolean',
            'push_notifications_enabled' => 'boolean',
        ];
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function persona(): HasOne
    {
        return $this->hasOne(Persona::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'created_by');
    }

    public function memories(): HasMany
    {
        return $this->hasMany(Memory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function contexts(): HasMany
    {
        return $this->hasMany(Context::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class);
    }

    public function defaultContext(): ?Context
    {
        return $this->contexts()->where('is_default', true)->first();
    }

    /**
     * Resolve a context by free-text hint — the LLM passes this from a tool
     * argument or from a "switch context" command. Matches against slug, name
     * (case-insensitive), or kind. Returns null if nothing matches so the
     * caller can fall back to currentContext().
     */
    public function findContextByHint(string $hint): ?Context
    {
        $hint = trim($hint);
        if ($hint === '') {
            return null;
        }

        $needle = strtolower($hint);

        return $this->contexts()
            ->whereNull('archived_at')
            ->get()
            ->first(function (Context $ctx) use ($needle): bool {
                return strtolower((string) $ctx->slug) === $needle
                    || strtolower((string) $ctx->name) === $needle
                    || strtolower((string) $ctx->kind) === $needle;
            });
    }

    /**
     * Pick a connection for a provider, optionally biased toward a context
     * named/slugged like $contextHint. If the hint resolves to a context, we
     * temporarily scope the lookup to it; otherwise we fall back to the
     * standard pickConnection behavior driven by currentContext().
     */
    public function pickConnectionForHint(string $provider, ?string $contextHint): ?Connection
    {
        if ($contextHint === null || trim($contextHint) === '') {
            return $this->pickConnection($provider);
        }

        $context = $this->findContextByHint($contextHint);
        if ($context === null) {
            return $this->pickConnection($provider);
        }

        $previous = $this->activeContext();
        $this->setActiveContext($context);
        try {
            return $this->pickConnection($provider);
        } finally {
            $this->setActiveContext($previous);
        }
    }

    /**
     * In-memory override so a request/job can scope context-aware tools
     * (CreateTask, CreateReminder, CreateNote, ...) to the channel's context
     * without persisting anything on the User row.
     */
    private ?Context $activeContext = null;

    /**
     * When the LLM calls switch_context mid-turn, the new context is also
     * stored here so the chat handler can persist it onto the conversation
     * row after streaming finishes (the conversation id is only known then).
     */
    private ?Context $pendingContextSwitch = null;

    public function setActiveContext(?Context $context): static
    {
        $this->activeContext = $context;

        return $this;
    }

    public function activeContext(): ?Context
    {
        return $this->activeContext;
    }

    public function markPendingContextSwitch(Context $context): void
    {
        $this->pendingContextSwitch = $context;
    }

    public function pendingContextSwitch(): ?Context
    {
        return $this->pendingContextSwitch;
    }

    public function clearPendingContextSwitch(): void
    {
        $this->pendingContextSwitch = null;
    }

    /**
     * Run $fn with $context as the active override, restoring whatever was
     * active before. Used by integration tools when the LLM passes an explicit
     * `context` argument to scope a single call without changing conversation
     * state.
     */
    public function withActiveContext(?Context $context, callable $fn): mixed
    {
        if ($context === null) {
            return $fn();
        }

        $previous = $this->activeContext;
        $this->activeContext = $context;
        try {
            return $fn();
        } finally {
            $this->activeContext = $previous;
        }
    }

    /**
     * The context AI tools should attach to records they create.
     * Prefers an explicit per-request override, falls back to the user's default.
     */
    public function currentContext(): ?Context
    {
        return $this->activeContext ?? $this->defaultContext();
    }

    /**
     * Pull `context_id` off the agent_conversations row (if any) and apply it
     * as the active context so all tools called during this turn pick the
     * matching connection. Used by chat handlers (web, WhatsApp, Telegram).
     */
    public function applyConversationContext(?string $conversationId): void
    {
        if ($conversationId === null) {
            return;
        }

        $contextId = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $this->id)
            ->value('context_id');

        if ($contextId === null) {
            return;
        }

        $context = $this->contexts()->find($contextId);
        if ($context !== null) {
            $this->setActiveContext($context);
        }
    }

    /**
     * All enabled connections that provide a capability, optionally scoped to
     * a specific context. Capability strings are defined on Connection
     * (CAPABILITY_MAIL, CAPABILITY_CALENDAR, CAPABILITY_CHAT, ...).
     *
     * @return Collection<int, Connection>
     */
    public function connectionsFor(string $capability, ?Context $context = null): Collection
    {
        return $this->connections()
            ->where('enabled', true)
            ->whereJsonContains('capabilities', $capability)
            ->when($context !== null, fn ($q) => $q->where('context_id', $context->id))
            ->get();
    }

    /**
     * The connection marked as default for a given capability, preferring the
     * user's default context. Returns the first enabled connection if no
     * default is set.
     */
    public function defaultConnectionFor(string $capability): ?Connection
    {
        $connections = $this->connectionsFor($capability);

        if ($connections->isEmpty()) {
            return null;
        }

        $default = $connections->firstWhere('is_default', true);

        return $default ?? $connections->first();
    }

    public function hasConnectionFor(string $provider): bool
    {
        return $this->connections()
            ->where('provider', $provider)
            ->where('enabled', true)
            ->exists();
    }

    /**
     * Pick a connection for a provider scoped to the user's current context.
     * Resolution order:
     *   1) connection in the active/default context marked is_default
     *   2) any connection in the active/default context
     *   3) the global is_default connection (any context)
     *   4) the first enabled connection
     * Returns null when the user has none.
     */
    public function pickConnection(string $provider): ?Connection
    {
        $context = $this->currentContext();

        $query = $this->connections()
            ->where('provider', $provider)
            ->where('enabled', true);

        if ($context !== null) {
            $scoped = (clone $query)->where('context_id', $context->id);

            $default = (clone $scoped)->where('is_default', true)->first();
            if ($default) {
                return $default;
            }

            $any = $scoped->first();
            if ($any) {
                return $any;
            }
        }

        $globalDefault = (clone $query)->where('is_default', true)->first();
        if ($globalDefault) {
            return $globalDefault;
        }

        return $query->first();
    }

    public function hasGoogleConnected(): bool
    {
        return $this->hasConnectionFor(Connection::PROVIDER_GOOGLE);
    }

    public function hasTelegramLinked(): bool
    {
        return $this->telegram_chat_id !== null;
    }

    public function hasWhatsAppLinked(): bool
    {
        return $this->whatsapp_phone !== null;
    }

    public function hasInboundEmailEnabled(): bool
    {
        return ! empty($this->inbound_email_token);
    }

    public function inboundEmailAddress(): ?string
    {
        if (! $this->hasInboundEmailEnabled()) {
            return null;
        }

        return "forward-{$this->inbound_email_token}@".config('inbound.domain');
    }

    public function hasJiraConnected(): bool
    {
        return $this->hasConnectionFor(Connection::PROVIDER_JIRA);
    }

    public function hasGitLabConnected(): bool
    {
        return $this->hasConnectionFor(Connection::PROVIDER_GITLAB);
    }

    public function hasGitHubConnected(): bool
    {
        return $this->hasConnectionFor(Connection::PROVIDER_GITHUB);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }
}
