@if ($open)
<div
    x-data
    x-on:keydown.escape.window="$wire.close()"
    class="fixed inset-0 z-50 flex items-start justify-center pt-[10vh] px-4"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/50"
        wire:click="close"
    ></div>

    {{-- Modal --}}
    <div class="relative w-full max-w-xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">

        {{-- Search input --}}
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                wire:model.live.debounce.300ms="query"
                type="text"
                placeholder="Search memories, notes, tasks, documents…"
                autofocus
                class="flex-1 text-sm bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none"
                x-on:keydown.escape="$wire.close()"
            >
            @if ($searching)
                <svg class="w-4 h-4 animate-spin text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            @else
                <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-xs font-medium text-gray-400 bg-gray-100 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">Esc</kbd>
            @endif
        </div>

        {{-- Results --}}
        @if (count($results) > 0)
            <ul class="max-h-96 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-700/50">
                @foreach ($results as $result)
                    <li>
                        <a href="{{ $result['url'] }}"
                            wire:navigate
                            wire:click="close"
                            class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">

                            {{-- Type icon --}}
                            <span class="flex-shrink-0 mt-0.5 w-6 h-6 rounded-md flex items-center justify-center
                                @if($result['type'] === 'memory') bg-purple-100 dark:bg-purple-900/40
                                @elseif($result['type'] === 'note') bg-amber-100 dark:bg-amber-900/40
                                @elseif($result['type'] === 'task') bg-indigo-100 dark:bg-indigo-900/40
                                @else bg-emerald-100 dark:bg-emerald-900/40
                                @endif">
                                @if($result['type'] === 'memory')
                                    <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                @elseif($result['type'] === 'note')
                                    <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                @elseif($result['type'] === 'task')
                                    <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                @else
                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $result['title'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $result['excerpt'] }}</p>
                            </div>

                            <span class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500 capitalize mt-0.5">{{ $result['type'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400 dark:text-gray-500">
                {{ count($results) }} result{{ count($results) !== 1 ? 's' : '' }}
            </div>

        @elseif (strlen(trim($query)) >= 2 && !$searching)
            <div class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                No results for "{{ $query }}"
            </div>
        @else
            <div class="px-4 py-8 text-center text-xs text-gray-400 dark:text-gray-500">
                Search across your memories, notes, tasks, and documents
            </div>
        @endif
    </div>
</div>
@endif
