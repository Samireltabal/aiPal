<div class="flex flex-col lg:flex-row w-full h-full">
    <x-nav-sidebar active="settings" />

    <main class="flex-1 overflow-y-auto pt-4 lg:pt-8 px-4 lg:px-8 pb-8">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <a href="{{ route('settings') }}"
                   class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">← Settings</a>
            </div>

            <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Browser Extension</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Capture pages, selections, and ideas straight from your browser into aiPal.
                Generate a token below and paste it into the extension popup, or click the one-click connect link.
            </p>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                @if ($generatedToken)
                    <div class="space-y-4">
                        <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 text-sm text-amber-800 dark:text-amber-300">
                            Copy your token now — for security it will not be shown again.
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Token</label>
                            <div class="flex gap-2">
                                <input type="text" readonly value="{{ $generatedToken }}"
                                       class="flex-1 font-mono text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 px-3 py-2 text-gray-900 dark:text-white"
                                       onclick="this.select()">
                                <button type="button"
                                        onclick="navigator.clipboard.writeText('{{ $generatedToken }}')"
                                        class="px-3 py-2 text-xs font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">One-click connect</label>
                            <a href="{{ $connectLink }}"
                               class="inline-block px-3 py-2 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                Connect extension
                            </a>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Requires the aiPal extension to be installed; it registers the
                                <code>aipal-ext://</code> protocol handler.
                            </p>
                        </div>
                    </div>
                @else
                    <button type="button" wire:click="generate"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                        Generate extension token
                    </button>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Generating a new token revokes any previous extension token.
                    </p>
                @endif
            </div>

            @if ($tokens->isNotEmpty())
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-8 mb-3">Active extension tokens</h2>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($tokens as $token)
                        <div class="p-4 flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-900 dark:text-white">Token #{{ $token->id }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Created {{ $token->created_at?->diffForHumans() }} ·
                                    Last used {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'never' }}
                                </div>
                            </div>
                            <button type="button"
                                    wire:click="revoke({{ $token->id }})"
                                    wire:confirm="Revoke this token? The extension will stop working until reconnected."
                                    class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline">
                                Revoke
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </main>
</div>
