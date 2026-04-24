<?php

declare(strict_types=1);

namespace Tests\Feature\Context;

use App\Models\Connection;
use App\Models\Context;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectionAccessorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_connections_for_capability_returns_matching_connections(): void
    {
        $user = User::factory()->withDefaultContext()->withGoogleConnection()->withTelegramConnection()->create();

        $mail = $user->connectionsFor(Connection::CAPABILITY_MAIL);
        $this->assertCount(1, $mail);
        $this->assertSame('google', $mail->first()->provider);

        $chat = $user->connectionsFor(Connection::CAPABILITY_CHAT);
        $this->assertCount(1, $chat);
        $this->assertSame('telegram', $chat->first()->provider);
    }

    public function test_connections_for_capability_excludes_disabled(): void
    {
        $user = User::factory()->withDefaultContext()->withGoogleConnection()->create();
        $user->connections()->first()->update(['enabled' => false]);

        $this->assertCount(0, $user->connectionsFor(Connection::CAPABILITY_MAIL));
    }

    public function test_connections_for_capability_scopes_by_context(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $work = Context::factory()->work('Acme')->create(['user_id' => $user->id]);

        Connection::factory()->google()->forContext($user->defaultContext())->create();
        Connection::factory()->google()->forContext($work)->create();

        $this->assertCount(2, $user->connectionsFor(Connection::CAPABILITY_MAIL));
        $this->assertCount(1, $user->connectionsFor(Connection::CAPABILITY_MAIL, $work));
    }

    public function test_default_connection_for_prefers_is_default_true(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $ctx = $user->defaultContext();

        Connection::factory()->google()->forContext($ctx)->create(['is_default' => false, 'label' => 'secondary']);
        Connection::factory()->google()->forContext($ctx)->create(['is_default' => true, 'label' => 'primary']);

        $this->assertSame('primary', $user->defaultConnectionFor(Connection::CAPABILITY_MAIL)->label);
    }

    public function test_default_connection_for_returns_null_when_no_match(): void
    {
        $user = User::factory()->withDefaultContext()->create();

        $this->assertNull($user->defaultConnectionFor(Connection::CAPABILITY_MAIL));
    }

    public function test_has_connection_for_provider(): void
    {
        $user = User::factory()->withDefaultContext()->withTelegramConnection('42')->create();

        $this->assertTrue($user->hasConnectionFor('telegram'));
        $this->assertFalse($user->hasConnectionFor('whatsapp'));
    }

    public function test_connection_scopes_chain(): void
    {
        $user = User::factory()->withDefaultContext()->withGoogleConnection()->create();

        $this->assertSame(1, Connection::query()->enabled()->provider('google')->capability('mail')->count());
        $this->assertSame(0, Connection::query()->enabled()->provider('google')->capability('chat')->count());
    }
}
