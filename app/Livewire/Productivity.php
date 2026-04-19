<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Note;
use App\Models\Reminder;
use App\Models\Task;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Productivity extends Component
{
    use WithPagination;

    public string $tab = 'tasks';

    // — Notes —
    public string $noteTitle = '';

    #[Validate('required|string|max:10000')]
    public string $noteContent = '';

    public string $noteSearch = '';

    // — Reminders —
    #[Validate('required|string|max:255')]
    public string $reminderTitle = '';

    public string $reminderBody = '';

    #[Validate('required|date|after:now')]
    public string $reminderAt = '';

    public string $reminderChannel = 'email';

    public string $reminderWebhookUrl = '';

    // — Tasks —
    #[Validate('required|string|max:255')]
    public string $taskTitle = '';

    public string $taskDescription = '';

    public string $taskPriority = 'medium';

    public string $taskDueDate = '';

    public string $taskSearch = '';

    public bool $showCompleted = false;

    public string $successMessage = '';

    public function updatingTab(): void
    {
        $this->resetPage();
        $this->successMessage = '';
    }

    // — Notes actions —

    public function saveNote(): void
    {
        $this->validateOnly('noteContent');

        $title = trim($this->noteTitle) !== '' ? trim($this->noteTitle) : null;
        $content = $this->noteContent;

        $embedding = app(EmbeddingService::class)->embedText($content);

        Note::create([
            'user_id' => Auth::id(),
            'title' => $title,
            'content' => $content,
            'embedding' => $embedding,
        ]);

        $this->noteTitle = '';
        $this->noteContent = '';
        $this->successMessage = 'Note saved.';
    }

    public function deleteNote(int $id): void
    {
        Note::query()->where('id', $id)->where('user_id', Auth::id())->delete();
    }

    // — Reminders actions —

    public function saveReminder(): void
    {
        $this->validateOnly('reminderTitle');
        $this->validateOnly('reminderAt');

        Reminder::create([
            'user_id' => Auth::id(),
            'title' => $this->reminderTitle,
            'body' => trim($this->reminderBody) !== '' ? $this->reminderBody : null,
            'remind_at' => $this->reminderAt,
            'channel' => $this->reminderChannel,
            'webhook_url' => $this->reminderChannel === 'webhook' ? $this->reminderWebhookUrl : null,
        ]);

        $this->reminderTitle = '';
        $this->reminderBody = '';
        $this->reminderAt = '';
        $this->reminderChannel = 'email';
        $this->reminderWebhookUrl = '';
        $this->successMessage = 'Reminder set.';
    }

    public function deleteReminder(int $id): void
    {
        Reminder::query()->where('id', $id)->where('user_id', Auth::id())->delete();
    }

    // — Tasks actions —

    public function saveTask(): void
    {
        $this->validateOnly('taskTitle');

        Task::create([
            'user_id' => Auth::id(),
            'title' => $this->taskTitle,
            'description' => trim($this->taskDescription) !== '' ? $this->taskDescription : null,
            'priority' => $this->taskPriority,
            'due_date' => $this->taskDueDate !== '' ? $this->taskDueDate : null,
        ]);

        $this->taskTitle = '';
        $this->taskDescription = '';
        $this->taskPriority = 'medium';
        $this->taskDueDate = '';
        $this->successMessage = 'Task created.';
    }

    public function completeTask(int $id): void
    {
        Task::query()
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);
    }

    public function uncompleteTask(int $id): void
    {
        Task::query()
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['completed_at' => null]);
    }

    public function deleteTask(int $id): void
    {
        Task::query()->where('id', $id)->where('user_id', Auth::id())->delete();
    }

    public function render(): View
    {
        $user = Auth::user();

        $notesQuery = $user->notes()->latest();
        if ($this->noteSearch !== '') {
            $notesQuery->where(function ($q): void {
                $q->where('title', 'ilike', '%'.$this->noteSearch.'%')
                    ->orWhere('content', 'ilike', '%'.$this->noteSearch.'%');
            });
        }

        $remindersQuery = $user->reminders()->orderBy('remind_at');

        $tasksQuery = $user->tasks();
        if (! $this->showCompleted) {
            $tasksQuery->whereNull('completed_at');
        }
        if ($this->taskSearch !== '') {
            $tasksQuery->where('title', 'ilike', '%'.$this->taskSearch.'%');
        }
        $tasksQuery->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
            ->orderBy('due_date')
            ->orderBy('created_at');

        return view('livewire.productivity', [
            'notes' => $notesQuery->paginate(15, pageName: 'notesPage'),
            'reminders' => $remindersQuery->paginate(15, pageName: 'remindersPage'),
            'tasks' => $tasksQuery->paginate(20, pageName: 'tasksPage'),
        ]);
    }
}
