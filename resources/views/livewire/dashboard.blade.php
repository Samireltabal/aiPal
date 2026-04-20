<div class="flex w-full h-full">
    {{-- Sidebar --}}
    <aside class="w-64 flex-shrink-0 flex flex-col border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                @if (auth()->user()->persona?->avatar_path)
                    <img src="{{ asset('storage/'.auth()->user()->persona->avatar_path) }}" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
                @endif
                <span class="font-semibold text-sm">{{ $personaName }}</span>
            </div>
            <button onclick="Alpine.store('theme').toggle()"
                class="p-1.5 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                title="Toggle dark mode"
                x-data>
                <svg x-show="!$store.theme.dark" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="$store.theme.dark" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
        </div>

        <nav class="flex-1 p-3 space-y-0.5">
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 font-medium">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
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
            <a href="{{ route('productivity') }}"
                class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Productivity
            </a>
            <a href="{{ route('settings') }}"
                class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.invitations') }}"
                class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Invitations
            </a>
            @endif
        </nav>

        <div class="p-3 border-t border-gray-200 dark:border-gray-700">
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
    <main class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">

        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $greeting }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ now()->format('l, F j, Y') }}</p>
            </div>
            <a href="{{ route('chat') }}"
                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                New Chat
            </a>
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
                <div class="px-5 py-5">
                    @if (! $gmailConnected)
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-400 dark:text-gray-500 mb-3">Google account not connected.</p>
                            <a href="{{ route('settings') }}" class="text-xs text-indigo-500 hover:text-indigo-600">Connect in Settings →</a>
                        </div>
                    @elseif (! $emailsFetched)
                        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">
                            Click <strong>Fetch</strong> to check your inbox.
                        </p>
                    @else
                        <div class="flex items-center gap-4">
                            <div class="text-4xl font-bold text-gray-900 dark:text-white">{{ $unreadEmailsCount }}</div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 font-medium">recent emails</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">in your inbox</p>
                            </div>
                        </div>
                        @if ($unreadEmailsCount > 0)
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">
                                Ask <a href="{{ route('chat') }}" class="text-indigo-500 hover:underline">the assistant</a> to summarize or reply to your emails.
                            </p>
                        @endif
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
