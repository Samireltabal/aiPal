@props(['size' => 'xs'])

@if (config('aipal.premium_enabled'))
    @php
        $sizeClass = match ($size) {
            'sm' => 'text-[11px] px-2 py-0.5',
            default => 'text-[9px] px-1.5 py-[1px]',
        };
    @endphp
    <span
        {{ $attributes->merge(['class' => "inline-flex items-center gap-0.5 rounded font-bold uppercase tracking-wider bg-gradient-to-r from-amber-400 to-yellow-500 text-amber-950 shadow-sm $sizeClass"]) }}
        title="Premium pack enabled">
        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M10 2l2.39 4.84 5.34.78-3.86 3.77.91 5.31L10 14.27 5.22 16.7l.91-5.31L2.27 7.62l5.34-.78L10 2z"/>
        </svg>
        Premium
    </span>
@endif
