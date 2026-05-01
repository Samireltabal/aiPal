<div class="flex flex-col lg:flex-row w-full h-full">
    <x-nav-sidebar active="people" />

    <main class="flex-1 overflow-y-auto pt-4 lg:pt-8 px-4 lg:px-8 pb-8">
        <div class="max-w-3xl mx-auto">
            <a href="{{ route('people') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-4">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                All people
            </a>

            @if ($successMessage)
                <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-400">
                    {{ $successMessage }}
                </div>
            @endif

            {{-- Header --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-4">
                <div class="flex items-start gap-4">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="" class="w-16 h-16 rounded-full object-cover flex-shrink-0">
                    @else
                        <div class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-300 text-xl font-semibold flex-shrink-0">
                            {{ mb_strtoupper(mb_substr($person->display_name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $person->display_name }}</h1>
                        @if ($person->company || $person->title)
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $person->title }}@if ($person->title && $person->company) · @endif{{ $person->company }}
                            </p>
                        @endif
                        @if ($person->last_contact_at)
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                Last contact {{ $person->last_contact_at->diffForHumans() }}
                            </p>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2 flex-shrink-0">
                        <a href="{{ route('productivity').'?reminder_for_person='.$person->id }}"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors whitespace-nowrap">
                            Schedule follow-up
                        </a>
                        <button wire:click="deletePerson" wire:confirm="Delete this person? Interactions will be kept."
                            class="px-3 py-1.5 text-xs font-medium rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            {{-- Profile fields --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-4">
                <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Profile</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="block">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Display name</span>
                        <input wire:model="displayName" type="text"
                            class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </label>
                    <label class="block">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Company</span>
                        <input wire:model="company" type="text"
                            class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </label>
                    <label class="block">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Title</span>
                        <input wire:model="title" type="text"
                            class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </label>
                    <label class="block">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Birthday</span>
                        <input wire:model="birthday" type="date"
                            class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Tags (comma-separated)</span>
                        <input wire:model="tagsInput" type="text" placeholder="friend, work, customer"
                            class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Notes</span>
                        <textarea wire:model="notes" rows="3"
                            class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
                    </label>
                </div>
                <div class="flex justify-end mt-3">
                    <button wire:click="saveProfile"
                        class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                        Save profile
                    </button>
                </div>
            </div>

            {{-- Emails + phones --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Emails</h2>
                    <div class="space-y-2 mb-3">
                        @forelse ($person->emails as $email)
                            <div class="flex items-center justify-between gap-2 text-sm" wire:key="email-{{ $email->id }}">
                                <span class="truncate {{ $email->is_primary ? 'font-medium text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ $email->email }}
                                    @if ($email->is_primary) <span class="text-xs text-indigo-600 dark:text-indigo-400">(primary)</span> @endif
                                </span>
                                <div class="flex gap-1 flex-shrink-0">
                                    @if (! $email->is_primary)
                                        <button wire:click="makeEmailPrimary({{ $email->id }})" class="text-xs text-gray-500 hover:text-indigo-600">★</button>
                                    @endif
                                    <button wire:click="deleteEmail({{ $email->id }})" wire:confirm="Remove this email?" class="text-xs text-gray-400 hover:text-red-500">×</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 dark:text-gray-500">No emails on file.</p>
                        @endforelse
                    </div>
                    <div class="flex gap-2">
                        <input wire:model="newEmail" wire:keydown.enter="addEmail" type="email" placeholder="Add email…"
                            class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <button wire:click="addEmail" class="px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">Add</button>
                    </div>
                    @error('newEmail') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Phones</h2>
                    <div class="space-y-2 mb-3">
                        @forelse ($person->phones as $phone)
                            <div class="flex items-center justify-between gap-2 text-sm" wire:key="phone-{{ $phone->id }}">
                                <span class="truncate {{ $phone->is_primary ? 'font-medium text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ $phone->phone }}
                                    @if ($phone->is_primary) <span class="text-xs text-indigo-600 dark:text-indigo-400">(primary)</span> @endif
                                </span>
                                <div class="flex gap-1 flex-shrink-0">
                                    @if (! $phone->is_primary)
                                        <button wire:click="makePhonePrimary({{ $phone->id }})" class="text-xs text-gray-500 hover:text-indigo-600">★</button>
                                    @endif
                                    <button wire:click="deletePhone({{ $phone->id }})" wire:confirm="Remove this phone?" class="text-xs text-gray-400 hover:text-red-500">×</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 dark:text-gray-500">No phones on file.</p>
                        @endforelse
                    </div>
                    <div class="flex gap-2">
                        <input wire:model="newPhone" wire:keydown.enter="addPhone" type="text" placeholder="Add phone…"
                            class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <button wire:click="addPhone" class="px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">Add</button>
                    </div>
                </div>
            </div>

            {{-- Log interaction --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-4">
                <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Log interaction</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <select wire:model="logChannel" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        @foreach ($channels as $c)
                            <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                    <select wire:model="logDirection" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        @foreach ($directions as $d)
                            <option value="{{ $d }}">{{ ucfirst($d) }}</option>
                        @endforeach
                    </select>
                    <input wire:model="logOccurredAt" type="datetime-local"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                </div>
                <input wire:model="logSubject" type="text" placeholder="Subject (optional)"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white mb-3">
                <textarea wire:model="logSummary" rows="2" placeholder="Summary (optional)"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white resize-none"></textarea>
                <div class="flex justify-end mt-3">
                    <button wire:click="logInteraction"
                        class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                        Log
                    </button>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Timeline</h2>
                @if ($interactions->isEmpty())
                    <p class="text-xs text-gray-400 dark:text-gray-500 py-4 text-center">No interactions yet.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($interactions as $i)
                            <li wire:key="int-{{ $i->id }}" class="border-l-2 border-gray-200 dark:border-gray-700 pl-3">
                                <button wire:click="toggleInteraction({{ $i->id }})" class="text-left w-full">
                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 uppercase text-[10px] tracking-wide">{{ $i->channel }}</span>
                                        @if ($i->direction === 'inbound')
                                            <span class="text-green-600 dark:text-green-400">↓ in</span>
                                        @elseif ($i->direction === 'outbound')
                                            <span class="text-blue-600 dark:text-blue-400">↑ out</span>
                                        @endif
                                        <span>{{ $i->occurred_at?->diffForHumans() }}</span>
                                    </div>
                                    @if ($i->subject)
                                        <p class="text-sm text-gray-900 dark:text-white mt-0.5">{{ $i->subject }}</p>
                                    @endif
                                    @if ($i->summary)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ $i->summary }}</p>
                                    @elseif ($i->raw_excerpt)
                                        <p class="text-xs text-gray-400 dark:text-gray-500 italic mt-0.5">summary pending…</p>
                                    @endif
                                </button>
                                @if ($expandedInteractionId === $i->id && $i->raw_excerpt)
                                    <pre class="mt-2 p-3 bg-gray-50 dark:bg-gray-900 rounded text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $i->raw_excerpt }}</pre>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </main>
</div>
