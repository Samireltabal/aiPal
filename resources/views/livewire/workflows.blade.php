<div class="flex flex-col lg:flex-row w-full h-full">
    <x-nav-sidebar active="workflows" />

    <main class="flex-1 overflow-y-auto pt-4 lg:pt-8 px-4 lg:px-8 pb-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Workflows</h1>
                @unless ($showForm)
                    <button wire:click="openCreate"
                        class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                        + New workflow
                    </button>
                @endunless
            </div>

            @if ($successMessage)
                <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">
                    {{ $successMessage }}
                </div>
            @endif

            @if ($errorMessage)
                <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-400">
                    {{ $errorMessage }}
                </div>
            @endif

            {{-- ===== FORM ===== --}}
            @if ($showForm)
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                        {{ $editingId ? 'Edit workflow' : 'New workflow' }}
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Name</label>
                            <input wire:model="name" type="text"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm">
                            @error('name') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description (optional)</label>
                            <input wire:model="description" type="text"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm">
                        </div>

                        {{-- Trigger type --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Trigger</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @foreach (['schedule' => 'Schedule', 'manual' => 'Manual', 'webhook' => 'Webhook', 'message' => 'Message'] as $value => $label)
                                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors
                                        {{ $triggerType === $value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400' }}">
                                        <input type="radio" wire:model.live="triggerType" value="{{ $value }}" class="sr-only">
                                        <span class="text-sm">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Trigger config --}}
                        @if ($triggerType === 'schedule')
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Cron expression</label>
                                <div class="flex gap-2 mb-2">
                                    <select wire:change="applyCronPreset($event.target.value)"
                                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-xs">
                                        <option value="">Presets…</option>
                                        <option value="every_minute">Every minute</option>
                                        <option value="every_hour">Every hour</option>
                                        <option value="every_day_8am">Every day at 8am</option>
                                        <option value="weekdays_8am">Weekdays at 8am</option>
                                        <option value="every_monday_9am">Every Monday at 9am</option>
                                        <option value="every_friday_5pm">Every Friday at 5pm</option>
                                    </select>
                                </div>
                                <input wire:model="cronExpression" type="text" placeholder="e.g. 0 8 * * 1-5"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm font-mono">
                                <p class="text-xs text-gray-500 mt-1">Format: minute hour day-of-month month day-of-week</p>
                            </div>
                        @endif

                        @if ($triggerType === 'webhook')
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Webhook URL</label>
                                @if ($webhookToken)
                                    <div class="flex gap-2 items-center">
                                        <code class="flex-1 text-xs bg-white dark:bg-gray-800 px-2 py-1.5 rounded border border-gray-300 dark:border-gray-600 font-mono truncate select-all">{{ url('/webhooks/workflow/'.$webhookToken) }}</code>
                                        <button type="button" wire:click="regenerateWebhookToken"
                                            class="px-2 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            Regenerate
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Paste this URL into the external service (Jira, GitHub, Zapier, etc.). POST to it to trigger. The request body will be appended to your prompt so you can reference it.</p>
                                @else
                                    <p class="text-xs text-gray-500">URL will be generated when you save.</p>
                                @endif
                            </div>
                        @endif

                        @if ($triggerType === 'message')
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Channel</label>
                                    <select wire:model="messageChannel"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm">
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="telegram">Telegram</option>
                                        <option value="any">Any channel</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pattern</label>
                                    <input wire:model="messagePattern" type="text" placeholder="/morning or /^status/i"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm font-mono">
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">Prefix match by default ("/morning"). Wrap in slashes for regex ("/^meeting with/i").</p>
                        @endif

                        @if ($triggerType === 'manual')
                            <p class="text-xs text-gray-500">Runs only when you click "Run now" or when the AI invokes it by name.</p>
                        @endif

                        {{-- Prompt --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Prompt</label>
                            <textarea wire:model="prompt" rows="4" placeholder="e.g. Fetch my Jira tickets due this week, summarize them, and highlight any blockers."
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm resize-y"></textarea>
                            @error('prompt') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Tools --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Tools the workflow can use</label>
                            <div class="space-y-3 max-h-64 overflow-y-auto p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                @foreach ($toolsByCategory as $category => $categoryTools)
                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ $category }}</p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                                            @foreach ($categoryTools as $tool)
                                                <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 px-1 py-1 rounded">
                                                    <input type="checkbox" wire:model="selectedTools" value="{{ $tool['name'] }}"
                                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                    <span>{{ $tool['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Delivery --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Delivery channel</label>
                            <select wire:model="deliveryChannel"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm">
                                <option value="notification">Browser notification</option>
                                <option value="email">Email</option>
                                <option value="telegram">Telegram</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="none">None (don't send, just log)</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="enabled" id="enabled-toggle"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="enabled-toggle" class="text-sm">Enabled</label>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button wire:click="save" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                                {{ $editingId ? 'Save changes' : 'Create workflow' }}
                            </button>
                            <button wire:click="cancelForm" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ===== LIST ===== --}}
            @if (! $showForm)
                <div class="space-y-3">
                    @forelse ($workflows as $workflow)
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $workflow->name }}</h3>
                                        @if (! $workflow->enabled)
                                            <span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400">Disabled</span>
                                        @endif
                                    </div>
                                    @if ($workflow->description)
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ $workflow->description }}</p>
                                    @endif
                                    <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                                        <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700">
                                            @switch($workflow->trigger_type)
                                                @case('schedule') 🕐 {{ $workflow->cron_expression }} @break
                                                @case('webhook') 🔗 webhook @break
                                                @case('message') 💬 {{ $workflow->message_channel }}: {{ $workflow->message_trigger_pattern }} @break
                                                @case('manual') 👆 manual @break
                                            @endswitch
                                        </span>
                                        @if ($workflow->last_run_at)
                                            <span>last run {{ $workflow->last_run_at->diffForHumans() }}</span>
                                        @endif
                                        <span>→ {{ $workflow->delivery_channel }}</span>
                                    </div>
                                </div>
                                <div class="flex gap-1 flex-shrink-0">
                                    <button wire:click="runNow({{ $workflow->id }})"
                                        class="p-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700" title="Run now">
                                        ▶
                                    </button>
                                    <button wire:click="viewRuns({{ $workflow->id }})"
                                        class="p-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700" title="View runs">
                                        📋
                                    </button>
                                    <button wire:click="toggleEnabled({{ $workflow->id }})"
                                        class="p-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700" title="Toggle enabled">
                                        {{ $workflow->enabled ? '⏸' : '▶︎' }}
                                    </button>
                                    <button wire:click="openEdit({{ $workflow->id }})"
                                        class="p-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700" title="Edit">
                                        ✎
                                    </button>
                                    <button wire:click="delete({{ $workflow->id }})" wire:confirm="Delete this workflow?"
                                        class="p-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-600" title="Delete">
                                        🗑
                                    </button>
                                </div>
                            </div>

                            @if ($viewingRunsForId === $workflow->id)
                                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300">Recent runs</h4>
                                        <button wire:click="closeRuns" class="text-xs text-gray-500 hover:text-gray-700">Close</button>
                                    </div>
                                    @forelse ($runs as $run)
                                        <details class="mb-2 rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                            <summary class="flex items-center gap-2 p-2 cursor-pointer text-xs">
                                                <span class="inline-block w-2 h-2 rounded-full
                                                    @if($run->status === 'success') bg-green-500
                                                    @elseif($run->status === 'failed') bg-red-500
                                                    @else bg-yellow-500
                                                    @endif"></span>
                                                <span class="flex-1 truncate">{{ $run->status }} · {{ $run->triggered_by }} · {{ $run->started_at?->diffForHumans() }} · {{ $run->duration_ms }}ms</span>
                                            </summary>
                                            <div class="px-3 pb-3 text-xs">
                                                @if ($run->error)
                                                    <p class="text-red-600 dark:text-red-400 mb-2"><strong>Error:</strong> {{ $run->error }}</p>
                                                @endif
                                                @if ($run->output)
                                                    <div class="whitespace-pre-wrap break-words bg-white dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-700 max-h-80 overflow-y-auto">{{ $run->output }}</div>
                                                @endif
                                            </div>
                                        </details>
                                    @empty
                                        <p class="text-xs text-gray-500">No runs yet.</p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-16 text-gray-500 dark:text-gray-400">
                            <p class="text-sm mb-2">No workflows yet.</p>
                            <p class="text-xs">Create a scheduled prompt that the AI runs for you automatically.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </main>
</div>
