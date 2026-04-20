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
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
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

                {{-- Voice --}}
                <div class="p-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        TTS Voice
                        <span class="text-gray-400 font-normal">(OpenAI voice for spoken responses)</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['alloy', 'ash', 'coral', 'echo', 'fable', 'onyx', 'nova', 'shimmer'] as $voice)
                        <button type="button" wire:click="$set('ttsVoice', '{{ $voice }}')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
                                {{ $ttsVoice === $voice
                                    ? 'bg-indigo-600 border-indigo-600 text-white'
                                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-400' }}">
                            {{ ucfirst($voice) }}
                        </button>
                        @endforeach
                    </div>
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

            {{-- Daily Briefing --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Daily Briefing</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Get a morning email summarising your tasks, reminders, and calendar events.</p>

                @if ($briefingSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    Briefing settings saved.
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                    {{-- Enable toggle --}}
                    <div class="p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable daily briefing</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Delivered by email at your chosen time each day.</p>
                        </div>
                        <button
                            wire:click="$toggle('briefingEnabled')"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none
                                {{ $briefingEnabled ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600' }}"
                            role="switch"
                            aria-checked="{{ $briefingEnabled ? 'true' : 'false' }}">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                {{ $briefingEnabled ? 'translate-x-4' : 'translate-x-0' }}"></span>
                        </button>
                    </div>

                    {{-- Delivery time --}}
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Delivery time</label>
                            <input wire:model="briefingTime" type="time"
                                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @error('briefingTime') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timezone</label>
                            <input wire:model="briefingTimezone" type="text" placeholder="e.g. America/New_York"
                                class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @error('briefingTimezone') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Google Calendar connection --}}
                    <div class="p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Google Calendar</p>
                            @if ($googleConnected)
                                <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">Connected — calendar events will appear in your briefing.</p>
                            @else
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Connect to include today's events in your briefing.</p>
                            @endif
                        </div>
                        @if ($googleConnected)
                            <form method="POST" action="{{ route('google.disconnect') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                    Disconnect
                                </button>
                            </form>
                        @else
                            <a href="{{ route('google.auth') }}"
                                class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                Connect Google
                            </a>
                        @endif
                    </div>

                    {{-- Default reminder channel --}}
                    <div class="p-5 space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Reminder Channel</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">When you ask for a reminder without specifying a channel, this is where it will be sent.</p>
                        <select wire:model="defaultReminderChannel"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="email">Email</option>
                            <option value="telegram">Telegram{{ $telegramLinked ? '' : ' (not linked)' }}</option>
                            <option value="whatsapp">WhatsApp{{ auth()->user()->hasWhatsAppLinked() ? '' : ' (not linked)' }}</option>
                            <option value="webhook">Webhook</option>
                        </select>
                        @error('defaultReminderChannel') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Save briefing --}}
                    <div class="p-5 flex justify-end">
                        <button wire:click="saveBriefingSettings" wire:loading.attr="disabled" wire:target="saveBriefingSettings"
                            class="px-5 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="saveBriefingSettings">Save briefing settings</span>
                            <span wire:loading wire:target="saveBriefingSettings">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Telegram --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Telegram</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Link your Telegram account to chat with your assistant and receive reminders via Telegram.</p>

                @if ($telegramSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    Telegram settings saved.
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-100 dark:divide-gray-700">
                    <div class="p-5 space-y-4">
                        @if ($telegramLinked)
                        <div class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Linked — Chat ID: {{ $telegramLinked ? auth()->user()->telegram_chat_id : '' }}
                        </div>
                        @endif

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Telegram Chat ID</label>
                            <input wire:model="telegramChatId" type="text" placeholder="e.g. 123456789"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                            @error('telegramChatId') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Start a chat with your bot and send <code>/start</code> — it will reply with your chat ID.</p>
                        </div>
                    </div>

                    <div class="p-5 flex justify-end">
                        <button wire:click="saveTelegramSettings" wire:loading.attr="disabled" wire:target="saveTelegramSettings"
                            class="px-5 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="saveTelegramSettings">Save Telegram settings</span>
                            <span wire:loading wire:target="saveTelegramSettings">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- WhatsApp --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">WhatsApp</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Link your WhatsApp number to chat with your assistant and receive reminders via WhatsApp.</p>

                @if ($whatsappSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    WhatsApp settings saved.
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-100 dark:divide-gray-700">
                    <div class="p-5 space-y-4">
                        @if (auth()->user()->hasWhatsAppLinked())
                        <div class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Linked — Phone: {{ auth()->user()->whatsapp_phone }}
                        </div>
                        @endif

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">WhatsApp Phone Number</label>
                            <input wire:model="whatsappPhone" type="text" placeholder="e.g. 201234567890"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                            @error('whatsappPhone') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Enter your number in international format without the + sign (e.g. 201234567890 for Egypt).</p>
                        </div>
                    </div>

                    <div class="p-5 flex justify-end">
                        <button wire:click="saveWhatsAppSettings" wire:loading.attr="disabled" wire:target="saveWhatsAppSettings"
                            class="px-5 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="saveWhatsAppSettings">Save WhatsApp settings</span>
                            <span wire:loading wire:target="saveWhatsAppSettings">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- GitLab --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">GitLab</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Connect your GitLab account to list MRs, view commits, and create issues.</p>

                @if ($gitlabSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    GitLab settings saved.
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-100 dark:divide-gray-700">
                    <div class="p-5 space-y-4">
                        @if (auth()->user()->hasGitLabConnected())
                        <div class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Connected — {{ auth()->user()->gitlab_host }}
                        </div>
                        @endif

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">GitLab Host URL</label>
                            <input wire:model="gitlabHost" type="url" placeholder="https://gitlab.com"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                            @error('gitlabHost') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Use <code>https://gitlab.com</code> or your self-hosted URL.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Personal Access Token</label>
                            <input wire:model="gitlabToken" type="password" placeholder="glpat-xxxxxxxxxxxxxxxxxxxx"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                            @error('gitlabToken') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Generate at GitLab → User Settings → Access Tokens. Required scopes: <code>api</code>, <code>read_repository</code>.</p>
                        </div>
                    </div>

                    <div class="p-5 flex justify-end">
                        <button wire:click="saveGitLabSettings" wire:loading.attr="disabled" wire:target="saveGitLabSettings"
                            class="px-5 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="saveGitLabSettings">Save GitLab settings</span>
                            <span wire:loading wire:target="saveGitLabSettings">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Jira --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Jira</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Connect your Jira account so the assistant can search issues, create tickets, and update statuses.</p>

                @if ($jiraSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    Jira settings saved.
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-100 dark:divide-gray-700">
                    <div class="p-5 space-y-4">
                        @if (auth()->user()->hasJiraConnected())
                        <div class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Connected — {{ auth()->user()->jira_host }}
                        </div>
                        @endif

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Jira Host URL</label>
                            <input wire:model="jiraHost" type="url" placeholder="https://yourcompany.atlassian.net"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                            @error('jiraHost') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Jira Email</label>
                            <input wire:model="jiraEmail" type="email" placeholder="you@company.com"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                            @error('jiraEmail') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">API Token</label>
                            <input wire:model="jiraToken" type="password" placeholder="Atlassian API token"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                            @error('jiraToken') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Generate at <a href="https://id.atlassian.com/manage-profile/security/api-tokens" target="_blank" class="underline">id.atlassian.com/manage-profile/security/api-tokens</a></p>
                        </div>
                    </div>

                    <div class="p-5 flex justify-end">
                        <button wire:click="saveJiraSettings" wire:loading.attr="disabled" wire:target="saveJiraSettings"
                            class="px-5 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="saveJiraSettings">Save Jira settings</span>
                            <span wire:loading wire:target="saveJiraSettings">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- AI Tools --}}
            <div class="mt-8 mb-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">AI Tools</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Enable or disable the tools your assistant can use. Changes take effect immediately.</p>

                @if ($toolSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    Tool settings updated.
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($tools as $category => $categoryTools)
                    <div class="p-5">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">{{ ucfirst($category) }}</h3>
                        <div class="space-y-3">
                            @foreach ($categoryTools as $tool)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $tool['label'] }}</span>
                                <button
                                    wire:click="toggleTool('{{ $tool['name'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleTool('{{ $tool['name'] }}')"
                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none
                                        {{ $tool['enabled'] ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600' }}"
                                    role="switch"
                                    aria-checked="{{ $tool['enabled'] ? 'true' : 'false' }}"
                                    title="{{ $tool['enabled'] ? 'Disable' : 'Enable' }} {{ $tool['label'] }}">
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                        {{ $tool['enabled'] ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </main>
</div>
