<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Ai\Tools\GoogleCalendarTool;
use App\Models\Connection;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Ai\Tools\Request;
use Mockery;
use Tests\TestCase;

class GoogleCalendarToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_not_connected_message_when_no_google_token(): void
    {
        $user = User::factory()->create();
        $service = Mockery::mock(GoogleCalendarService::class);

        $tool = new GoogleCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request(['range' => 'today']));

        $this->assertStringContainsString('not connected', $result);
    }

    public function test_returns_no_events_when_calendar_is_empty(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $user->connections()->create([
            'context_id' => $user->defaultContext()->id,
            'provider' => Connection::PROVIDER_GOOGLE,
            'capabilities' => [Connection::CAPABILITY_CALENDAR],
            'label' => 'Google',
            'identifier' => 'me@example.com',
            'credentials' => ['access_token' => 'tok', 'scopes' => ''],
            'is_default' => true,
            'enabled' => true,
        ]);

        $service = Mockery::mock(GoogleCalendarService::class);
        $service->shouldReceive('listEvents')->once()->andReturn([]);

        $tool = new GoogleCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request(['range' => 'today']));

        $this->assertStringContainsString('No events found', $result);
    }

    public function test_returns_formatted_events(): void
    {
        $user = User::factory()->withDefaultContext()->create();
        $user->connections()->create([
            'context_id' => $user->defaultContext()->id,
            'provider' => Connection::PROVIDER_GOOGLE,
            'capabilities' => [Connection::CAPABILITY_CALENDAR],
            'label' => 'Google',
            'identifier' => 'me@example.com',
            'credentials' => ['access_token' => 'tok', 'scopes' => ''],
            'is_default' => true,
            'enabled' => true,
        ]);

        $service = Mockery::mock(GoogleCalendarService::class);
        $service->shouldReceive('listEvents')->once()->andReturn([
            [
                'id' => '1',
                'summary' => 'Team Standup',
                'start' => Carbon::today()->setTime(9, 0)->toIso8601String(),
                'end' => Carbon::today()->setTime(9, 15)->toIso8601String(),
                'location' => null,
                'description' => null,
            ],
        ]);

        $tool = new GoogleCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request(['range' => 'today']));

        $this->assertStringContainsString('Team Standup', $result);
        $this->assertStringContainsString('9:00 AM', $result);
    }

    public function test_tool_metadata(): void
    {
        $this->assertSame('google_calendar', GoogleCalendarTool::toolName());
        $this->assertSame('Google Calendar', GoogleCalendarTool::toolLabel());
        $this->assertSame('calendar', GoogleCalendarTool::toolCategory());
    }
}
