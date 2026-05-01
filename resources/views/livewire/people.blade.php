<div class="flex flex-col lg:flex-row w-full h-full">
    <x-nav-sidebar active="people" />

    <main class="flex-1 overflow-y-auto pt-4 lg:pt-8 px-4 lg:px-8 pb-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">People</h1>
                <button wire:click="toggleAddForm"
                    class="px-3 py-1.5 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                    {{ $showAddForm ? 'Cancel' : '+ Add person' }}
                </button>
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
                <div class="space-y-2">
                    @foreach ($people as $person)
                        @php $primaryEmail = $person->emails->first()?->email; @endphp
                        <a href="{{ route('people.show', $person->id) }}" wire:key="person-{{ $person->id }}"
                            class="block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3 hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors">
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
                    @endforeach
                </div>
                <div class="mt-4">{{ $people->links() }}</div>
            @endif
        </div>
    </main>
</div>
