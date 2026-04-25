@props(['active' => 'dashboard'])

<div x-data="{ open: false }" class="w-full lg:w-auto lg:flex-shrink-0">

    {{-- Mobile top bar --}}
    <div class="lg:hidden flex items-center gap-3 px-4 h-14 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <button @click="open = true"
            class="p-2 -ml-1 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div class="flex items-center gap-2 flex-1">
            @if (auth()->user()->persona?->avatar_path)
                <img src="{{ asset('storage/'.auth()->user()->persona->avatar_path) }}" alt="Avatar" class="w-6 h-6 rounded-full object-cover">
            @endif
            <span class="font-semibold text-sm text-gray-900 dark:text-white">aiPal</span>
        </div>
        <button onclick="Alpine.store('theme').toggle()"
            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-4 h-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <svg class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </button>
    </div>

    {{-- Mobile backdrop --}}
    <div x-show="open"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        @click="open = false"
        style="display:none">
    </div>

    {{-- Sidebar panel --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-72 flex flex-col
                  border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800
                  transition-transform duration-200 ease-in-out
                  lg:static lg:w-64 lg:translate-x-0 lg:flex"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        style="display:flex">

        {{-- Header --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2">
                @if (auth()->user()->persona?->avatar_path)
                    <img src="{{ asset('storage/'.auth()->user()->persona->avatar_path) }}" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
                @endif
                <span class="font-semibold text-sm">aiPal</span>
            </div>
            <div class="flex items-center gap-1">
                <button onclick="Alpine.store('theme').toggle()"
                    class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
                <button @click="open = false"
                    class="lg:hidden p-1.5 rounded-lg text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Search trigger --}}
        <div class="px-3 pt-2 pb-1 flex-shrink-0">
            <button
                onclick="Livewire.dispatch('search:open')"
                class="w-full flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700/50 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="flex-1 text-left">Search</span>
                <kbd class="hidden sm:inline-flex items-center gap-0.5 text-xs text-gray-400 dark:text-gray-500">
                    <span>⌘</span><span>K</span>
                </kbd>
            </button>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
            @php
                $base   = 'flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors';
                $active_cls = 'flex items-center gap-2 px-3 py-2 text-xs rounded-lg bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 font-medium';
            @endphp

            <a href="{{ route('dashboard') }}" class="{{ $active === 'dashboard' ? $active_cls : $base }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="{{ route('chat') }}" class="{{ $active === 'chat' ? $active_cls : $base }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Chat
            </a>
            <a href="{{ route('memories') }}" class="{{ $active === 'memories' ? $active_cls : $base }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                Memories
            </a>
            <a href="{{ route('documents') }}" class="{{ $active === 'documents' ? $active_cls : $base }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Documents
            </a>
            <a href="{{ route('productivity') }}" class="{{ $active === 'productivity' ? $active_cls : $base }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Productivity
            </a>
            <a href="{{ route('workflows') }}" class="{{ $active === 'workflows' ? $active_cls : $base }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Workflows
            </a>
            <a href="{{ route('usage') }}" class="{{ $active === 'usage' ? $active_cls : $base }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Usage
            </a>
            <a href="{{ route('contexts') }}" class="{{ $active === 'contexts' ? $active_cls : $base }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                Contexts
            </a>
            <a href="{{ route('settings') }}" class="{{ $active === 'settings' ? $active_cls : $base }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.invitations') }}" class="{{ $active === 'invitations' ? $active_cls : $base }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Invitations
            </a>
            @endif
        </nav>

        {{-- PWA install banner --}}
        <div x-show="$store.pwa.installable"
            x-transition
            class="mx-3 mb-2 p-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 flex-shrink-0"
            style="display:none">
            <p class="text-xs text-indigo-700 dark:text-indigo-300 font-medium mb-2">Install aiPal on your device</p>
            <button @click="$store.pwa.install()"
                class="w-full text-xs px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                Add to Home Screen
            </button>
        </div>

        {{-- Sign out --}}
        <div class="p-3 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="{{ $base }}  w-full">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>
</div>
