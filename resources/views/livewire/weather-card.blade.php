<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5"
    wire:poll.visible.900s>

    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Weather</h3>
        @if ($hasLocation)
            <div class="flex items-center gap-2">
                @if ($updatedAt)
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $updatedAt->diffForHumans() }}</span>
                @endif
                <button type="button"
                    data-action="detect-location"
                    title="Refresh location"
                    class="p-1 rounded text-gray-400 hover:text-indigo-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>
        @endif
    </div>

    @if ($hasLocation && $current)
        <div class="flex items-center gap-4 mb-4">
            <div class="text-5xl">{{ $icon }}</div>
            <div class="flex-1 min-w-0">
                <p class="text-3xl font-light text-gray-900 dark:text-white leading-none">
                    {{ round($current['temperature']) }}°C
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 truncate">{{ $description }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 truncate">{{ $locationName }}</p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3 mb-4 pb-4 border-b border-gray-100 dark:border-gray-700">
            @if ($current['feels_like'] !== null)
                <div>
                    <p class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Feels like</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ round($current['feels_like']) }}°</p>
                </div>
            @endif
            @if ($current['humidity'] !== null)
                <div>
                    <p class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Humidity</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $current['humidity'] }}%</p>
                </div>
            @endif
            @if ($current['wind'] !== null)
                <div>
                    <p class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Wind</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ round($current['wind']) }} km/h</p>
                </div>
            @endif
        </div>

        @if ($today)
            <div class="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400 mb-4">
                <span>High <span class="font-medium text-gray-900 dark:text-white">{{ round($today['high']) }}°</span></span>
                <span>Low <span class="font-medium text-gray-900 dark:text-white">{{ round($today['low']) }}°</span></span>
                @if ($today['precipitation'] > 0)
                    <span>💧 {{ $today['precipitation'] }}%</span>
                @endif
            </div>
        @endif

        @if (count($hourly) > 0)
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-1 mb-3">
                @foreach ($hourly as $hour)
                    <div class="flex-shrink-0 flex flex-col items-center gap-1 w-12 text-center">
                        <span class="text-[10px] text-gray-500 dark:text-gray-400">{{ $hour['time'] }}</span>
                        <span class="text-base">{{ $hour['icon'] }}</span>
                        <span class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ round($hour['temp']) }}°</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if (count($daily) > 0)
            <div class="pt-3 border-t border-gray-100 dark:border-gray-700 space-y-1.5">
                @foreach ($daily as $day)
                    <div class="flex items-center gap-3 text-xs">
                        <span class="w-16 text-gray-600 dark:text-gray-400">{{ $day['label'] }}</span>
                        <span class="w-5 text-center">{{ $day['icon'] }}</span>
                        <span class="flex-1 text-gray-500 dark:text-gray-500">{{ round($day['low']) }}° — {{ round($day['high']) }}°</span>
                    </div>
                @endforeach
            </div>
        @endif

    @elseif ($hasLocation)
        <div class="text-center py-6 text-sm text-gray-500 dark:text-gray-400">
            Weather service unavailable right now.
        </div>

    @else
        {{-- No saved location — inline setup UX --}}
        <div class="text-center py-4">
            <div class="text-4xl mb-2">📍</div>
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">Set your location to see weather</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">aiPal can also use this for time zone, daily briefings, and location-aware answers</p>

            {{-- Browser geolocation button (handled by location.js via the data-action attribute) --}}
            <button type="button"
                data-action="detect-location"
                class="w-full mb-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                📍 Detect my location
            </button>

            <div class="flex items-center gap-2 my-3">
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                <span class="text-[10px] uppercase text-gray-400 dark:text-gray-500">or</span>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
            </div>

            <form wire:submit.prevent="saveCity" class="flex gap-2">
                <input wire:model="manualCity" type="text" placeholder="Enter city (e.g. Riyadh)"
                    class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm">
                <button type="submit" wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300">
                    Save
                </button>
            </form>

            @if ($errorMessage)
                <p class="text-xs text-red-600 dark:text-red-400 mt-2">{{ $errorMessage }}</p>
            @endif
        </div>
    @endif
</div>
