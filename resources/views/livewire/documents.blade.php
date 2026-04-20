<div class="flex flex-col lg:flex-row w-full h-full">
    {{-- Sidebar --}}
    <x-nav-sidebar active="documents" />

    {{-- Main content --}}
    <main class="flex-1 overflow-y-auto pt-4 lg:pt-8 px-4 lg:px-8 pb-8">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Knowledge Base</h1>

            {{-- Upload --}}
            <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Upload a document</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Supported: text, markdown, code files (PHP, JS, Python, Go, etc.). Max 20 MB.</p>
                <div class="flex gap-3 items-end">
                    <div class="flex-1">
                        <input type="file" wire:model="uploadFile"
                            class="w-full text-xs text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-400 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 transition-colors">
                    </div>
                    <button wire:click="uploadDocument" wire:loading.attr="disabled" wire:target="uploadDocument"
                        class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors whitespace-nowrap">
                        <span wire:loading.remove wire:target="upload">Upload</span>
                        <span wire:loading wire:target="uploadDocument">Uploading…</span>
                    </button>
                </div>
                @error('uploadFile') <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                @if ($uploadError)
                    <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $uploadError }}</p>
                @endif
                @if ($uploadQueued)
                    <p class="mt-2 text-xs text-green-600 dark:text-green-400">
                        Document queued for processing. Chunks will be embedded in the background — refresh in a moment.
                    </p>
                @endif
            </div>

            {{-- Search --}}
            <div class="mb-4">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search documents…"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>

            {{-- Document list --}}
            @if ($documents->isEmpty())
                <div class="text-center py-16 text-sm text-gray-500 dark:text-gray-400">
                    @if ($search)
                        No documents match "{{ $search }}".
                    @else
                        No documents yet. Upload a file and the assistant will be able to answer questions about it.
                    @endif
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($documents as $document)
                        <div class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3"
                             wire:key="document-{{ $document->id }}">
                            {{-- Status icon --}}
                            <div class="flex-shrink-0">
                                @if ($document->status === 'ready')
                                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @elseif ($document->status === 'processing')
                                    <svg class="w-5 h-5 text-yellow-500 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                @elseif ($document->status === 'failed')
                                    <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </div>

                            {{-- Name + meta --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $document->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($document->size / 1024, 1) }} KB
                                    · {{ $document->chunks_count ?? $document->chunks()->count() }} chunks
                                    @if ($document->status === 'failed')
                                        · <span class="text-red-500" title="{{ $document->error_message }}">failed</span>
                                    @else
                                        · {{ $document->status }}
                                    @endif
                                    · {{ $document->created_at->diffForHumans() }}
                                </p>
                            </div>

                            {{-- Delete --}}
                            <button wire:click="delete({{ $document->id }})"
                                wire:confirm="Delete this document and all its chunks?"
                                class="flex-shrink-0 p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors rounded"
                                title="Delete">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </main>
</div>
