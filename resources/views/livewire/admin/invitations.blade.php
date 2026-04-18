<div class="flex-1 p-8 overflow-y-auto">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Invitations</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage access to aiPal</p>
            </div>
            <a href="{{ route('chat') }}"
                class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 font-medium">
                ← Back to chat
            </a>
        </div>

        {{-- Create invitation --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Create invitation</h2>
            <form wire:submit="createInvitation" class="flex gap-3 items-end">
                <div class="flex-1">
                    <label for="email" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Email (optional — leave blank for any)</label>
                    <input wire:model="email" type="email" id="email" placeholder="user@example.com"
                        class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    @error('email') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove>Generate link</span>
                    <span wire:loading>Generating…</span>
                </button>
            </form>

            @if($newInviteUrl)
            <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <p class="text-xs font-medium text-green-700 dark:text-green-400 mb-1">Invitation link (share with user):</p>
                <div class="flex gap-2 items-center">
                    <code class="flex-1 text-xs text-green-800 dark:text-green-300 break-all">{{ $newInviteUrl }}</code>
                    <button
                        onclick="navigator.clipboard.writeText('{{ $newInviteUrl }}').then(() => this.textContent = 'Copied!')"
                        class="flex-shrink-0 text-xs text-green-700 dark:text-green-400 hover:underline font-medium">
                        Copy
                    </button>
                </div>
            </div>
            @endif
        </div>

        {{-- Invitations list --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400">Email</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400">Created by</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400">Expires</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($invitations as $inv)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            {{ $inv->email ?? '<any>' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $inv->creator->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $inv->expires_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3">
                            @if($inv->accepted_at)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">Accepted</span>
                            @elseif($inv->expires_at->isPast())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500">Expired</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(! $inv->accepted_at && $inv->expires_at->isFuture())
                            <button wire:click="revokeInvitation({{ $inv->id }})"
                                wire:confirm="Revoke this invitation?"
                                class="text-xs text-red-500 hover:text-red-700 font-medium">
                                Revoke
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No invitations yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
