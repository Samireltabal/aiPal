<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move every existing google_tokens row into a `connections` row so
     * Gmail/Calendar can be refactored off the legacy single-account table.
     *
     * Existing rows don't carry the user's Google email, so we use a
     * placeholder `identifier` of "primary-{user_id}" — the next OAuth
     * callback will overwrite it with the real email by upsert.
     */
    public function up(): void
    {
        if (! Schema::hasTable('google_tokens')) {
            return;
        }

        $now = now();
        $rows = DB::table('google_tokens')->get();

        foreach ($rows as $row) {
            $defaultContextId = DB::table('contexts')
                ->where('user_id', $row->user_id)
                ->where('is_default', true)
                ->value('id');

            $exists = DB::table('connections')
                ->where('user_id', $row->user_id)
                ->where('provider', 'google')
                ->where('identifier', "primary-{$row->user_id}")
                ->exists();

            if ($exists) {
                continue;
            }

            $credentials = [
                'access_token' => $row->access_token,
                'refresh_token' => $row->refresh_token,
                'expires_at' => $row->expires_at,
                'scopes' => $row->scopes ?? '',
            ];

            DB::table('connections')->insert([
                'user_id' => $row->user_id,
                'context_id' => $defaultContextId,
                'provider' => 'google',
                'capabilities' => json_encode(['mail', 'calendar']),
                'label' => 'Google account',
                'identifier' => "primary-{$row->user_id}",
                'credentials' => Crypt::encrypt($credentials),
                'is_default' => true,
                'enabled' => true,
                'metadata' => json_encode(['migrated_from' => 'google_tokens']),
                'last_synced_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('connections')
            ->where('provider', 'google')
            ->whereJsonContains('metadata', ['migrated_from' => 'google_tokens'])
            ->delete();
    }
};
