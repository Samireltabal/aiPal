<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use App\Services\GmailService;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public array $pendingTasks = [];

    public int $pendingTasksCount = 0;

    public array $upcomingReminders = [];

    public array $calendarEvents = [];

    public int $memoriesCount = 0;

    public int $documentsCount = 0;

    public int $notesCount = 0;

    public int $conversationsCount = 0;

    public int $unreadEmailsCount = 0;

    public bool $gmailConnected = false;

    public bool $calendarConnected = false;

    public bool $emailsFetched = false;

    public bool $fetchingEmails = false;

    public bool $calendarFetched = false;

    public bool $fetchingCalendar = false;

    public string $greeting = '';

    public string $personaName = 'aiPal';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->personaName = $user->persona?->assistant_name ?? 'aiPal';
        $this->greeting = $this->buildGreeting($user->name);
        $this->gmailConnected = $user->hasGoogleConnected();
        $this->calendarConnected = $user->hasGoogleConnected();

        $this->loadTasks($user);
        $this->loadReminders($user);
        $this->loadStats($user);
    }

    public function fetchEmails(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasGoogleConnected()) {
            return;
        }

        $this->fetchingEmails = true;

        try {
            $gmail = app(GmailService::class);
            $emails = $gmail->listInbox($user, 20);

            $this->unreadEmailsCount = count($emails);
            $this->emailsFetched = true;
        } catch (\Throwable $e) {
            Log::warning('Dashboard Gmail fetch failed', ['error' => $e->getMessage()]);
            $this->emailsFetched = true;
            $this->unreadEmailsCount = 0;
        } finally {
            $this->fetchingEmails = false;
        }
    }

    public function fetchCalendar(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasGoogleConnected()) {
            return;
        }

        $this->fetchingCalendar = true;

        try {
            $calendar = app(GoogleCalendarService::class);
            $events = $calendar->listTodayEvents($user);

            $this->calendarEvents = array_slice(array_map(fn (array $e) => [
                'title' => $e['summary'] ?? 'Untitled event',
                'time' => $e['start'] ?? '',
            ], $events), 0, 5);

            $this->calendarFetched = true;
        } catch (\Throwable $e) {
            Log::warning('Dashboard calendar fetch failed', ['error' => $e->getMessage()]);
            $this->calendarFetched = true;
            $this->calendarEvents = [];
        } finally {
            $this->fetchingCalendar = false;
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }

    private function loadTasks(User $user): void
    {
        $tasks = $user->tasks()
            ->whereNull('completed_at')
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->limit(5)
            ->get(['id', 'title', 'priority', 'due_date']);

        $this->pendingTasksCount = $user->tasks()->whereNull('completed_at')->count();

        $this->pendingTasks = $tasks->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'priority' => $t->priority,
            'due_date' => $t->due_date?->format('M j'),
        ])->toArray();
    }

    private function loadReminders(User $user): void
    {
        $reminders = $user->reminders()
            ->whereNull('sent_at')
            ->where('remind_at', '>', now())
            ->orderBy('remind_at')
            ->limit(3)
            ->get(['id', 'title', 'remind_at']);

        $this->upcomingReminders = $reminders->map(fn ($r) => [
            'id' => $r->id,
            'title' => $r->title,
            'remind_at' => $r->remind_at->format('M j, g:i A'),
        ])->toArray();
    }

    private function loadStats(User $user): void
    {
        $this->memoriesCount = $user->memories()->count();
        $this->documentsCount = $user->documents()->count();
        $this->notesCount = $user->notes()->count();
        $this->conversationsCount = DB::table('agent_conversations')
            ->where('user_id', $user->id)
            ->count();
    }

    private function buildGreeting(string $name): string
    {
        $hour = (int) now()->format('G');

        $timeGreeting = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };

        return "{$timeGreeting}, {$name}";
    }
}
