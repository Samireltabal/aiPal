<?php

declare(strict_types=1);

namespace Tests\Feature\Context;

use App\Models\Connection;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifies the Day 2 backfill migration by simulating its logic against a
 * freshly-migrated DB. Because `RefreshDatabase` runs all migrations at setup
 * (with no pre-existing rows), we populate scalar columns on a user, call the
 * migration's up() again, and assert Connection rows were created.
 */
class BackfillMigrationTest extends TestCase
{
    use RefreshDatabase;

    private string $migrationPath = 'database/migrations/2026_04_24_153058_backfill_contexts_and_connections.php';

    private function rerunBackfill(): void
    {
        // Day 1's contexts migration + Day 2 backfill already ran at setup
        // against an empty users table. Re-run Day 2's migration against the
        // users we've just created.
        $migration = require base_path($this->migrationPath);
        $migration->up();
    }

    private function userWithDefaultContext(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        // Day 1 migration seeds default context only for users that existed
        // before the migration ran. Test-created users don't get one
        // automatically, so mirror the seed here.
        DB::table('contexts')->insert([
            'user_id' => $user->id,
            'kind' => 'personal',
            'name' => 'Personal',
            'slug' => 'personal',
            'color' => '#6366f1',
            'is_default' => true,
            'inference_rules' => null,
            'archived_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->refresh();
    }

    public function test_backfills_telegram_connection_from_scalar_column(): void
    {
        $user = $this->userWithDefaultContext(['telegram_chat_id' => '42']);

        $this->rerunBackfill();

        $connection = Connection::where('user_id', $user->id)
            ->where('provider', 'telegram')
            ->first();

        $this->assertNotNull($connection);
        $this->assertSame('42', $connection->identifier);
        $this->assertTrue($connection->isCapableOf('chat'));
        $this->assertSame($user->defaultContext()->id, $connection->context_id);
    }

    public function test_backfills_whatsapp_and_inbound_email(): void
    {
        $user = $this->userWithDefaultContext([
            'whatsapp_phone' => '15551234567',
            'inbound_email_token' => str_repeat('a', 32),
        ]);

        $this->rerunBackfill();

        $this->assertSame(1, Connection::where('user_id', $user->id)->where('provider', 'whatsapp')->count());
        $this->assertSame(1, Connection::where('user_id', $user->id)->where('provider', 'inbound_email')->count());
    }

    public function test_backfill_is_idempotent(): void
    {
        $user = $this->userWithDefaultContext(['telegram_chat_id' => '777']);

        $this->rerunBackfill();
        $this->rerunBackfill();
        $this->rerunBackfill();

        $this->assertSame(1, Connection::where('user_id', $user->id)->where('provider', 'telegram')->count());
    }

    public function test_backfills_context_id_on_owned_tables(): void
    {
        $user = $this->userWithDefaultContext();

        $task = Task::create([
            'user_id' => $user->id,
            'title' => 'Legacy task',
            'priority' => 'medium',
        ]);
        $note = Note::create([
            'user_id' => $user->id,
            'title' => 'Legacy note',
            'content' => 'test',
        ]);
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Legacy reminder',
            'remind_at' => now()->addDay(),
            'channel' => 'email',
        ]);

        // null context_id on all three (pre-backfill state)
        DB::table('tasks')->where('id', $task->id)->update(['context_id' => null]);
        DB::table('notes')->where('id', $note->id)->update(['context_id' => null]);
        DB::table('reminders')->where('id', $reminder->id)->update(['context_id' => null]);

        $this->rerunBackfill();

        $defaultCtxId = $user->defaultContext()->id;

        $this->assertSame($defaultCtxId, Task::find($task->id)->context_id);
        $this->assertSame($defaultCtxId, Note::find($note->id)->context_id);
        $this->assertSame($defaultCtxId, Reminder::find($reminder->id)->context_id);
    }

    public function test_user_without_any_scalar_integrations_gets_no_connections(): void
    {
        $user = $this->userWithDefaultContext();

        $this->rerunBackfill();

        $this->assertSame(0, Connection::where('user_id', $user->id)->count());
    }

    public function test_credentials_are_encrypted_via_model_cast_after_backfill(): void
    {
        $user = $this->userWithDefaultContext([
            'github_token' => 'ghp_secret_token_xyz',
        ]);

        $this->rerunBackfill();

        $connection = Connection::where('user_id', $user->id)
            ->where('provider', 'github')
            ->firstOrFail();

        // Raw storage should not contain plaintext.
        $raw = DB::table('connections')->where('id', $connection->id)->value('credentials');
        $this->assertStringNotContainsString('ghp_secret_token_xyz', (string) $raw);

        // Model cast decrypts correctly.
        $this->assertSame('ghp_secret_token_xyz', $connection->credentials['token']);
    }
}
