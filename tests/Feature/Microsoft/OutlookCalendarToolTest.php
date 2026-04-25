<?php

declare(strict_types=1);

namespace Tests\Feature\Microsoft;

use App\Ai\Tools\OutlookCalendarTool;
use App\Models\Connection;
use App\Models\User;
use App\Services\MicrosoftGraphCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Ai\Tools\Request;
use Mockery;
use Tests\TestCase;

class OutlookCalendarToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_tool_metadata(): void
    {
        $this->assertSame('outlook_calendar', OutlookCalendarTool::toolName());
        $this->assertSame('Outlook Calendar', OutlookCalendarTool::toolLabel());
        $this->assertSame('calendar', OutlookCalendarTool::toolCategory());
    }

    public function test_returns_not_connected_when_no_microsoft_account(): void
    {
        $user = User::factory()->create();
        $service = Mockery::mock(MicrosoftGraphCalendarService::class);

        $tool = new OutlookCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'list']));

        $this->assertStringContainsString('not connected', $result);
    }

    public function test_list_returns_no_events_message_when_empty(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphCalendarService::class);
        $service->shouldReceive('listEvents')->once()->andReturn([]);

        $tool = new OutlookCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'list', 'range' => 'today']));

        $this->assertStringContainsString('No Outlook events', $result);
    }

    public function test_list_formats_events(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphCalendarService::class);
        $service->shouldReceive('listEvents')->once()->andReturn([
            [
                'id' => 'evt-1',
                'summary' => 'Sprint planning',
                'start' => Carbon::today()->setTime(10, 0)->toIso8601String(),
                'end' => Carbon::today()->setTime(11, 0)->toIso8601String(),
                'location' => 'Room 4',
                'organizer' => 'pm@acme.com',
                'isAllDay' => false,
            ],
        ]);

        $tool = new OutlookCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'list', 'range' => 'today']));

        $this->assertStringContainsString('Sprint planning', $result);
        $this->assertStringContainsString('10:00 AM', $result);
        $this->assertStringContainsString('Room 4', $result);
    }

    public function test_create_persists_event_and_increments_guardrail(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $start = Carbon::tomorrow()->setTime(14, 0);
        $end = Carbon::tomorrow()->setTime(15, 0);

        $service = Mockery::mock(MicrosoftGraphCalendarService::class);
        $service->shouldReceive('createEvent')
            ->once()
            ->with(
                Mockery::on(fn ($u) => $u->is($user)),
                'Design review',
                Mockery::on(fn (Carbon $s) => $s->equalTo($start)),
                Mockery::on(fn (Carbon $e) => $e->equalTo($end)),
                'Room 4',
                null,
                null,
            )
            ->andReturn(['id' => 'evt-new', 'web_link' => null]);

        $tool = new OutlookCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request([
            'action' => 'create',
            'subject' => 'Design review',
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'location' => 'Room 4',
        ]));

        $this->assertStringContainsString('Outlook event created', $result);
        $this->assertStringContainsString('Design review', $result);
        $this->assertSame(1, $user->createdRecordsThisTurn());
    }

    public function test_create_defaults_end_to_thirty_minutes_after_start(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $start = Carbon::tomorrow()->setTime(9, 0);

        $service = Mockery::mock(MicrosoftGraphCalendarService::class);
        $service->shouldReceive('createEvent')
            ->once()
            ->with(
                Mockery::any(),
                'Quick sync',
                Mockery::any(),
                Mockery::on(fn (Carbon $e) => $e->equalTo($start->copy()->addMinutes(30))),
                null,
                null,
                null,
            )
            ->andReturn(['id' => 'x', 'web_link' => null]);

        $tool = new OutlookCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request([
            'action' => 'create',
            'subject' => 'Quick sync',
            'start' => $start->toIso8601String(),
        ]));

        $this->assertStringContainsString('Outlook event created', $result);
    }

    public function test_create_blocks_after_three_records_in_one_turn(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $user->incrementCreatedRecordsThisTurn();
        $user->incrementCreatedRecordsThisTurn();
        $user->incrementCreatedRecordsThisTurn();

        $service = Mockery::mock(MicrosoftGraphCalendarService::class);
        $service->shouldNotReceive('createEvent');

        $tool = new OutlookCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request([
            'action' => 'create',
            'subject' => 'Extra',
            'start' => Carbon::tomorrow()->toIso8601String(),
        ]));

        $this->assertStringContainsString('GUARDRAIL', $result);
    }

    public function test_create_rejects_end_before_start(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphCalendarService::class);
        $service->shouldNotReceive('createEvent');

        $tool = new OutlookCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request([
            'action' => 'create',
            'subject' => 'Bad',
            'start' => '2026-04-26T14:00:00Z',
            'end' => '2026-04-26T13:00:00Z',
        ]));

        $this->assertStringContainsString('End time must be after start time', $result);
    }

    public function test_create_requires_subject_and_start(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphCalendarService::class);

        $tool = new OutlookCalendarTool($user, $service);

        $missingSubject = (string) $tool->handle(new Request([
            'action' => 'create',
            'start' => '2026-04-26T14:00:00Z',
        ]));
        $this->assertStringContainsString('subject', $missingSubject);

        $missingStart = (string) $tool->handle(new Request([
            'action' => 'create',
            'subject' => 'X',
        ]));
        $this->assertStringContainsString('start', $missingStart);
    }

    public function test_swallows_graph_errors_into_friendly_message(): void
    {
        $user = $this->userWithMicrosoftConnection();
        $service = Mockery::mock(MicrosoftGraphCalendarService::class);
        $service->shouldReceive('listEvents')->once()->andThrow(new \RuntimeException('500 Internal'));

        $tool = new OutlookCalendarTool($user, $service);
        $result = (string) $tool->handle(new Request(['action' => 'list']));

        $this->assertStringContainsString('Outlook Calendar is unavailable', $result);
        $this->assertStringContainsString('500', $result);
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
