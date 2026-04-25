<?php

declare(strict_types=1);

namespace Tests\Feature\Usage;

use App\Ai\Tools\UsageInsightsTool;
use App\Models\User;
use App\Services\ServerMetrics;
use App\Services\UsageAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class UsageInsightsToolTest extends TestCase
{
    use RefreshDatabase;

    private function makeTool(User $user): UsageInsightsTool
    {
        return new UsageInsightsTool(
            $user,
            app(UsageAnalytics::class),
            app(ServerMetrics::class),
        );
    }

    private function seedMessage(int $userId, string $conversationId, array $usage, string $model = 'gpt-4o-mini'): void
    {
        DB::table('agent_conversations')->updateOrInsert(
            ['id' => $conversationId],
            [
                'user_id' => $userId,
                'title' => 'Test convo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('agent_conversation_messages')->insert([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'role' => 'assistant',
            'agent' => 'App\\Ai\\Agents\\Chat\\ChatAgent',
            'content' => 'hello',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => json_encode($usage),
            'meta' => json_encode(['model' => $model]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_overview_returns_totals_for_user(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->seedMessage($user->id, 'conv-1', [
            'prompt_tokens' => 100,
            'completion_tokens' => 50,
            'cache_read_input_tokens' => 0,
            'cache_write_input_tokens' => 0,
            'reasoning_tokens' => 0,
        ]);

        $out = (string) $this->makeTool($user)->handle(new Request([
            'section' => 'overview',
            'days' => 7,
            'scope' => 'me',
        ]));

        $this->assertStringContainsString('scope: me', $out);
        $this->assertStringContainsString('window_days: 7', $out);
        $this->assertStringContainsString('total_tokens: 150', $out);
        $this->assertStringContainsString('prompt_tokens: 100', $out);
        $this->assertStringContainsString('messages: 1', $out);
    }

    public function test_non_admin_requesting_global_falls_back_to_me(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $other = User::factory()->create(['is_admin' => false]);

        $this->seedMessage($user->id, 'conv-a', ['prompt_tokens' => 10, 'completion_tokens' => 5, 'cache_read_input_tokens' => 0, 'cache_write_input_tokens' => 0, 'reasoning_tokens' => 0]);
        $this->seedMessage($other->id, 'conv-b', ['prompt_tokens' => 999, 'completion_tokens' => 999, 'cache_read_input_tokens' => 0, 'cache_write_input_tokens' => 0, 'reasoning_tokens' => 0]);

        $out = (string) $this->makeTool($user)->handle(new Request(['scope' => 'global']));

        $this->assertStringContainsString('scope: me', $out);
        $this->assertStringContainsString('total_tokens: 15', $out);
        $this->assertStringNotContainsString('1,998', $out);
    }

    public function test_non_admin_server_section_is_rejected(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $out = (string) $this->makeTool($user)->handle(new Request(['section' => 'server']));

        $this->assertStringContainsString('restricted to admins', $out);
    }

    public function test_admin_server_section_returns_metrics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $out = (string) $this->makeTool($admin)->handle(new Request(['section' => 'server']));

        $this->assertStringContainsString('php:', $out);
        $this->assertStringContainsString('laravel:', $out);
        $this->assertStringContainsString('db_driver:', $out);
        $this->assertStringContainsString('queue_driver:', $out);
    }

    public function test_invalid_days_clamps_to_30(): void
    {
        $user = User::factory()->create();

        $out = (string) $this->makeTool($user)->handle(new Request(['days' => 99999]));

        $this->assertStringContainsString('window_days: 30', $out);
    }

    public function test_admin_global_scope_aggregates_all_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $other = User::factory()->create();

        $this->seedMessage($admin->id, 'conv-admin', ['prompt_tokens' => 10, 'completion_tokens' => 5, 'cache_read_input_tokens' => 0, 'cache_write_input_tokens' => 0, 'reasoning_tokens' => 0]);
        $this->seedMessage($other->id, 'conv-other', ['prompt_tokens' => 20, 'completion_tokens' => 10, 'cache_read_input_tokens' => 0, 'cache_write_input_tokens' => 0, 'reasoning_tokens' => 0]);

        $out = (string) $this->makeTool($admin)->handle(new Request(['scope' => 'global']));

        $this->assertStringContainsString('scope: global', $out);
        $this->assertStringContainsString('total_tokens: 45', $out);
    }
}
