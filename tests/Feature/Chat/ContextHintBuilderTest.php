<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use App\Models\Connection;
use App\Models\Context;
use App\Models\User;
use App\Services\Chat\ContextHintBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContextHintBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_when_user_has_a_single_context(): void
    {
        $user = User::factory()->withDefaultContext()->create();

        $hint = (new ContextHintBuilder)->build($user);

        $this->assertSame('', $hint);
    }

    public function test_lists_each_context_with_its_attached_providers(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $personal = $user->defaultContext();
        $tiqora = Context::factory()->work('Tiqora')->create(['user_id' => $user->id]);

        // Personal has Google.
        $user->connections()->create([
            'context_id' => $personal->id,
            'provider' => Connection::PROVIDER_GOOGLE,
            'capabilities' => [Connection::CAPABILITY_MAIL, Connection::CAPABILITY_CALENDAR],
            'label' => 'Google',
            'identifier' => 'me@gmail.com',
            'credentials' => ['access_token' => 't'],
            'is_default' => true,
            'enabled' => true,
        ]);

        // Tiqora has Microsoft.
        $user->connections()->create([
            'context_id' => $tiqora->id,
            'provider' => Connection::PROVIDER_MICROSOFT,
            'capabilities' => [Connection::CAPABILITY_MAIL, Connection::CAPABILITY_CALENDAR],
            'label' => 'Microsoft',
            'identifier' => 'me@tiqora.ai',
            'credentials' => ['access_token' => 't'],
            'is_default' => true,
            'enabled' => true,
        ]);

        $hint = (new ContextHintBuilder)->build($user);

        $this->assertStringContainsString($personal->name, $hint);
        $this->assertStringContainsString('Google (Gmail + Calendar)', $hint);
        $this->assertStringContainsString('Tiqora', $hint);
        $this->assertStringContainsString('Microsoft (Outlook + Calendar)', $hint);
        $this->assertStringContainsString('outlook / outlook_calendar', $hint);
        $this->assertStringContainsString('gmail / google_calendar', $hint);
    }

    public function test_marks_contexts_with_no_integrations(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        Context::factory()->work('Empty')->create(['user_id' => $user->id]);

        $hint = (new ContextHintBuilder)->build($user);

        $this->assertStringContainsString('Empty', $hint);
        $this->assertStringContainsString('no integrations attached', $hint);
    }

    public function test_skips_disabled_connections(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $tiqora = Context::factory()->work('Tiqora')->create(['user_id' => $user->id]);

        $user->connections()->create([
            'context_id' => $tiqora->id,
            'provider' => Connection::PROVIDER_MICROSOFT,
            'capabilities' => [Connection::CAPABILITY_MAIL],
            'label' => 'Microsoft',
            'identifier' => 'me@tiqora.ai',
            'credentials' => ['access_token' => 't'],
            'is_default' => true,
            'enabled' => false,
        ]);

        $hint = (new ContextHintBuilder)->build($user);

        $this->assertStringContainsString('Tiqora', $hint);
        $this->assertStringNotContainsString('Microsoft (Outlook + Calendar)', $hint);
    }
}
