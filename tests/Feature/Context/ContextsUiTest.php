<?php

declare(strict_types=1);

namespace Tests\Feature\Context;

use App\Livewire\Contexts;
use App\Models\Connection;
use App\Models\Context;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContextsUiTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPersona(): User
    {
        $user = User::factory()->withDefaultContext()->create();

        Persona::create([
            'user_id' => $user->id,
            'assistant_name' => 'Pal',
            'tone' => 'friendly',
            'formality' => 'casual',
            'humor_level' => 'none',
            'system_prompt' => 'You are helpful.',
        ]);

        return $user;
    }

    public function test_page_loads_with_default_context(): void
    {
        $this->actingAs($this->userWithPersona())
            ->get(route('contexts'))
            ->assertOk()
            ->assertSee('Contexts')
            ->assertSee('Personal');
    }

    public function test_can_create_work_context(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->set('newKind', 'work')
            ->set('newName', 'Acme Corp')
            ->call('createContext')
            ->assertSet('saved', true);

        $this->assertTrue($user->contexts()->where('name', 'Acme Corp')->where('kind', 'work')->exists());
    }

    public function test_slug_collision_auto_disambiguates(): void
    {
        $user = $this->userWithPersona();

        Context::factory()->create(['user_id' => $user->id, 'slug' => 'acme', 'name' => 'Acme']);

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->set('newKind', 'freelance')
            ->set('newName', 'Acme')
            ->call('createContext');

        $this->assertTrue($user->contexts()->where('slug', 'acme-2')->exists());
    }

    public function test_set_default_switches_which_context_is_default(): void
    {
        $user = $this->userWithPersona();
        $work = Context::factory()->work('Acme')->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->call('setDefault', $work->id);

        $this->assertSame($work->id, $user->fresh()->defaultContext()->id);
    }

    public function test_archive_blocks_default_context(): void
    {
        $user = $this->userWithPersona();
        $defaultId = $user->defaultContext()->id;

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->call('archive', $defaultId)
            ->assertSet('errorMessage', "Can't archive your default context. Set another as default first.");

        $this->assertNull(Context::find($defaultId)->archived_at);
    }

    public function test_move_connection_updates_context_id(): void
    {
        $user = $this->userWithPersona();
        $work = Context::factory()->work('Acme')->create(['user_id' => $user->id]);
        $connection = Connection::factory()->telegram()->forContext($user->defaultContext())->create();

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->call('moveConnection', $connection->id, $work->id);

        $this->assertSame($work->id, $connection->fresh()->context_id);
    }

    public function test_delete_context_reassigns_connections_to_default(): void
    {
        $user = $this->userWithPersona();
        $work = Context::factory()->work('Acme')->create(['user_id' => $user->id]);
        $conn = Connection::factory()->telegram()->forContext($work)->create();

        Livewire::actingAs($user)
            ->test(Contexts::class)
            ->call('deleteContext', $work->id);

        $this->assertNull(Context::find($work->id));
        $this->assertSame($user->defaultContext()->id, $conn->fresh()->context_id);
    }
}
