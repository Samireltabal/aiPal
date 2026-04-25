<div class="flex flex-col lg:flex-row w-full h-full">
    {{-- Sidebar --}}
    <x-nav-sidebar active="settings" />

    {{-- Main content --}}
    <main class="flex-1 overflow-y-auto pt-4 lg:pt-8 px-4 lg:px-8 pb-8">
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

            {{-- Morning Focus Cast --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Morning Focus Cast</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">A short daily push — the top 3 things to focus on today, based on your calendar, reminders, and tasks. Sent via Telegram if linked, otherwise email.</p>

                @if ($briefingSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    Focus cast settings saved.
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                    {{-- Enable toggle --}}
                    <div class="p-5 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable morning focus cast</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Delivered at your chosen local time each day.</p>
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

                    {{-- Google accounts (multi-account) --}}
                    <div class="p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Google accounts</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Mail + Calendar. Add as many as you need; the active context picks which account tools use.</p>
                            </div>
                            <a href="{{ route('google.auth') }}"
                                class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                                {{ $googleConnections->isEmpty() ? 'Connect Google' : 'Add another' }}
                            </a>
                        </div>

                        @if ($googleConnections->isNotEmpty())
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700 border border-gray-100 dark:border-gray-700 rounded-lg">
                            @foreach ($googleConnections as $conn)
                                <li class="flex items-center justify-between gap-3 px-3 py-2" wire:key="google-{{ $conn->id }}">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                            {{ $conn->label }}
                                            @if ($conn->is_default)<span class="ml-2 text-[10px] uppercase tracking-wide text-indigo-600 dark:text-indigo-400">default</span>@endif
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ str_starts_with((string) $conn->identifier, 'primary-') ? 'Reconnect to populate email' : $conn->identifier }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if (! $conn->is_default)
                                        <button wire:click="setDefaultIntegrationConnection({{ $conn->id }})" class="text-[11px] px-2 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Make default</button>
                                        @endif
                                        <button wire:click="removeIntegrationConnection({{ $conn->id }})" wire:confirm="Remove this Google account?" class="text-[11px] px-2 py-1 rounded border border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30">Remove</button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>

                    {{-- Microsoft accounts (multi-account) --}}
                    <div class="p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Microsoft accounts</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Outlook + Microsoft Calendar. Add both personal and work accounts; the active context picks which one tools use.</p>
                            </div>
                            <a href="{{ route('microsoft.auth') }}"
                                class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 23 23" fill="none"><rect x="1" y="1" width="10" height="10" fill="#F25022"/><rect x="12" y="1" width="10" height="10" fill="#7FBA00"/><rect x="1" y="12" width="10" height="10" fill="#00A4EF"/><rect x="12" y="12" width="10" height="10" fill="#FFB900"/></svg>
                                {{ $microsoftConnections->isEmpty() ? 'Connect Microsoft' : 'Add another' }}
                            </a>
                        </div>

                        @if ($microsoftConnections->isNotEmpty())
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700 border border-gray-100 dark:border-gray-700 rounded-lg">
                            @foreach ($microsoftConnections as $conn)
                                <li class="flex items-center justify-between gap-3 px-3 py-2" wire:key="microsoft-{{ $conn->id }}">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                            {{ $conn->label }}
                                            @if ($conn->is_default)<span class="ml-2 text-[10px] uppercase tracking-wide text-indigo-600 dark:text-indigo-400">default</span>@endif
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ str_starts_with((string) $conn->identifier, 'primary-') ? 'Reconnect to populate email' : $conn->identifier }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if (! $conn->is_default)
                                        <button wire:click="setDefaultIntegrationConnection({{ $conn->id }})" class="text-[11px] px-2 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Make default</button>
                                        @endif
                                        <button wire:click="removeIntegrationConnection({{ $conn->id }})" wire:confirm="Remove this Microsoft account?" class="text-[11px] px-2 py-1 rounded border border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30">Remove</button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
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

            {{-- Forward-to-aiPal (inbound email) --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Forward-to-aiPal</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Forward any email to your personal aiPal address and it will be auto-classified into a task, reminder, note, or memory. You'll get a short confirmation back.
                </p>

                @if ($inboundEmailSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    Forwarding settings updated.
                </div>
                @endif

                @php($inboundAddress = auth()->user()->inboundEmailAddress())

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                    @if ($inboundAddress === null)
                        <div class="p-5 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Email forwarding is off</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Enable to generate a personal forwarding address.</p>
                            </div>
                            <button wire:click="enableInboundEmail" wire:loading.attr="disabled" wire:target="enableInboundEmail"
                                class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="enableInboundEmail">Enable forwarding</span>
                                <span wire:loading wire:target="enableInboundEmail">Generating…</span>
                            </button>
                        </div>
                    @else
                        <div class="p-5">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Your forwarding address</p>
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-mono text-xs text-gray-800 dark:text-gray-200 break-all">
                                {{ $inboundAddress }}
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Forward any email to this address. Subject and body are classified; attachments are ignored in this version.</p>
                        </div>
                        <div class="p-5 flex items-center justify-end gap-2">
                            <button wire:click="regenerateInboundEmail" wire:loading.attr="disabled" wire:target="regenerateInboundEmail"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                                Regenerate address
                            </button>
                            <button wire:click="disableInboundEmail" wire:loading.attr="disabled" wire:target="disableInboundEmail"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30 disabled:opacity-50">
                                Disable
                            </button>
                        </div>
                    @endif
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
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Link your WhatsApp number to chat with your assistant and receive reminders via WhatsApp. Voice notes are transcribed via a third-party speech-to-text service before being processed; capped at {{ (int) config('services.whatsapp.voice_daily_limit', 30) }} voice messages per day.</p>

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

            {{-- GitLab (multi-account) --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">GitLab</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Connect one or more GitLab accounts. The active context picks which account tools use.</p>

                @if ($gitlabSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    GitLab account added.
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-100 dark:divide-gray-700">
                    @if ($gitlabConnections->isNotEmpty())
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($gitlabConnections as $conn)
                            <li class="flex items-center justify-between gap-3 px-5 py-3" wire:key="gitlab-{{ $conn->id }}">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                        {{ $conn->label }}
                                        @if ($conn->is_default)<span class="ml-2 text-[10px] uppercase tracking-wide text-indigo-600 dark:text-indigo-400">default</span>@endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $conn->credential('host') ?: $conn->identifier }}</p>
                                </div>
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    @if (! $conn->is_default)
                                    <button wire:click="setDefaultIntegrationConnection({{ $conn->id }})" class="text-[11px] px-2 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Make default</button>
                                    @endif
                                    <button wire:click="removeIntegrationConnection({{ $conn->id }})" wire:confirm="Remove this GitLab account?" class="text-[11px] px-2 py-1 rounded border border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30">Remove</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @endif

                    <div class="p-5 space-y-4">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Add a GitLab account</p>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Label (optional)</label>
                            <input wire:model="gitlabLabel" type="text" placeholder="Work, Personal, …"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">GitLab Host URL</label>
                            <input wire:model="gitlabHost" type="url" placeholder="https://gitlab.com"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                            @error('gitlabHost') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Personal Access Token</label>
                            <input wire:model="gitlabToken" type="password" placeholder="glpat-xxxxxxxxxxxxxxxxxxxx"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                            @error('gitlabToken') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Required scopes: <code>api</code>, <code>read_repository</code>.</p>
                        </div>
                    </div>

                    <div class="p-5 flex justify-end">
                        <button wire:click="addGitLabAccount" wire:loading.attr="disabled" wire:target="addGitLabAccount"
                            class="px-5 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="addGitLabAccount">Add GitLab account</span>
                            <span wire:loading wire:target="addGitLabAccount">Adding…</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- GitHub (multi-account) --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">GitHub</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Connect one or more GitHub accounts. The active context picks which account tools use.</p>

                @if ($githubSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    GitHub account added.
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-100 dark:divide-gray-700">
                    @if ($githubConnections->isNotEmpty())
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($githubConnections as $conn)
                            <li class="flex items-center justify-between gap-3 px-5 py-3" wire:key="github-{{ $conn->id }}">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                        {{ $conn->label }}
                                        @if ($conn->is_default)<span class="ml-2 text-[10px] uppercase tracking-wide text-indigo-600 dark:text-indigo-400">default</span>@endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">github.com</p>
                                </div>
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    @if (! $conn->is_default)
                                    <button wire:click="setDefaultIntegrationConnection({{ $conn->id }})" class="text-[11px] px-2 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Make default</button>
                                    @endif
                                    <button wire:click="removeIntegrationConnection({{ $conn->id }})" wire:confirm="Remove this GitHub account?" class="text-[11px] px-2 py-1 rounded border border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30">Remove</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @endif

                    <div class="p-5 space-y-4">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Add a GitHub account</p>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Label (optional)</label>
                            <input wire:model="githubLabel" type="text" placeholder="Work, Personal, …"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Personal Access Token</label>
                            <input wire:model="githubToken" type="password" placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
                                class="w-full text-sm px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('githubToken') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Required scopes: <code>repo</code>, <code>read:user</code>.</p>
                        </div>

                        <button wire:click="addGitHubAccount" wire:loading.attr="disabled" wire:target="addGitHubAccount"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="addGitHubAccount">Add GitHub account</span>
                            <span wire:loading wire:target="addGitHubAccount">Adding…</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Jira (multi-account) --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Jira</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Connect one or more Jira accounts. The active context picks which account tools use.</p>

                @if ($jiraSaved)
                <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                     x-data x-init="setTimeout(() => $el.remove(), 2500)">
                    Jira account added.
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-100 dark:divide-gray-700">
                    @if ($jiraConnections->isNotEmpty())
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($jiraConnections as $conn)
                            <li class="flex items-center justify-between gap-3 px-5 py-3" wire:key="jira-{{ $conn->id }}">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                        {{ $conn->label }}
                                        @if ($conn->is_default)<span class="ml-2 text-[10px] uppercase tracking-wide text-indigo-600 dark:text-indigo-400">default</span>@endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $conn->credential('host') ?: $conn->identifier }}</p>
                                </div>
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    @if (! $conn->is_default)
                                    <button wire:click="setDefaultIntegrationConnection({{ $conn->id }})" class="text-[11px] px-2 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Make default</button>
                                    @endif
                                    <button wire:click="removeIntegrationConnection({{ $conn->id }})" wire:confirm="Remove this Jira account?" class="text-[11px] px-2 py-1 rounded border border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30">Remove</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @endif

                    <div class="p-5 space-y-4">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Add a Jira account</p>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Label (optional)</label>
                            <input wire:model="jiraLabel" type="text" placeholder="Work, Side project, …"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                        </div>
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
                        <button wire:click="addJiraAccount" wire:loading.attr="disabled" wire:target="addJiraAccount"
                            class="px-5 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            <span wire:loading.remove wire:target="addJiraAccount">Add Jira account</span>
                            <span wire:loading wire:target="addJiraAccount">Adding…</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- AI Tools --}}
            {{-- Push Notifications --}}
            <div class="mt-8">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Push Notifications</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Receive reminders and alerts as native push notifications, even when the browser is closed.</p>

                <div x-data="pushToggle({{ auth()->user()->push_notifications_enabled ? 'true' : 'false' }}, '{{ csrf_token() }}')"
                    x-show="'serviceWorker' in navigator && 'PushManager' in window">
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable push notifications</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Requires browser permission. Works on Android, macOS, and Windows.</p>
                        </div>
                        <button @click="toggle()"
                            :disabled="loading"
                            :class="enabled ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600'"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 disabled:opacity-50"
                            :aria-checked="enabled ? 'true' : 'false'">
                            <span :class="enabled ? 'translate-x-4' : 'translate-x-0'"
                                class="inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200"></span>
                        </button>
                    </div>
                </div>
                <p x-data x-show="!('serviceWorker' in navigator && 'PushManager' in window)"
                    class="text-sm text-gray-400 dark:text-gray-500">Push notifications are not supported in this browser.</p>

                @if (auth()->user()->push_notifications_enabled)
                    <div class="mt-3 flex items-center gap-3">
                        <button wire:click="sendTestPush"
                            wire:loading.attr="disabled"
                            class="flex items-center gap-2 text-xs px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors">
                            <svg wire:loading wire:target="sendTestPush" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <svg wire:loading.remove wire:target="sendTestPush" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            Send test notification
                        </button>
                        @if ($pushTestSent)
                            <span class="text-xs text-green-600 dark:text-green-400"
                                x-data x-init="setTimeout(() => $el.remove(), 3000)">
                                Sent! Check your notifications.
                            </span>
                        @endif
                    </div>
                @endif
            </div>

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

            {{-- Link to Usage & Models page --}}
            <div class="mt-8">
                <a href="{{ route('usage') }}"
                   class="block bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Usage &amp; Models</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Token consumption, cost estimates, and active model configuration.</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            </div>

        </div>
    </main>
</div>
