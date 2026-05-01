<div class="flex flex-col lg:flex-row w-full h-full">
    <x-nav-sidebar active="people" />

    <main class="flex-1 overflow-y-auto pt-4 lg:pt-8 px-4 lg:px-8 pb-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">People</h1>
                <div class="flex items-center gap-2">
                    <button wire:click="exportCsv"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Export CSV
                    </button>
                    <button wire:click="exportJson"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Export JSON
                    </button>
                    <button wire:click="toggleAddForm"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                        {{ $showAddForm ? 'Cancel' : '+ Add person' }}
                    </button>
                </div>
            </div>

            @if ($successMessage)
                <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">
                    {{ $successMessage }}
                </div>
            @endif

            @if ($showAddForm)
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 space-y-3">
                    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300">New person</h2>
                    <input wire:model="newName" type="text" placeholder="Display name (required)"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    @error('newName') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                    <input wire:model="newEmail" type="email" placeholder="Email (optional)"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    @error('newEmail') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                    <input wire:model="newCompany" type="text" placeholder="Company (optional)"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">

                    <div class="flex justify-end">
                        <button wire:click="createPerson" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            Save
                        </button>
                    </div>
                </div>
            @endif

            <div class="flex flex-col md:flex-row gap-3 mb-4">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search people, companies, emails…"
                    class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                    <input wire:model.live="stale" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Stale ({{ $stalenessDays }}+ days)
                </label>
            </div>

            @if (! empty($allTags))
                <div class="flex flex-wrap gap-2 mb-4">
                    @if ($tag !== '')
                        <button wire:click="clearTag"
                            class="px-2.5 py-1 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                            Clear tag
                        </button>
                    @endif
                    @foreach ($allTags as $t)
                        <button wire:click="setTag(@js($t))"
                            class="px-2.5 py-1 text-xs rounded-full transition-colors {{ $tag === $t ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                            {{ $t }}
                        </button>
                    @endforeach
                </div>
            @endif

            @if ($people->isEmpty())
                <div class="text-center py-16 text-sm text-gray-500 dark:text-gray-400">
                    @if ($search !== '' || $tag !== '' || $stale)
                        No people match those filters.
                    @else
                        <p>No people yet.</p>
                        <p class="mt-2">Add someone manually above, or forward an email to your inbound aiPal address to auto-create one.</p>
                    @endif
                </div>
            @else
                @php $selectedCount = count(array_filter($selected)); @endphp
                @if ($selectedCount > 0)
                    <div class="mb-3 flex flex-wrap items-center gap-2 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-2 text-sm">
                        <span class="text-indigo-700 dark:text-indigo-300">
                            {{ $selectedCount }} selected
                        </span>
                        <input wire:model="bulkTag" type="text" placeholder="tag to apply"
                            class="flex-1 min-w-[8rem] rounded-md border border-indigo-300 dark:border-indigo-700 bg-white dark:bg-gray-800 px-2 py-1 text-xs text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <button wire:click="applyBulkTag"
                            class="px-2.5 py-1 text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Apply tag
                        </button>
                        @if ($selectedCount === 2)
                            <button wire:click="startMerge"
                                class="px-2.5 py-1 text-xs font-medium rounded-md text-white bg-rose-600 hover:bg-rose-700">
                                Merge
                            </button>
                        @endif
                        <button wire:click="clearSelection"
                            class="px-2.5 py-1 text-xs font-medium rounded-md text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50">
                            Clear
                        </button>
                    </div>
                @endif

                @if ($mergePrimaryId > 0 && $selectedCount === 2)
                    <div class="mb-3 rounded-lg border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-900/20 p-3 text-sm">
                        <p class="text-rose-700 dark:text-rose-300 font-medium">Merge two people</p>
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">
                            The duplicate's emails, phones, interactions, and tags are moved into the primary. The duplicate is then soft-deleted.
                        </p>
                        <div class="mt-2 space-y-1.5">
                            @foreach ($selectedPeople as $sp)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:click="setMergePrimary({{ $sp->id }})"
                                        @checked($mergePrimaryId === $sp->id)
                                        class="text-rose-600 focus:ring-rose-500">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $sp->display_name }}</span>
                                    @if ($sp->company)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">· {{ $sp->company }}</span>
                                    @endif
                                    @if ($mergePrimaryId === $sp->id)
                                        <span class="text-xs text-rose-700 dark:text-rose-300">primary</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button wire:click="confirmMerge"
                                wire:confirm="Merge these two people? This soft-deletes the duplicate."
                                class="px-3 py-1 text-xs font-medium rounded-md text-white bg-rose-600 hover:bg-rose-700">
                                Confirm merge
                            </button>
                            <button wire:click="cancelMerge"
                                class="px-3 py-1 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">
                                Cancel
                            </button>
                        </div>
                    </div>
                @endif
                <div class="space-y-2">
                    @foreach ($people as $person)
                        @php $primaryEmail = $person->emails->first()?->email; @endphp
                        <div wire:key="person-{{ $person->id }}"
                            class="flex items-stretch gap-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-3 hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors">
                            <label class="flex items-center pl-1 pr-2 -my-3 cursor-pointer">
                                <input type="checkbox" wire:model.live="selected.{{ $person->id }}"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                            </label>
                            <a href="{{ route('people.show', $person->id) }}"
                                class="flex-1 -my-3 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $person->display_name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                        @if ($person->company)
                                            {{ $person->company }}@if ($person->title) · {{ $person->title }}@endif
                                        @elseif ($primaryEmail)
                                            {{ $primaryEmail }}
                                        @endif
                                    </p>
                                    @if (! empty($person->tags))
                                        <div class="flex flex-wrap gap-1 mt-1.5">
                                            @foreach ($person->tags as $t)
                                                <span class="px-1.5 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ $t }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0 text-right">
                                    @if ($person->last_contact_at)
                                        {{ $person->last_contact_at->diffForHumans() }}
                                    @else
                                        no contact
                                    @endif
                                </div>
                            </div>
                            </a>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $people->links() }}</div>
            @endif
        </div>
    </main>
</div>
