<div class="flex w-full h-full">
    {{-- Sidebar --}}
    <aside class="w-64 flex-shrink-0 flex flex-col border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
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
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </span>
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
        <div class="max-w-2xl mx-auto">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Assistant Settings</h1>

            @if ($saved)
            <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                 x-data x-init="setTimeout(() => $el.remove(), 3000)">
                Settings saved successfully.
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">

                {{-- Avatar --}}
                <div class="p-5 flex items-center gap-5">
                    <div class="flex-shrink-0">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="Avatar" class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600">
                        @else
                            <div class="w-20 h-20 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center border-2 border-dashed border-indigo-300 dark:border-indigo-700">
                                <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assistant avatar</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Generated via AI — reflects your assistant's name and personality.</p>
                        @if ($avatarQueued)
                            <p class="text-xs text-indigo-600 dark:text-indigo-400">Avatar generation queued — refresh in ~30 seconds.</p>
                        @else
                            <button wire:click="generateAvatar" wire:loading.attr="disabled" wire:target="generateAvatar"
                                class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg border border-indigo-300 dark:border-indigo-700 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 disabled:opacity-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span wire:loading.remove wire:target="generateAvatar">{{ $avatarUrl ? 'Regenerate avatar' : 'Generate avatar' }}</span>
                                <span wire:loading wire:target="generateAvatar">Queuing…</span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Name --}}
                <div class="p-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assistant name</label>
                    <input wire:model="assistantName" type="text"
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    @error('assistantName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Tone --}}
                <div class="p-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tone</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['friendly', 'professional', 'enthusiastic', 'calm', 'direct'] as $option)
                        <button type="button" wire:click="$set('tone', '{{ $option }}')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
                                {{ $tone === $option
                                    ? 'bg-indigo-600 border-indigo-600 text-white'
                                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-400' }}">
                            {{ ucfirst($option) }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Formality --}}
                <div class="p-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Formality</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['casual', 'semi-formal', 'formal'] as $option)
                        <button type="button" wire:click="$set('formality', '{{ $option }}')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
                                {{ $formality === $option
                                    ? 'bg-indigo-600 border-indigo-600 text-white'
                                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-400' }}">
                            {{ ucfirst($option) }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Humor --}}
                <div class="p-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Humor</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['none', 'light', 'moderate', 'frequent'] as $option)
                        <button type="button" wire:click="$set('humorLevel', '{{ $option }}')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
                                {{ $humorLevel === $option
                                    ? 'bg-indigo-600 border-indigo-600 text-white'
                                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-400' }}">
                            {{ ucfirst($option) }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Backstory --}}
                <div class="p-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Backstory / Role
                    </label>
                    <textarea wire:model="backstory" rows="3"
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
                    @error('backstory') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- System prompt (editable) --}}
                <div class="p-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        System prompt
                        <span class="text-gray-400 font-normal">(edit directly or regenerate)</span>
                    </label>
                    <textarea wire:model="systemPrompt" rows="6"
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm font-mono text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
                </div>

                {{-- Export / Import --}}
                <div class="p-5 space-y-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Persona backup</h3>

                    <div class="flex flex-wrap gap-3 items-start">
                        {{-- Export --}}
                        <a href="{{ route('persona.export') }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export JSON
                        </a>

                        {{-- Import --}}
                        <div class="flex-1 min-w-[220px]">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Import from JSON</label>
                            <div class="flex gap-2">
                                <input type="file" wire:model="importFile" accept=".json"
                                    class="flex-1 text-xs text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-400 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 transition-colors">
                                <button wire:click="import" wire:loading.attr="disabled" wire:target="import"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors whitespace-nowrap">
                                    <span wire:loading.remove wire:target="import">Import</span>
                                    <span wire:loading wire:target="import">Importing…</span>
                                </button>
                            </div>
                            @error('importFile') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            @if ($importError)
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $importError }}</p>
                            @endif
                            @if ($importSuccess)
                                <p class="mt-1 text-xs text-green-600 dark:text-green-400">Persona loaded from file — review and save changes.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="p-5 flex items-center justify-between gap-3">
                    <button wire:click="regenerate" wire:loading.attr="disabled" wire:target="regenerate"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-indigo-300 dark:border-indigo-700 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 disabled:opacity-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span wire:loading.remove wire:target="regenerate">Regenerate prompt</span>
                        <span wire:loading wire:target="regenerate">Regenerating…</span>
                    </button>

                    <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        class="px-5 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        <span wire:loading.remove wire:target="save">Save changes</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>
