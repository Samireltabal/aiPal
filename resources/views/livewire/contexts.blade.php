<div class="flex flex-col lg:flex-row w-full h-full">
    <x-nav-sidebar active="contexts" />

    <main class="flex-1 overflow-y-auto pt-4 lg:pt-8 px-4 lg:px-8 pb-8">
        <div class="max-w-4xl mx-auto">

            <div class="mb-6">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Contexts</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Organize your work, freelance, and personal life into separate contexts. Each has its own connections (email, calendar, chat) and forward-to address.
                </p>
            </div>

            @if ($saved)
            <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400"
                 x-data x-init="setTimeout(() => $wire.set('saved', false), 2500)">
                Context created.
            </div>
            @endif

            @if ($errorMessage)
            <div class="mb-4 p-3 rounded-lg bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-sm text-rose-700 dark:text-rose-400">
                {{ $errorMessage }}
                <button wire:click="$set('errorMessage', null)" class="ml-2 text-xs underline">dismiss</button>
            </div>
            @endif

            {{-- New context form --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Add a context</h2>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kind</label>
                        <select wire:model="newKind"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm">
                            <option value="work">Work</option>
                            <option value="freelance">Freelance</option>
                            <option value="personal">Personal</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Name</label>
                        <input wire:model="newName" type="text" placeholder="Acme Corp / Project Phoenix"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm">
                        @error('newName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Color</label>
                        <input wire:model="newColor" type="color"
                            class="block w-full h-10 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                    </div>
                </div>
                <div class="mt-3 flex justify-end">
                    <button wire:click="createContext"
                        class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">
                        Add context
                    </button>
                </div>
            </div>

            {{-- Contexts list --}}
            <div class="space-y-3">
                @forelse ($contexts as $ctx)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 {{ $ctx->archived_at ? 'opacity-60' : '' }}">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="flex-shrink-0 w-4 h-4 rounded-full" style="background-color: {{ $ctx->color }}"></span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $ctx->name }}</p>
                                    <span class="px-1.5 py-0.5 text-[10px] uppercase tracking-wide rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ $ctx->kind }}</span>
                                    @if ($ctx->is_default)
                                        <span class="px-1.5 py-0.5 text-[10px] uppercase tracking-wide rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">default</span>
                                    @endif
                                    @if ($ctx->archived_at)
                                        <span class="px-1.5 py-0.5 text-[10px] uppercase tracking-wide rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">archived</span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 font-mono">slug: {{ $ctx->slug }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            @if (! $ctx->is_default && ! $ctx->archived_at)
                                <button wire:click="setDefault({{ $ctx->id }})" class="px-2 py-1 text-[11px] rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Set default</button>
                            @endif
                            @if ($ctx->archived_at)
                                <button wire:click="unarchive({{ $ctx->id }})" class="px-2 py-1 text-[11px] rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Unarchive</button>
                            @else
                                <button wire:click="archive({{ $ctx->id }})" class="px-2 py-1 text-[11px] rounded border border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30">Archive</button>
                            @endif
                            <button wire:click="deleteContext({{ $ctx->id }})" wire:confirm="Delete this context? Its connections will move to your default context."
                                class="px-2 py-1 text-[11px] rounded border border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30">Delete</button>
                        </div>
                    </div>

                    @php($ctxConnections = $connections->where('context_id', $ctx->id))
                    @if ($ctxConnections->isEmpty())
                        <p class="text-xs text-gray-500 dark:text-gray-400">No connections yet.</p>
                    @else
                        <div class="space-y-1.5">
                            @foreach ($ctxConnections as $conn)
                                <div class="flex items-center justify-between text-xs py-1">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="font-mono text-gray-700 dark:text-gray-300">{{ $conn->provider }}</span>
                                        <span class="text-gray-500 dark:text-gray-400 truncate">{{ $conn->label ?: $conn->identifier }}</span>
                                    </div>
                                    <select wire:change="moveConnection({{ $conn->id }}, $event.target.value)"
                                        class="text-[11px] rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-1.5 py-0.5">
                                        @foreach ($contexts as $c)
                                            <option value="{{ $c->id }}" @selected($c->id === $conn->context_id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Inference rules: auto-route inbound emails by sender domain --}}
                    @php($rules = is_array($ctx->inference_rules) ? $ctx->inference_rules : [])
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">Inbound email routing</p>
                        @if (count($rules) > 0)
                            <div class="space-y-1 mb-2">
                                @foreach ($rules as $idx => $rule)
                                    @if (($rule['type'] ?? null) === 'sender_domain')
                                        <div class="flex items-center justify-between text-xs py-1" wire:key="rule-{{ $ctx->id }}-{{ $idx }}">
                                            <span class="font-mono text-gray-700 dark:text-gray-300">@{{ $rule['value'] }}</span>
                                            <button wire:click="removeInferenceRule({{ $ctx->id }}, {{ $idx }})"
                                                class="text-[11px] px-1.5 py-0.5 rounded text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30">Remove</button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">No rules. Add a sender domain to auto-route inbound emails to this context.</p>
                        @endif

                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                wire:model="newDomainsByContext.{{ $ctx->id }}"
                                wire:keydown.enter.prevent="addInferenceRule({{ $ctx->id }})"
                                placeholder="example.com"
                                class="flex-1 text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                            <button wire:click="addInferenceRule({{ $ctx->id }})"
                                class="text-[11px] px-2 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Add rule</button>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No contexts yet. Add one above.</p>
                @endforelse
            </div>

        </div>
    </main>
</div>
