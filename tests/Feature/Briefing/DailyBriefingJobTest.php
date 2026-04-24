<?php

declare(strict_types=1);

namespace Tests\Feature\Briefing;

use App\Ai\Agents\Briefing\DailyBriefingAgent;
use App\Jobs\DailyBriefingJob;
use App\Models\Task;
use App\Models\User;
use App\Notifications\DailyBriefingNotification;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class DailyBriefingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_briefing_email_to_user(): void
    {
        Notification::fake();
        DailyBriefingAgent::fake(['Here is your morning briefing.']);

        $user = User::factory()->withDefaultContext()->create([
            'briefing_enabled' => true,
            'briefing_time' => '08:00',
            'briefing_timezone' => 'UTC',
        ]);

        $calendarService = Mockery::mock(GoogleCalendarService::class);
        $calendarService->shouldReceive('listTodayEvents')->andReturn([]);

        $job = new DailyBriefingJob($user->id);
        $job->handle($calendarService);

        Notification::assertSentTo($user, DailyBriefingNotification::class);
    }

    public function test_skips_disabled_briefing(): void
    {
        Notification::fake();

        $user = User::factory()->create(['briefing_enabled' => false]);

        $calendarService = Mockery::mock(GoogleCalendarService::class);

        $job = new DailyBriefingJob($user->id);
        $job->handle($calendarService);

        Notification::assertNothingSent();
    }

    public function test_includes_pending_tasks_in_context(): void
    {
        Notification::fake();
        DailyBriefingAgent::fake(['Briefing with tasks.']);

        $user = User::factory()->withDefaultContext()->create([
            'briefing_enabled' => true,
            'briefing_time' => '08:00',
            'briefing_timezone' => 'UTC',
        ]);

        Task::create([
            'user_id' => $user->id,
            'title' => 'Write unit tests',
            'priority' => 'high',
        ]);

        $calendarService = Mockery::mock(GoogleCalendarService::class);
        $calendarService->shouldReceive('listTodayEvents')->andReturn([]);

        $job = new DailyBriefingJob($user->id);
        $job->handle($calendarService);

        Notification::assertSentTo($user, DailyBriefingNotification::class);
    }

    public function test_updates_briefing_last_sent_at_after_dispatch(): void
    {
        Notification::fake();
        DailyBriefingAgent::fake(['Morning briefing.']);

        $user = User::factory()->withDefaultContext()->create([
            'briefing_enabled' => true,
            'briefing_time' => '08:00',
            'briefing_timezone' => 'UTC',
        ]);

        $calendarService = Mockery::mock(GoogleCalendarService::class);
        $calendarService->shouldReceive('listTodayEvents')->andReturn([]);

        $job = new DailyBriefingJob($user->id);
        $job->handle($calendarService);

        $this->assertNotNull($user->fresh()->briefing_last_sent_at);
    }

    public function test_skips_nonexistent_user(): void
    {
        Notification::fake();

        $calendarService = Mockery::mock(GoogleCalendarService::class);

        $job = new DailyBriefingJob(999999);
        $job->handle($calendarService);

        Notification::assertNothingSent();
    }
}
