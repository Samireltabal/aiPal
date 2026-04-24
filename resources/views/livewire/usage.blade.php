<div class="flex flex-col lg:flex-row w-full h-full">
    {{-- Sidebar --}}
    <x-nav-sidebar active="usage" />

    {{-- Main content --}}
    <main class="flex-1 overflow-y-auto pt-4 lg:pt-8 px-4 lg:px-8 pb-8">
        <div class="max-w-5xl mx-auto">

            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Usage &amp; Models</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Token consumption, cost estimates, and active model configuration.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($isAdmin)
                    <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button type="button" wire:click="setScope('me')"
                            class="px-3 py-1.5 text-xs {{ $effectiveScope === 'me' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            My usage
                        </button>
                        <button type="button" wire:click="setScope('global')"
                            class="px-3 py-1.5 text-xs border-l border-gray-200 dark:border-gray-700 {{ $effectiveScope === 'global' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            All users
                        </button>
                    </div>
                    @endif

                    <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        @foreach ($ranges as $r)
                        <button type="button" wire:click="setRange({{ $r }})"
                            class="px-3 py-1.5 text-xs {{ $r !== $ranges[0] ? 'border-l border-gray-200 dark:border-gray-700' : '' }} {{ $days === $r ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            {{ $r }}d
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Totals cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                @php
                    $t = $summary['totals'];
                    $cards = [
                        ['label' => 'Total tokens',     'value' => number_format($t['total_tokens']), 'sub' => number_format($t['messages']).' messages'],
                        ['label' => 'Prompt',           'value' => number_format($t['prompt_tokens']), 'sub' => 'input tokens'],
                        ['label' => 'Completion',       'value' => number_format($t['completion_tokens']), 'sub' => 'output tokens'],
                        ['label' => 'Est. cost',        'value' => '$'.number_format($summary['cost_estimate_usd'], 4), 'sub' => 'list price estimate'],
                    ];
                @endphp
                @foreach ($cards as $c)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $c['label'] }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">{{ $c['value'] }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $c['sub'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- Secondary metrics --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                @php
                    $sec = [
                        ['label' => 'Conversations',    'value' => number_format($t['conversations'])],
                        ['label' => 'Reasoning tokens', 'value' => number_format($t['reasoning_tokens'])],
                        ['label' => 'Cache reads',      'value' => number_format($t['cache_read_input_tokens'])],
                        ['label' => 'Cache hit rate',   'value' => $t['cache_hit_rate'].'%'],
                    ];
                    if ($effectiveScope === 'global') {
                        $sec[] = ['label' => 'Active users', 'value' => number_format($t['users'])];
                    }
                @endphp
                @foreach ($sec as $s)
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $s['label'] }}</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white mt-0.5">{{ $s['value'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- Daily chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Daily token usage — last {{ $days }} days</h2>
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-indigo-500"></span>Prompt</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-emerald-500"></span>Completion</span>
                    </div>
                </div>

                @php
                    $maxDaily = max(1, max(array_map(fn($d) => $d['prompt'] + $d['completion'], $summary['daily'])));
                @endphp

                <div class="flex items-end gap-px h-40 w-full">
                    @foreach ($summary['daily'] as $d)
                        @php
                            $promptH = ($d['prompt'] / $maxDaily) * 100;
                            $compH = ($d['completion'] / $maxDaily) * 100;
                            $tot = $d['prompt'] + $d['completion'];
                        @endphp
                        <div class="flex-1 flex flex-col justify-end h-full group relative" title="{{ $d['date'] }}: {{ number_format($tot) }} tokens">
                            <div class="bg-emerald-500 hover:bg-emerald-400" style="height: {{ $compH }}%"></div>
                            <div class="bg-indigo-500 hover:bg-indigo-400" style="height: {{ $promptH }}%"></div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-between mt-2 text-[10px] text-gray-400 dark:text-gray-500">
                    <span>{{ $summary['daily'][0]['date'] ?? '' }}</span>
                    <span>{{ end($summary['daily'])['date'] ?? '' }}</span>
                </div>
            </div>

            {{-- By function + By model --}}
            <div class="grid lg:grid-cols-2 gap-4 mb-6">

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">By function</h2>

                    @forelse ($summary['by_function'] as $fn)
                        <div class="mb-3 last:mb-0">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $fn['function'] }}</span>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ number_format($fn['total_tokens']) }} · {{ $fn['percent'] }}%
                                </span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full bg-indigo-500" style="width: {{ $fn['percent'] }}%"></div>
                            </div>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                                {{ number_format($fn['messages']) }} messages
                            </p>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500 dark:text-gray-400">No usage in this range yet.</p>
                    @endforelse
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">By model</h2>

                    @if (count($summary['by_model']) === 0)
                        <p class="text-xs text-gray-500 dark:text-gray-400">No usage in this range yet.</p>
                    @else
                    <div class="overflow-x-auto -mx-2">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-2 py-2 font-medium">Model</th>
                                    <th class="px-2 py-2 font-medium text-right">Tokens</th>
                                    <th class="px-2 py-2 font-medium text-right">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($summary['by_model'] as $m)
                                    <tr>
                                        <td class="px-2 py-2 font-mono text-gray-700 dark:text-gray-300 truncate max-w-[180px]">{{ $m['model'] }}</td>
                                        <td class="px-2 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($m['total_tokens']) }}</td>
                                        <td class="px-2 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($m['cost_usd'], 4) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

            </div>

            {{-- Top conversations + Tools --}}
            <div class="grid lg:grid-cols-2 gap-4 mb-6">

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Top conversations</h2>

                    @forelse ($summary['top_conversations'] as $c)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <div class="min-w-0 flex-1 mr-3">
                                <a href="{{ route('chat') }}?conversation={{ $c['conversation_id'] }}"
                                   class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline truncate block">
                                    {{ $c['title'] ?: 'Untitled' }}
                                </a>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500">{{ $c['messages'] }} messages</p>
                            </div>
                            <span class="text-xs font-mono text-gray-600 dark:text-gray-400 flex-shrink-0">
                                {{ number_format($c['total_tokens']) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500 dark:text-gray-400">No conversations in this range yet.</p>
                    @endforelse
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Tool calls</h2>

                    @if (count($summary['tools']) === 0)
                        <p class="text-xs text-gray-500 dark:text-gray-400">No tool executions in this range.</p>
                    @else
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-2 py-2 font-medium">Tool</th>
                                <th class="px-2 py-2 font-medium text-right">Calls</th>
                                <th class="px-2 py-2 font-medium text-right">Avg ms</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($summary['tools'] as $tool)
                                <tr>
                                    <td class="px-2 py-2 font-mono text-gray-700 dark:text-gray-300">{{ $tool['tool'] }}</td>
                                    <td class="px-2 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($tool['count']) }}</td>
                                    <td class="px-2 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($tool['avg_ms']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>

            </div>

            {{-- Server Health (admin only) --}}
            @if ($isAdmin && $serverMetrics !== null)
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Server health</h2>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Host, database, queue, and disk status for this deployment.
                </p>

                @php
                    $rt = $serverMetrics['runtime'];
                    $sys = $serverMetrics['system'];
                    $disk = $serverMetrics['disk'];
                    $db = $serverMetrics['database'];
                    $queue = $serverMetrics['queue'];
                    $cache = $serverMetrics['cache'];
                    $load = $sys['load_average'];
                    $uptimeDays = $sys['uptime_seconds'] !== null
                        ? round($sys['uptime_seconds'] / 86400, 1)
                        : null;
                @endphp

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Runtime</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">PHP {{ $rt['php_version'] }}</p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Laravel {{ $rt['laravel_version'] }} · {{ $rt['environment'] }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Memory</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $rt['memory_usage_mb'] }} MB</p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">peak {{ $rt['peak_memory_mb'] }} MB · limit {{ $rt['memory_limit'] }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Load avg</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                            @if ($load) {{ $load['1m'] }} · {{ $load['5m'] }} · {{ $load['15m'] }} @else n/a @endif
                        </p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                            @if ($uptimeDays !== null) uptime {{ $uptimeDays }}d @else {{ $sys['os'] }} @endif
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Disk</p>
                        @if ($disk['total_gb'] !== null)
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                {{ $disk['free_gb'] }} / {{ $disk['total_gb'] }} GB free
                            </p>
                            <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 mt-2 overflow-hidden">
                                <div class="h-full {{ $disk['used_percent'] > 85 ? 'bg-rose-500' : ($disk['used_percent'] > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $disk['used_percent'] }}%"></div>
                            </div>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ $disk['used_percent'] }}% used</p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">n/a</p>
                        @endif
                    </div>
                </div>

                <div class="grid lg:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Database · {{ $db['driver'] }}</h3>
                        @if (count($db['row_counts']) === 0)
                            <p class="text-xs text-gray-500 dark:text-gray-400">No tables found.</p>
                        @else
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($db['row_counts'] as $table => $count)
                                <div class="flex justify-between text-xs py-1">
                                    <span class="font-mono text-gray-600 dark:text-gray-400 truncate">{{ $table }}</span>
                                    <span class="text-gray-900 dark:text-white font-semibold">{{ number_format($count) }}</span>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Queue &amp; cache</h3>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Queue driver</span>
                                <span class="font-mono text-gray-700 dark:text-gray-300">{{ $queue['driver'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Pending jobs</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $queue['pending'] !== null ? number_format($queue['pending']) : 'n/a' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Failed jobs</span>
                                <span class="font-semibold {{ ($queue['failed'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $queue['failed'] !== null ? number_format($queue['failed']) : 'n/a' }}
                                </span>
                            </div>
                            <div class="flex justify-between pt-1 border-t border-gray-100 dark:border-gray-700 mt-2">
                                <span class="text-gray-500 dark:text-gray-400">Cache driver</span>
                                <span class="font-mono text-gray-700 dark:text-gray-300">{{ $cache['driver'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Session driver</span>
                                <span class="font-mono text-gray-700 dark:text-gray-300">{{ $cache['session_driver'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- AI Model Configuration --}}
            <div class="mt-8">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Active AI model configuration</h2>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Configure providers and models via your
                    <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-700 font-mono text-xs">.env</code>
                    file, then rebuild the container. Values shown reflect the current active configuration.
                </p>

                <div class="space-y-3">
                    @foreach ($aiConfig as $fn)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $fn['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $fn['description'] }}</p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700">
                                    {{ $fn['provider'] }}
                                </span>
                                @if ($fn['model'] !== '—')
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 font-mono">{{ $fn['model'] }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-x-6 gap-y-2">
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Env variables</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($fn['env_vars'] as $var)
                                    <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs font-mono text-gray-700 dark:text-gray-300">{{ $var }}</code>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Compatible providers</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($fn['compatible'] as $provider)
                                    <span class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ $provider }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @if (!empty($fn['note']))
                        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            {{ $fn['note'] }}
                        </p>
                        @endif
                    </div>
                    @endforeach
                </div>

                <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                    Cost figures are <strong>estimates</strong> based on public list prices and may differ from actual provider billing.
                </p>
            </div>

        </div>
    </main>
</div>
