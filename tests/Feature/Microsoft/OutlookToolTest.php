<?php

declare(strict_types=1);

namespace Tests\Feature\Microsoft;

use App\Ai\Tools\OutlookTool;
use App\Models\Connection;
use App\Models\User;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Mockery;
use Tests\TestCase;

class OutlookToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_tool_metadata(): void
    {
        $this->assertSame('outlook', OutlookTool::toolName());
        $this->assertSame('Outlook', OutlookTool::toolLabel());
        $this->assertSame('integrations', OutlookTool::toolCategory());
    }

    public function test_returns_not_connected_message_when_no_microsoft_account(): void
    {
        $user = User::factory()->create();
        $service = Mockery::mock(MicrosoftGraphMailService::class);

        $tool = new OutlookTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'list']));

        $this->assertStringContainsString('not connected', $result);
    }

    public function test_list_returns_empty_inbox_message(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphMailService::class);
        $service->shouldReceive('listInbox')->once()->andReturn([]);

        $tool = new OutlookTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'list']));

        $this->assertStringContainsString('inbox is empty', $result);
    }

    public function test_list_formats_messages(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphMailService::class);
        $service->shouldReceive('listInbox')->once()->with($user, 5)->andReturn([
            [
                'id' => 'AAMkAD...1',
                'subject' => 'Sprint kickoff',
                'from' => 'Lead <lead@acme.com>',
                'received' => '2026-04-25T09:00:00Z',
                'snippet' => 'Agenda attached',
                'isUnread' => true,
            ],
        ]);

        $tool = new OutlookTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'list', 'max_results' => 5]));

        $this->assertStringContainsString('Sprint kickoff', $result);
        $this->assertStringContainsString('AAMkAD...1', $result);
        $this->assertStringContainsString('●', $result);
    }

    public function test_read_returns_full_message(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphMailService::class);
        $service->shouldReceive('getMessage')->once()->with($user, 'msg-id')->andReturn([
            'id' => 'msg-id',
            'subject' => 'Q2 plan',
            'from' => 'CEO <ceo@acme.com>',
            'to' => 'me@acme.com',
            'date' => '2026-04-25T09:00:00Z',
            'body' => 'Full plan body here',
        ]);

        $tool = new OutlookTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'read', 'message_id' => 'msg-id']));

        $this->assertStringContainsString('Q2 plan', $result);
        $this->assertStringContainsString('Full plan body here', $result);
    }

    public function test_read_requires_message_id(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphMailService::class);

        $tool = new OutlookTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'read']));

        $this->assertStringContainsString('provide a message_id', $result);
    }

    public function test_search_returns_results(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphMailService::class);
        $service->shouldReceive('search')->once()->with($user, 'invoice', 10)->andReturn([
            [
                'id' => '1',
                'subject' => 'Invoice 2031',
                'from' => 'Billing <billing@acme.com>',
                'received' => '2026-04-25T09:00:00Z',
                'snippet' => 'Net 30',
                'isUnread' => false,
            ],
        ]);

        $tool = new OutlookTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'search', 'query' => 'invoice']));

        $this->assertStringContainsString('Invoice 2031', $result);
    }

    public function test_search_requires_query(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphMailService::class);

        $tool = new OutlookTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'search']));

        $this->assertStringContainsString('provide a query', $result);
    }

    public function test_swallows_graph_errors_into_friendly_message(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphMailService::class);
        $service->shouldReceive('listInbox')->once()->andThrow(new \RuntimeException('429 Too Many Requests'));

        $tool = new OutlookTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'list']));

        $this->assertStringContainsString('Outlook is unavailable', $result);
        $this->assertStringContainsString('429', $result);
    }

    private function userWithMicrosoftConnection(): User
    {
        $user = User::factory()->withDefaultContext()->create();

        $user->connections()->create([
            'context_id' => $user->defaultContext()->id,
            'provider' => Connection::PROVIDER_MICROSOFT,
            'capabilities' => [Connection::CAPABILITY_MAIL, Connection::CAPABILITY_CALENDAR],
            'label' => 'Microsoft',
            'identifier' => 'me@example.com',
            'credentials' => ['access_token' => 'tok', 'refresh_token' => 'r', 'scopes' => ''],
            'is_default' => true,
            'enabled' => true,
        ]);

        return $user;
    }
}
