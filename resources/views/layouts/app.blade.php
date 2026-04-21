<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" x-data x-bind:class="{ 'dark': $store.theme.dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'aiPal') }}</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#4f46e5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'aiPal') }}">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    @if(config('services.vapid.public_key'))
        <meta name="vapid-public-key" content="{{ config('services.vapid.public_key') }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div class="flex h-full">
        {{ $slot }}
    </div>

    @auth
        <livewire:unified-search />
    @endauth
    @livewireScripts

    {{-- Unified search: Cmd+K / Ctrl+K --}}
    <script>
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                Livewire.dispatch('search:open');
            }
        });
    </script>

    {{-- PWA: service worker + install prompt --}}
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }

        window.__pwaInstallPrompt = null;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            window.__pwaInstallPrompt = e;
            document.dispatchEvent(new CustomEvent('pwa:installable'));
        });
    </script>
</body>
</html>
