<div x-data="{ chatSidebarOpen: false }" class="flex flex-col h-full flex-1 min-w-0">

    {{-- Mobile top bar --}}
    <div class="lg:hidden flex-shrink-0 flex items-center gap-3 px-4 h-14 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 z-20">
        <button @click="chatSidebarOpen = true"
            class="p-2 -ml-1 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="flex items-center gap-2 flex-1 min-w-0">
            @if (auth()->user()->persona?->avatar_path)
                <img src="{{ asset('storage/'.auth()->user()->persona->avatar_path) }}" alt="Avatar" class="w-6 h-6 rounded-full object-cover flex-shrink-0">
            @endif
            <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">aiPal</span>
        </div>
        <button onclick="Alpine.store('theme').toggle()"
            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-4 h-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <svg class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </button>
    </div>

    {{-- Chat layout --}}
    <div class="flex flex-1 min-h-0"
        x-data="chatApp(@js($apiToken), @js($conversations), @js($activeConversationId), @js(route('voice.transcribe')), @js(route('voice.tts')))"
        x-init="init()">

        {{-- Mobile backdrop --}}
        <div x-show="chatSidebarOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="lg:hidden fixed inset-0 z-40 bg-black/50"
            @click="chatSidebarOpen = false">
        </div>

        {{-- Sidebar --}}
        <aside class="fixed lg:static inset-y-0 left-0 z-50 w-72 lg:w-64 flex-shrink-0 flex flex-col
                      border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800
                      transition-transform duration-200 ease-in-out
                      lg:translate-x-0"
            :class="chatSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    @if (auth()->user()->persona?->avatar_path)
                        <img src="{{ asset('storage/'.auth()->user()->persona->avatar_path) }}" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
                    @endif
                    <span class="font-semibold text-sm">aiPal</span>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="$store.theme.toggle()"
                        class="p-1.5 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                        title="Toggle dark mode">
                        <svg x-show="!$store.theme.dark" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg x-show="$store.theme.dark" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
                    <button @click="chatSidebarOpen = false"
                        class="lg:hidden p-1.5 rounded-lg text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="p-3">
                <button @click="newConversation(); chatSidebarOpen = false"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors border border-gray-300 dark:border-gray-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New chat
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-2 pb-2 space-y-0.5">
                <template x-for="conv in conversations" :key="conv.id">
                    <div class="group flex items-center gap-1 rounded-lg px-2 py-1.5 cursor-pointer transition-colors"
                        :class="activeConversationId === conv.id ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300' : 'hover:bg-gray-200 dark:hover:bg-gray-700'"
                        @click="selectConversation(conv.id); chatSidebarOpen = false">
                        <span class="flex-1 text-xs truncate" x-text="conv.title"></span>
                        <button
                            class="opacity-0 group-hover:opacity-100 p-0.5 rounded text-gray-400 hover:text-red-500 transition-all"
                            @click.stop="deleteConversation(conv.id)">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </nav>

            <div class="p-3 border-t border-gray-200 dark:border-gray-700 space-y-0.5">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('memories') }}"
                    class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                    Memories
                </a>
                <a href="{{ route('documents') }}"
                    class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Documents
                </a>
                <a href="{{ route('productivity') }}"
                    class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Productivity
                </a>
                <a href="{{ route('settings') }}"
                    class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Settings
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.invitations') }}"
                    class="flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Invitations
                </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-2 px-3 py-2 text-xs rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main chat area --}}
        <main class="flex-1 flex flex-col min-w-0">
            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4" x-ref="messages">
                <template x-for="(msg, idx) in messages" :key="idx">
                    <div class="flex gap-3" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[85%] sm:max-w-[75%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                            :class="msg.role === 'user'
                                ? 'bg-indigo-600 text-white rounded-br-sm'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-bl-sm'">
                            <div x-html="renderMarkdown(msg.content)"></div>
                            <template x-if="msg.streaming">
                                <span class="inline-block w-1.5 h-4 ml-0.5 bg-current animate-pulse rounded-sm align-middle"></span>
                            </template>
                            <template x-if="msg.role === 'assistant' && !msg.streaming && msg.content">
                                <button @click="speak(msg.content)"
                                    class="mt-1.5 flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500 hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors"
                                    title="Read aloud">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6v12m0 0l-3-3m3 3l3-3M9.172 9.172a4 4 0 000 5.656"/></svg>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M12 18h.01"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="messages.length === 0">
                    <div class="flex flex-col items-center justify-center h-full text-center py-16">
                        <div class="text-4xl mb-4">💬</div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Start a conversation</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Ask me anything — I'm here to help.</p>
                    </div>
                </template>
            </div>

            {{-- Input --}}
            <div class="border-t border-gray-200 dark:border-gray-700 p-3 sm:p-4 w-full">
                <form @submit.prevent="sendMessage()" class="flex gap-2 items-end w-full">
                    <button type="button"
                        @mousedown="startRecording()" @mouseup="stopRecording()" @touchstart.prevent="startRecording()" @touchend.prevent="stopRecording()"
                        class="flex-shrink-0 p-2.5 sm:p-3 rounded-xl border transition-colors"
                        :class="recording
                            ? 'bg-red-500 border-red-500 text-white animate-pulse'
                            : 'border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:border-indigo-400 hover:text-indigo-500'"
                        :disabled="streaming || transcribing"
                        title="Hold to record voice">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                    </button>

                    <textarea
                        x-model="input"
                        @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                        :placeholder="transcribing ? 'Transcribing…' : 'Message aiPal…'"
                        rows="1"
                        class="flex-1 resize-none rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2.5 sm:px-4 sm:py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors max-h-32 overflow-y-auto"
                        :disabled="streaming || transcribing"
                        x-ref="messageInput"
                        @input="$el.style.height='auto'; $el.style.height=$el.scrollHeight+'px'">
                    </textarea>

                    <button type="button" @click="ttsEnabled = !ttsEnabled; saveTtsPreference()"
                        class="flex-shrink-0 p-2.5 sm:p-3 rounded-xl border transition-colors"
                        :class="ttsEnabled
                            ? 'bg-indigo-600 border-indigo-600 text-white'
                            : 'border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:border-indigo-400'"
                        title="Toggle auto-speak responses">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6v12m0 0l-3-3m3 3l3-3"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.07 4.929a10 10 0 010 14.142"/>
                        </svg>
                    </button>

                    <button type="submit"
                        class="flex-shrink-0 p-2.5 sm:p-3 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        :disabled="!input.trim() || streaming">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 text-center hidden sm:block">
                    AI can make mistakes. Verify important information.
                </p>
            </div>
        </main>
    </div>
</div>
