<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Day 2 — backfill connections for every existing user, and backfill
 * context_id on tasks/reminders/notes/memories/agent_conversations to each
 * user's default Personal context (seeded by the contexts-table migration).
 *
 * Idempotent: re-running won't duplicate connections (checked by
 * user+provider+identifier). Safe to re-run after partial failures.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const OWNED_TABLES = [
        'tasks',
        'reminders',
        'notes',
        'memories',
        'agent_conversations',
    ];

    public function up(): void
    {
        // Build a map: user_id → default context_id (seeded by prior migration).
        $defaultContextByUser = DB::table('contexts')
            ->where('is_default', true)
            ->pluck('id', 'user_id')
            ->all();

        if ($defaultContextByUser === []) {
            // No existing users → nothing to backfill.
            return;
        }

        DB::table('users')->orderBy('id')->chunkById(200, function ($users) use ($defaultContextByUser): void {
            foreach ($users as $user) {
                $contextId = $defaultContextByUser[$user->id] ?? null;
                if ($contextId === null) {
                    continue;
                }

                $this->backfillScalarConnections($user, $contextId);
                $this->backfillGoogleConnection($user, $contextId);
            }
        });

        // Backfill context_id on owned-record tables.
        foreach (self::OWNED_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'context_id')) {
                continue;
            }

            // Set null context_id rows to the owning user's default context.
            DB::table($table)
                ->whereNull('context_id')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($defaultContextByUser, $table): void {
                    foreach ($rows as $row) {
                        $ctx = $defaultContextByUser[$row->user_id] ?? null;
                        if ($ctx === null) {
                            continue;
                        }
                        DB::table($table)->where('id', $row->id)->update(['context_id' => $ctx]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Reverse: wipe rows created by this backfill. Tolerate missing columns
        // (rollback may run after columns are dropped in Day 9's migration).
        DB::table('connections')->delete();

        foreach (self::OWNED_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'context_id')) {
                DB::table($table)->update(['context_id' => null]);
            }
        }
    }

    private function backfillScalarConnections(object $user, int $contextId): void
    {
        $now = now();

        // Telegram
        if (! empty($user->telegram_chat_id)) {
            $this->insertIfMissing([
                'user_id' => $user->id,
                'context_id' => $contextId,
                'provider' => 'telegram',
                'capabilities' => json_encode(['chat']),
                'label' => 'Telegram',
                'identifier' => (string) $user->telegram_chat_id,
                'credentials' => null,
                'is_default' => true,
                'enabled' => true,
                'metadata' => null,
                'last_synced_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // WhatsApp
        if (! empty($user->whatsapp_phone)) {
            $this->insertIfMissing([
                'user_id' => $user->id,
                'context_id' => $contextId,
                'provider' => 'whatsapp',
                'capabilities' => json_encode(['chat']),
                'label' => 'WhatsApp',
                'identifier' => (string) $user->whatsapp_phone,
                'credentials' => null,
                'is_default' => true,
                'enabled' => true,
                'metadata' => null,
                'last_synced_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Jira
        if (! empty($user->jira_host) || ! empty($user->jira_token)) {
            $this->insertIfMissing([
                'user_id' => $user->id,
                'context_id' => $contextId,
                'provider' => 'jira',
                'capabilities' => json_encode(['issues']),
                'label' => $user->jira_host,
                'identifier' => $user->jira_email,
                'credentials' => $this->encryptArray([
                    'host' => $user->jira_host,
                    'email' => $user->jira_email,
                    'token' => $user->jira_token,
                ]),
                'is_default' => true,
                'enabled' => true,
                'metadata' => null,
                'last_synced_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // GitLab
        if (! empty($user->gitlab_token)) {
            $this->insertIfMissing([
                'user_id' => $user->id,
                'context_id' => $contextId,
                'provider' => 'gitlab',
                'capabilities' => json_encode(['code', 'issues']),
                'label' => $user->gitlab_host ?: 'gitlab.com',
                'identifier' => null,
                'credentials' => $this->encryptArray([
                    'host' => $user->gitlab_host ?: 'https://gitlab.com',
                    'token' => $user->gitlab_token,
                ]),
                'is_default' => true,
                'enabled' => true,
                'metadata' => null,
                'last_synced_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // GitHub
        if (! empty($user->github_token)) {
            $this->insertIfMissing([
                'user_id' => $user->id,
                'context_id' => $contextId,
                'provider' => 'github',
                'capabilities' => json_encode(['code', 'issues']),
                'label' => 'GitHub',
                'identifier' => null,
                'credentials' => $this->encryptArray(['token' => $user->github_token]),
                'is_default' => true,
                'enabled' => true,
                'metadata' => null,
                'last_synced_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Inbound email (forward-to-aiPal)
        if (! empty($user->inbound_email_token)) {
            $this->insertIfMissing([
                'user_id' => $user->id,
                'context_id' => $contextId,
                'provider' => 'inbound_email',
                'capabilities' => json_encode(['mail']),
                'label' => 'Inbound Email',
                'identifier' => (string) $user->inbound_email_token,
                'credentials' => null,
                'is_default' => true,
                'enabled' => true,
                'metadata' => null,
                'last_synced_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillGoogleConnection(object $user, int $contextId): void
    {
        if (! Schema::hasTable('google_tokens')) {
            return;
        }

        $token = DB::table('google_tokens')->where('user_id', $user->id)->first();

        if ($token === null) {
            return;
        }

        $this->insertIfMissing([
            'user_id' => $user->id,
            'context_id' => $contextId,
            'provider' => 'google',
            'capabilities' => json_encode(['mail', 'calendar']),
            'label' => $user->email,
            'identifier' => $user->email,
            'credentials' => $this->encryptArray([
                'access_token' => $token->access_token,
                'refresh_token' => $token->refresh_token,
                'expires_at' => $token->expires_at,
                'scopes' => $token->scopes,
            ]),
            'is_default' => true,
            'enabled' => true,
            'metadata' => json_encode(['google_token_id' => $token->id]),
            'last_synced_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $row */
    private function insertIfMissing(array $row): void
    {
        $exists = DB::table('connections')
            ->where('user_id', $row['user_id'])
            ->where('provider', $row['provider'])
            ->when(
                $row['identifier'] !== null,
                fn ($q) => $q->where('identifier', $row['identifier']),
                fn ($q) => $q->whereNull('identifier'),
            )
            ->exists();

        if (! $exists) {
            DB::table('connections')->insert($row);
        }
    }

    /** @param array<string, mixed> $data */
    private function encryptArray(array $data): string
    {
        return Crypt::encryptString(json_encode($data));
    }
};
