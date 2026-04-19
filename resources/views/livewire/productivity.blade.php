<div class="flex w-full h-full">
    {{-- Sidebar --}}
    <aside class="w-64 flex-shrink-0 flex flex-col border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                @if (auth()->user()->persona?->avatar_path)
                    <img src="{{ asset('storage/'.auth()->user()->persona->avatar_path) }}" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
                @endif
                <span class="font-semibold text-sm">aiPal</span>
            </div>
            <button onclick="Alpine.store('theme').toggle()"
                class="p-1.5 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                title="Toggle dark mode">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
        </div>

        <nav class="flex-1 p-3 space-y-0.5">
            <a href="{{ route('chat') }}"
                class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Chat
            </a>
            <a href="{{ route('memories') }}"
                class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                Memories
            </a>
            <a href="{{ route('documents') }}"
                class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Documents
            </a>
            <span class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Productivity
            </span>
            <a href="{{ route('settings') }}"
                class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
        </nav>

        <div class="p-3 border-t border-gray-200 dark:border-gray-700 space-y-1">
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.invitations') }}"
                class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Invitations
            </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    {{-- Main content --}}
    <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Productivity</h1>

            @if ($successMessage)
                <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">
                    {{ $successMessage }}
                </div>
            @endif

            {{-- Tab bar --}}
            <div class="flex gap-1 mb-6 border-b border-gray-200 dark:border-gray-700">
                <button wire:click="$set('tab', 'tasks')"
                    class="px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'tasks' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    Tasks
                </button>
                <button wire:click="$set('tab', 'reminders')"
                    class="px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'reminders' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    Reminders
                </button>
                <button wire:click="$set('tab', 'notes')"
                    class="px-4 py-2 text-sm font-medium transition-colors {{ $tab === 'notes' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    Notes
                </button>
            </div>

            {{-- ===== TASKS TAB ===== --}}
            @if ($tab === 'tasks')
                {{-- Add task form --}}
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">New task</h2>
                    <div class="space-y-3">
                        <input wire:model="taskTitle" type="text" placeholder="Task title…"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        @error('taskTitle') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                        <textarea wire:model="taskDescription" rows="2" placeholder="Description (optional)…"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>

                        <div class="flex gap-3">
                            <select wire:model="taskPriority"
                                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="low">Low priority</option>
                                <option value="medium" selected>Medium priority</option>
                                <option value="high">High priority</option>
                            </select>
                            <input wire:model="taskDueDate" type="date"
                                class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <button wire:click="saveTask" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors whitespace-nowrap">
                                Add task
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="flex gap-3 mb-4">
                    <input wire:model.live.debounce.300ms="taskSearch" type="search" placeholder="Search tasks…"
                        class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                        <input wire:model.live="showCompleted" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Show completed
                    </label>
                </div>

                {{-- Task list --}}
                @if ($tasks->isEmpty())
                    <div class="text-center py-16 text-sm text-gray-500 dark:text-gray-400">
                        No {{ $showCompleted ? '' : 'pending ' }}tasks. Add one above or ask the assistant to create one.
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($tasks as $task)
                            @php $done = $task->isCompleted(); @endphp
                            <div class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3"
                                 wire:key="task-{{ $task->id }}">
                                {{-- Complete toggle --}}
                                <button wire:click="{{ $done ? 'uncompleteTask' : 'completeTask' }}({{ $task->id }})"
                                    class="flex-shrink-0 mt-0.5 w-5 h-5 rounded border-2 transition-colors {{ $done ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-400 dark:border-gray-500 hover:border-indigo-500' }}"
                                    title="{{ $done ? 'Mark incomplete' : 'Mark complete' }}">
                                    @if ($done)
                                        <svg class="w-full h-full p-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </button>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm {{ $done ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-200' }}">
                                        {{ $task->title }}
                                    </p>
                                    @if ($task->description)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ $task->description }}</p>
                                    @endif
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs px-1.5 py-0.5 rounded font-medium
                                            {{ $task->priority === 'high' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' : ($task->priority === 'medium' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400') }}">
                                            {{ $task->priority }}
                                        </span>
                                        @if ($task->due_date)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Due {{ $task->due_date->toFormattedDateString() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <button wire:click="deleteTask({{ $task->id }})" wire:confirm="Delete this task?"
                                    class="flex-shrink-0 p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors rounded" title="Delete">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $tasks->links() }}</div>
                @endif
            @endif

            {{-- ===== REMINDERS TAB ===== --}}
            @if ($tab === 'reminders')
                {{-- Add reminder form --}}
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">New reminder</h2>
                    <div class="space-y-3">
                        <input wire:model="reminderTitle" type="text" placeholder="Reminder title…"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        @error('reminderTitle') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                        <textarea wire:model="reminderBody" rows="2" placeholder="Message body (optional)…"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>

                        <div class="flex gap-3">
                            <input wire:model="reminderAt" type="datetime-local"
                                class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @error('reminderAt') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                            <select wire:model.live="reminderChannel"
                                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="email">Email</option>
                                <option value="webhook">Webhook</option>
                            </select>
                        </div>

                        @if ($reminderChannel === 'webhook')
                            <input wire:model="reminderWebhookUrl" type="url" placeholder="https://your-webhook-url.com"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        @endif

                        <button wire:click="saveReminder" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            Set reminder
                        </button>
                    </div>
                </div>

                {{-- Reminder list --}}
                @if ($reminders->isEmpty())
                    <div class="text-center py-16 text-sm text-gray-500 dark:text-gray-400">
                        No reminders. Add one above or tell the assistant "remind me to…"
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($reminders as $reminder)
                            <div class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3"
                                 wire:key="reminder-{{ $reminder->id }}">
                                <div class="flex-shrink-0 mt-0.5">
                                    @if ($reminder->sent_at)
                                        <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    @endif
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $reminder->title }}</p>
                                    @if ($reminder->body)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $reminder->body }}</p>
                                    @endif
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $reminder->remind_at->toDayDateTimeString() }}
                                        </span>
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                            {{ $reminder->channel }}
                                        </span>
                                        @if ($reminder->sent_at)
                                            <span class="text-xs text-green-600 dark:text-green-400">sent</span>
                                        @endif
                                    </div>
                                </div>

                                <button wire:click="deleteReminder({{ $reminder->id }})" wire:confirm="Delete this reminder?"
                                    class="flex-shrink-0 p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors rounded" title="Delete">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $reminders->links() }}</div>
                @endif
            @endif

            {{-- ===== NOTES TAB ===== --}}
            @if ($tab === 'notes')
                {{-- Add note form --}}
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">New note</h2>
                    <div class="space-y-3">
                        <input wire:model="noteTitle" type="text" placeholder="Title (optional)…"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">

                        <textarea wire:model="noteContent" rows="4" placeholder="Note content…"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
                        @error('noteContent') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                        <button wire:click="saveNote" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="saveNote">Save note</span>
                            <span wire:loading wire:target="saveNote">Saving…</span>
                        </button>
                    </div>
                </div>

                {{-- Search --}}
                <div class="mb-4">
                    <input wire:model.live.debounce.300ms="noteSearch" type="search" placeholder="Search notes…"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                {{-- Note list --}}
                @if ($notes->isEmpty())
                    <div class="text-center py-16 text-sm text-gray-500 dark:text-gray-400">
                        No notes yet. Write one above or ask the assistant to save something.
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($notes as $note)
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3"
                                 wire:key="note-{{ $note->id }}">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        @if ($note->title)
                                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ $note->title }}</p>
                                        @endif
                                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $note->content }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">{{ $note->created_at->diffForHumans() }}</p>
                                    </div>
                                    <button wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete this note?"
                                        class="flex-shrink-0 p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors rounded" title="Delete">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $notes->links() }}</div>
                @endif
            @endif
        </div>
    </main>
</div>
