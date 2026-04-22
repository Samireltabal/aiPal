<div class="flex flex-col lg:flex-row w-full h-full">
    {{-- Sidebar --}}
    <x-nav-sidebar active="dashboard" />

    {{-- Main content --}}
    <main class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900 pt-4 lg:pt-8 px-4 lg:px-6 pb-6">

        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $greeting }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ now()->format('l, F j, Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if ($activeModel)
                    <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $activeModel }}</span>
                    </div>
                @endif
                <a href="{{ route('chat') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    New Chat
                </a>
            </div>
        </div>

        {{-- Stats row --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">Conversations</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $conversationsCount }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">Memories</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $memoriesCount }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">Documents</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $documentsCount }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">Notes</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $notesCount }}</p>
            </div>
        </div>

        {{-- Weather card --}}
        <div class="mb-6">
            <livewire:weather-card />
        </div>

        {{-- Main grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Tasks --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Pending Tasks</h2>
                    </div>
                    @if ($pendingTasksCount > 0)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300">
                            {{ $pendingTasksCount }}
                        </span>
                    @endif
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700">
                    @forelse ($pendingTasks as $task)
                        <div class="flex items-center gap-3 px-5 py-3">
                            <span class="flex-shrink-0 w-2 h-2 rounded-full
                                @if ($task['priority'] === 'high') bg-red-500
                                @elseif ($task['priority'] === 'medium') bg-yellow-400
                                @else bg-gray-300 dark:bg-gray-600
                                @endif">
                            </span>
                            <span class="flex-1 text-sm text-gray-800 dark:text-gray-200 truncate">{{ $task['title'] }}</span>
                            @if ($task['due_date'])
                                <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">{{ $task['due_date'] }}</span>
                            @endif
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            All caught up! No pending tasks.
                        </div>
                    @endforelse
                </div>
                @if ($pendingTasksCount > 5)
                    <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('productivity') }}" class="text-xs text-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400">
                            View all {{ $pendingTasksCount }} tasks →
                        </a>
                    </div>
                @endif
            </div>

            {{-- Reminders --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Upcoming Reminders</h2>
                    </div>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700">
                    @forelse ($upcomingReminders as $reminder)
                        <div class="px-5 py-3">
                            <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $reminder['title'] }}</p>
                            <p class="text-xs text-amber-500 mt-0.5">{{ $reminder['remind_at'] }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            No upcoming reminders.
                        </div>
                    @endforelse
                </div>
                <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('productivity') }}" class="text-xs text-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400">
                        Manage reminders →
                    </a>
                </div>
            </div>

            {{-- Gmail --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Gmail</h2>
                    </div>
                    @if ($gmailConnected)
                        <button wire:click="fetchEmails"
                            wire:loading.attr="disabled"
                            class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors">
                            <svg wire:loading wire:target="fetchEmails" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <svg wire:loading.remove wire:target="fetchEmails" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Fetch
                        </button>
                    @endif
                </div>
                <div class="@if($emailsFetched && count($recentEmails) > 0) divide-y divide-gray-50 dark:divide-gray-700 @else px-5 py-5 @endif">
                    @if (! $gmailConnected)
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-400 dark:text-gray-500 mb-3">Google account not connected.</p>
                            <a href="{{ route('settings') }}" class="text-xs text-indigo-500 hover:text-indigo-600">Connect in Settings →</a>
                        </div>
                    @elseif (! $emailsFetched)
                        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">
                            Click <strong>Fetch</strong> to check your inbox.
                        </p>
                    @elseif (count($recentEmails) === 0)
                        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">Your inbox is empty.</p>
                    @else
                        @foreach ($recentEmails as $email)
                            <div class="flex items-start gap-3 px-5 py-3">
                                @if ($email['isUnread'])
                                    <span class="flex-shrink-0 w-2 h-2 rounded-full bg-blue-500 mt-1.5"></span>
                                @else
                                    <span class="flex-shrink-0 w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600 mt-1.5"></span>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm {{ $email['isUnread'] ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300' }} truncate">{{ $email['subject'] }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ $email['from'] }}</p>
                                </div>
                            </div>
                        @endforeach
                        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            @if ($unreadEmailsCount > 0)
                                <span class="text-xs text-blue-500 font-medium">{{ $unreadEmailsCount }} unread</span>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">All read</span>
                            @endif
                            <a href="{{ route('chat') }}" class="text-xs text-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400">Ask assistant →</a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Calendar --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Today's Calendar</h2>
                    </div>
                    @if ($calendarConnected)
                        <button wire:click="fetchCalendar"
                            wire:loading.attr="disabled"
                            class="flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors">
                            <svg wire:loading wire:target="fetchCalendar" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <svg wire:loading.remove wire:target="fetchCalendar" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Fetch
                        </button>
                    @endif
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700">
                    @if (! $calendarConnected)
                        <div class="px-5 py-5 text-center">
                            <p class="text-sm text-gray-400 dark:text-gray-500 mb-3">Google account not connected.</p>
                            <a href="{{ route('settings') }}" class="text-xs text-indigo-500 hover:text-indigo-600">Connect in Settings →</a>
                        </div>
                    @elseif (! $calendarFetched)
                        <div class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            Click <strong>Fetch</strong> to load today's events.
                        </div>
                    @elseif (count($calendarEvents) === 0)
                        <div class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            No events today.
                        </div>
                    @else
                        @foreach ($calendarEvents as $event)
                            <div class="flex items-start gap-3 px-5 py-3">
                                <div class="w-1.5 h-1.5 rounded-full bg-green-500 mt-1.5 flex-shrink-0"></div>
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $event['title'] }}</p>
                                    @if ($event['time'])
                                        <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">{{ $event['time'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

        </div>
    </main>
</div>
