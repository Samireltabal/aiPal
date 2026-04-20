<div class="flex flex-col lg:flex-row w-full h-full">
    {{-- Sidebar --}}
    <x-nav-sidebar active="memories" />

    {{-- Main content --}}
    <main class="flex-1 overflow-y-auto pt-4 lg:pt-8 px-4 lg:px-8 pb-8">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Memories</h1>
                <a href="{{ route('memories.export') }}"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export JSON
                </a>
            </div>

            {{-- Import --}}
            <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Import memories</h2>
                <div class="flex gap-3 items-end">
                    <div class="flex-1">
                        <input type="file" wire:model="importFile" accept=".json"
                            class="w-full text-xs text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-400 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 transition-colors">
                    </div>
                    <button wire:click="import" wire:loading.attr="disabled" wire:target="import"
                        class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors whitespace-nowrap">
                        <span wire:loading.remove wire:target="import">Import</span>
                        <span wire:loading wire:target="import">Importing…</span>
                    </button>
                </div>
                @error('importFile') <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                @if ($importError)
                    <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $importError }}</p>
                @endif
                @if ($importSuccess)
                    <p class="mt-2 text-xs text-green-600 dark:text-green-400">
                        Imported {{ $importedCount }} new {{ Str::plural('memory', $importedCount) }}.
                    </p>
                @endif
            </div>

            {{-- Search --}}
            <div class="mb-4">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search memories…"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>

            {{-- Memory list --}}
            @if ($memories->isEmpty())
                <div class="text-center py-16 text-sm text-gray-500 dark:text-gray-400">
                    @if ($search)
                        No memories match "{{ $search }}".
                    @else
                        No memories yet. Start chatting — the assistant will remember facts about you automatically.
                    @endif
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($memories as $memory)
                        <div class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3"
                             wire:key="memory-{{ $memory->id }}">
                            <p class="flex-1 text-sm text-gray-800 dark:text-gray-200">{{ $memory->content }}</p>
                            <div class="flex-shrink-0 flex items-center gap-2">
                                <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                                    {{ $memory->created_at->diffForHumans() }}
                                </span>
                                <button wire:click="delete({{ $memory->id }})"
                                    wire:confirm="Delete this memory?"
                                    class="p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors rounded"
                                    title="Delete">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $memories->links() }}
                </div>
            @endif
        </div>
    </main>
</div>
