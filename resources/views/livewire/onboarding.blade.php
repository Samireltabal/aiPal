<div>
    {{-- Header --}}
    <div class="mb-8 text-center">
        <div class="text-4xl mb-3">✨</div>
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Set up your assistant</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Step {{ $step }} of {{ $totalSteps }}</p>
    </div>

    {{-- Progress bar --}}
    <div class="mb-8 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
        <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300"
             style="width: {{ ($step / $totalSteps) * 100 }}%"></div>
    </div>

    @if ($generating)
        <div class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-indigo-600 border-t-transparent mb-4"></div>
            <p class="text-gray-600 dark:text-gray-400">Building your assistant's personality…</p>
        </div>
    @else

    {{-- Step 1: Name --}}
    @if ($step === 1)
    <div class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                What should your assistant be called?
            </label>
            <input wire:model="assistantName" type="text" placeholder="e.g. Pal, Max, Nova…"
                class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            @error('assistantName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end pt-2">
            <button wire:click="next"
                class="px-5 py-2 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                Next →
            </button>
        </div>
    </div>
    @endif

    {{-- Step 2: Communication style --}}
    @if ($step === 2)
    <div class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tone</label>
            <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                @foreach (['friendly', 'professional', 'enthusiastic', 'calm', 'direct'] as $option)
                <button type="button" wire:click="$set('tone', '{{ $option }}')"
                    class="px-3 py-2 rounded-lg text-xs font-medium border transition-colors
                        {{ $tone === $option
                            ? 'bg-indigo-600 border-indigo-600 text-white'
                            : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-400' }}">
                    {{ ucfirst($option) }}
                </button>
                @endforeach
            </div>
            @error('tone') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Formality</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach (['casual', 'semi-formal', 'formal'] as $option)
                <button type="button" wire:click="$set('formality', '{{ $option }}')"
                    class="px-3 py-2 rounded-lg text-xs font-medium border transition-colors
                        {{ $formality === $option
                            ? 'bg-indigo-600 border-indigo-600 text-white'
                            : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-400' }}">
                    {{ ucfirst($option) }}
                </button>
                @endforeach
            </div>
            @error('formality') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Humor</label>
            <div class="grid grid-cols-4 gap-2">
                @foreach (['none', 'light', 'moderate', 'frequent'] as $option)
                <button type="button" wire:click="$set('humorLevel', '{{ $option }}')"
                    class="px-3 py-2 rounded-lg text-xs font-medium border transition-colors
                        {{ $humorLevel === $option
                            ? 'bg-indigo-600 border-indigo-600 text-white'
                            : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-400' }}">
                    {{ ucfirst($option) }}
                </button>
                @endforeach
            </div>
            @error('humorLevel') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-between pt-2">
            <button wire:click="previous"
                class="px-5 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                ← Back
            </button>
            <button wire:click="next"
                class="px-5 py-2 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                Next →
            </button>
        </div>
    </div>
    @endif

    {{-- Step 3: Backstory --}}
    @if ($step === 3)
    <div class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Backstory / Role <span class="text-gray-400 font-normal">(optional)</span>
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                Describe what your assistant should specialise in or know about you.
            </p>
            <textarea wire:model="backstory" rows="4"
                placeholder="e.g. You're a senior software engineer's assistant, expert in Laravel and DevOps…"
                class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
            @error('backstory') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-between pt-2">
            <button wire:click="previous"
                class="px-5 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                ← Back
            </button>
            <button wire:click="finish" wire:loading.attr="disabled"
                class="px-5 py-2 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                <span wire:loading.remove wire:target="finish">Create my assistant ✨</span>
                <span wire:loading wire:target="finish">Generating…</span>
            </button>
        </div>
    </div>
    @endif

    @endif {{-- end generating check --}}
</div>
