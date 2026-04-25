<?php

declare(strict_types=1);

namespace Tests\Feature\Context;

use App\Ai\Tools\SwitchContextTool;
use App\Models\Context;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class SwitchContextToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_switches_active_context_by_name(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Acme')->create(['user_id' => $user->id]);

        $tool = new SwitchContextTool($user);
        $result = (string) $tool->handle(new Request(['context' => 'Acme']));

        $this->assertStringContainsString('Switched to Acme', $result);
        $this->assertSame($work->id, $user->activeContext()->id);
        $this->assertSame($work->id, $user->pendingContextSwitch()->id);
    }

    public function test_switches_by_kind(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        Context::factory()->work('Acme')->create(['user_id' => $user->id]);

        $tool = new SwitchContextTool($user);
        $result = (string) $tool->handle(new Request(['context' => 'work']));

        $this->assertStringContainsString('Switched to Acme', $result);
    }

    public function test_returns_helpful_error_when_context_not_found(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        Context::factory()->work('Acme')->create(['user_id' => $user->id]);

        $tool = new SwitchContextTool($user);
        $result = (string) $tool->handle(new Request(['context' => 'NonExistent']));

        $this->assertStringContainsString('No context found', $result);
        $this->assertStringContainsString('Acme', $result);
    }

    public function test_apply_conversation_context_loads_persisted_context(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Phoenix')->create(['user_id' => $user->id]);

        $conversationId = (string) Str::uuid();
        DB::table('agent_conversations')->insert([
            'id' => $conversationId,
            'user_id' => $user->id,
            'context_id' => $work->id,
            'title' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->applyConversationContext($conversationId);

        $this->assertSame($work->id, $user->activeContext()->id);
    }
}
