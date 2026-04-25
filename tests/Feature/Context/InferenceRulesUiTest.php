<?php

declare(strict_types=1);

namespace Tests\Feature\Context;

use App\Livewire\Contexts;
use App\Models\Context;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InferenceRulesUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_a_sender_domain_rule_to_a_context(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Tiqora')->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->set("newDomainsByContext.{$work->id}", '@tiqora.ai')
            ->call('addInferenceRule', $work->id);

        $rules = $work->fresh()->inference_rules;
        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertSame('sender_domain', $rules[0]['type']);
        $this->assertSame('tiqora.ai', $rules[0]['value']);
        $this->assertSame(50, $rules[0]['priority']);
    }

    public function test_strips_leading_at_and_lowercases(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Tiqora')->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->set("newDomainsByContext.{$work->id}", '  @TIQORA.AI  ')
            ->call('addInferenceRule', $work->id);

        $this->assertSame('tiqora.ai', $work->fresh()->inference_rules[0]['value']);
    }

    public function test_rejects_invalid_domain_format(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Tiqora')->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->set("newDomainsByContext.{$work->id}", 'not-a-domain')
            ->call('addInferenceRule', $work->id)
            ->assertSet('errorMessage', 'Domain must look like "example.com" or "@example.com".');

        $this->assertNull($work->fresh()->inference_rules);
    }

    public function test_dedupes_existing_domain_silently(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Tiqora')->create([
            'user_id' => $user->id,
            'inference_rules' => [['type' => 'sender_domain', 'value' => 'tiqora.ai', 'priority' => 50]],
        ]);

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->set("newDomainsByContext.{$work->id}", 'tiqora.ai')
            ->call('addInferenceRule', $work->id);

        $this->assertCount(1, $work->fresh()->inference_rules);
    }

    public function test_removes_a_rule_by_index(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Tiqora')->create([
            'user_id' => $user->id,
            'inference_rules' => [
                ['type' => 'sender_domain', 'value' => 'tiqora.ai', 'priority' => 50],
                ['type' => 'sender_domain', 'value' => 'acme.com', 'priority' => 50],
            ],
        ]);

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->call('removeInferenceRule', $work->id, 0);

        $rules = $work->fresh()->inference_rules;
        $this->assertCount(1, $rules);
        $this->assertSame('acme.com', $rules[0]['value']);
    }

    public function test_removing_last_rule_nullifies_inference_rules(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Tiqora')->create([
            'user_id' => $user->id,
            'inference_rules' => [['type' => 'sender_domain', 'value' => 'tiqora.ai', 'priority' => 50]],
        ]);

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->call('removeInferenceRule', $work->id, 0);

        $this->assertNull($work->fresh()->inference_rules);
    }

    public function test_user_cannot_modify_another_users_context(): void
    {
        $owner = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Tiqora')->create(['user_id' => $owner->id]);
        $intruder = User::factory()->withDefaultContext()->create();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($intruder)
            ->test(Contexts::class)
            ->set("newDomainsByContext.{$work->id}", 'evil.com')
            ->call('addInferenceRule', $work->id);
    }
}
