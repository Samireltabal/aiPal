<?php

declare(strict_types=1);

namespace Tests\Feature\Usage;

use App\Models\User;
use App\Services\UsageAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UsageAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function seedConversation(int $userId, string $convId, string $title = 'Test'): void
    {
        DB::table('agent_conversations')->insert([
            'id' => $convId,
            'user_id' => $userId,
            'title' => $title,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedAssistantMessage(
        string $convId,
        ?int $userId,
        string $agent,
        array $usage,
        ?string $model = null,
        ?string $createdAt = null,
    ): void {
        DB::table('agent_conversation_messages')->insert([
            'id' => (string) Str::uuid7(),
            'conversation_id' => $convId,
            'user_id' => $userId,
            'agent' => $agent,
            'role' => 'assistant',
            'content' => '',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => json_encode($usage),
            'meta' => $model ? json_encode(['model' => $model]) : '[]',
            'created_at' => $createdAt ?? (string) now(),
            'updated_at' => now(),
        ]);
    }

    public function test_user_scope_only_includes_own_messages(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->seedConversation($a->id, 'conv-a');
        $this->seedConversation($b->id, 'conv-b');

        $this->seedAssistantMessage('conv-a', $a->id, 'App\\Ai\\Agents\\Chat\\ChatAgent', [
            'prompt_tokens' => 100, 'completion_tokens' => 50,
        ], 'gpt-4o-mini');
        $this->seedAssistantMessage('conv-b', $b->id, 'App\\Ai\\Agents\\Chat\\ChatAgent', [
            'prompt_tokens' => 999, 'completion_tokens' => 999,
        ], 'gpt-4o-mini');

        $summary = (new UsageAnalytics)->summary($a->id, 30);

        $this->assertSame(100, $summary['totals']['prompt_tokens']);
        $this->assertSame(50, $summary['totals']['completion_tokens']);
        $this->assertSame(150, $summary['totals']['total_tokens']);
        $this->assertSame(1, $summary['totals']['messages']);
        $this->assertSame(1, $summary['totals']['conversations']);
    }

    public function test_global_scope_aggregates_all_users(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->seedConversation($a->id, 'conv-a');
        $this->seedConversation($b->id, 'conv-b');

        $this->seedAssistantMessage('conv-a', $a->id, 'App\\Ai\\Agents\\Chat\\ChatAgent', [
            'prompt_tokens' => 100, 'completion_tokens' => 50,
        ]);
        $this->seedAssistantMessage('conv-b', $b->id, 'App\\Ai\\Agents\\Chat\\ChatAgent', [
            'prompt_tokens' => 200, 'completion_tokens' => 100,
        ]);

        $summary = (new UsageAnalytics)->summary(null, 30);

        $this->assertSame(300, $summary['totals']['prompt_tokens']);
        $this->assertSame(150, $summary['totals']['completion_tokens']);
        $this->assertSame(2, $summary['totals']['users']);
    }

    public function test_user_messages_are_excluded_from_totals(): void
    {
        $u = User::factory()->create();
        $this->seedConversation($u->id, 'conv-u');

        DB::table('agent_conversation_messages')->insert([
            'id' => 'user-msg-1',
            'conversation_id' => 'conv-u',
            'user_id' => $u->id,
            'agent' => 'App\\Ai\\Agents\\Chat\\ChatAgent',
            'role' => 'user',
            'content' => 'hi',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => json_encode(['prompt_tokens' => 9999]),
            'meta' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $summary = (new UsageAnalytics)->summary($u->id, 30);

        $this->assertSame(0, $summary['totals']['prompt_tokens']);
        $this->assertSame(0, $summary['totals']['messages']);
    }

    public function test_by_function_groups_and_percentages_sum_to_100(): void
    {
        $u = User::factory()->create();
        $this->seedConversation($u->id, 'c1');

        $this->seedAssistantMessage('c1', $u->id, 'App\\Ai\\Agents\\Chat\\ChatAgent', [
            'prompt_tokens' => 600, 'completion_tokens' => 0,
        ]);
        $this->seedAssistantMessage('c1', $u->id, 'App\\Ai\\Agents\\Memory\\MemoryExtractorAgent', [
            'prompt_tokens' => 400, 'completion_tokens' => 0,
        ]);

        $summary = (new UsageAnalytics)->summary($u->id, 30);

        $this->assertCount(2, $summary['by_function']);
        $this->assertSame('Chat', $summary['by_function'][0]['function']);
        $this->assertSame(60.0, $summary['by_function'][0]['percent']);
        $this->assertSame('Memory Extraction', $summary['by_function'][1]['function']);
        $this->assertSame(40.0, $summary['by_function'][1]['percent']);
    }

    public function test_cost_is_estimated_from_pricing_table(): void
    {
        $u = User::factory()->create();
        $this->seedConversation($u->id, 'c-cost');

        // gpt-4o-mini: $0.15 input / $0.60 output per 1M tokens
        $this->seedAssistantMessage('c-cost', $u->id, 'App\\Ai\\Agents\\Chat\\ChatAgent', [
            'prompt_tokens' => 1_000_000,
            'completion_tokens' => 1_000_000,
        ], 'gpt-4o-mini');

        $summary = (new UsageAnalytics)->summary($u->id, 30);

        $this->assertEqualsWithDelta(0.75, $summary['cost_estimate_usd'], 0.001);
    }

    public function test_grok_pricing_is_supported(): void
    {
        $u = User::factory()->create();
        $this->seedConversation($u->id, 'c-grok');

        // grok-3-mini: $0.30 input / $0.50 output per 1M
        $this->seedAssistantMessage('c-grok', $u->id, 'App\\Ai\\Agents\\Chat\\ChatAgent', [
            'prompt_tokens' => 1_000_000,
            'completion_tokens' => 0,
        ], 'grok-3-mini');

        $summary = (new UsageAnalytics)->summary($u->id, 30);

        $this->assertEqualsWithDelta(0.30, $summary['cost_estimate_usd'], 0.001);
    }

    public function test_top_conversations_includes_titles(): void
    {
        $u = User::factory()->create();
        $this->seedConversation($u->id, 'c-named', 'Project plan');

        $this->seedAssistantMessage('c-named', $u->id, 'App\\Ai\\Agents\\Chat\\ChatAgent', [
            'prompt_tokens' => 100, 'completion_tokens' => 100,
        ]);

        $summary = (new UsageAnalytics)->summary($u->id, 30);

        $this->assertSame('Project plan', $summary['top_conversations'][0]['title']);
        $this->assertSame(200, $summary['top_conversations'][0]['total_tokens']);
    }
}
