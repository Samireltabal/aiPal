import { marked } from 'marked';

// PWA helpers
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
}

async function subscribeToPush() {
    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
    if (!vapidKey || !('serviceWorker' in navigator) || !('PushManager' in window)) return null;

    const reg = await navigator.serviceWorker.ready;
    const existing = await reg.pushManager.getSubscription();
    if (existing) return existing;

    return reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey),
    });
}

async function sendSubscriptionToServer(subscription, csrfToken) {
    const key = subscription.getKey('p256dh');
    const auth = subscription.getKey('auth');
    await fetch('/push/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({
            endpoint: subscription.endpoint,
            public_key: btoa(String.fromCharCode(...new Uint8Array(key))),
            auth_token: btoa(String.fromCharCode(...new Uint8Array(auth))),
        }),
    });
}

marked.setOptions({ breaks: true });

document.addEventListener('alpine:init', () => {
    Alpine.store('pwa', {
        installable: false,
        install() {
            if (!window.__pwaInstallPrompt) return;
            window.__pwaInstallPrompt.prompt();
            window.__pwaInstallPrompt.userChoice.then(() => { this.installable = false; });
        },
    });

    document.addEventListener('pwa:installable', () => {
        Alpine.store('pwa').installable = true;
    });

    Alpine.data('pushToggle', (initialEnabled, csrfToken) => ({
        enabled: initialEnabled,
        loading: false,
        async toggle() {
            this.loading = true;
            try {
                if (!this.enabled) {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') { this.loading = false; return; }
                    const sub = await subscribeToPush();
                    if (sub) await sendSubscriptionToServer(sub, csrfToken);
                    this.enabled = true;
                } else {
                    const reg = await navigator.serviceWorker.ready;
                    const sub = await reg.pushManager.getSubscription();
                    if (sub) {
                        await fetch('/push/unsubscribe', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ endpoint: sub.endpoint }),
                        });
                        await sub.unsubscribe();
                    }
                    this.enabled = false;
                }
            } finally { this.loading = false; }
        },
    }));

    Alpine.store('theme', {
        dark: localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        },
    });

    Alpine.data('chatApp', (token, initialConversations, initialConversationId, transcribeUrl, ttsUrl) => ({
        token,
        input: '',
        messages: [],
        streaming: false,
        allConversations: initialConversations ?? [],
        conversations: initialConversations ?? [],
        activeConversationId: initialConversationId ?? null,
        conversationSearch: '',
        searchDebounce: null,

        recording: false,
        transcribing: false,
        mediaRecorder: null,
        audioChunks: [],
        ttsEnabled: localStorage.getItem('ttsEnabled') === 'true',
        currentAudio: null,

        init() {
            // Browser-extension "Ask" links open /chat?prefill=<encoded message>.
            // Seed the composer once and strip the param so a refresh doesn't repeat it.
            const params = new URLSearchParams(window.location.search);
            const prefill = params.get('prefill');
            if (prefill) {
                this.input = prefill.slice(0, 8000);
                params.delete('prefill');
                const newSearch = params.toString();
                const newUrl = window.location.pathname + (newSearch ? '?' + newSearch : '') + window.location.hash;
                window.history.replaceState({}, '', newUrl);
                this.$nextTick(() => {
                    const el = this.$refs.messageInput;
                    if (el) {
                        el.style.height = 'auto';
                        el.style.height = el.scrollHeight + 'px';
                        el.focus();
                    }
                });
            }

            this.$watch('activeConversationId', (id) => {
                if (this.streaming) return;
                if (id) {
                    this.loadConversationMessages(id);
                } else {
                    this.messages = [];
                }
            });

            if (this.activeConversationId) {
                this.loadConversationMessages(this.activeConversationId);
            }
        },

        renderMarkdown(text) {
            return marked.parse(text || '');
        },

        async loadConversationMessages(conversationId) {
            this.messages = [];
            try {
                const res = await fetch(`/api/v1/conversations/${conversationId}/messages`, {
                    headers: { Authorization: `Bearer ${this.token}`, Accept: 'application/json' },
                });
                if (res.ok) {
                    const data = await res.json();
                    this.messages = (data.data ?? []).map((m) => ({
                        role: m.role,
                        content: m.content,
                        streaming: false,
                    }));
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (_) {}
        },

        async loadConversations() {
            try {
                const res = await fetch('/api/v1/conversations', {
                    headers: { Authorization: `Bearer ${this.token}`, Accept: 'application/json' },
                });
                if (res.ok) {
                    const data = await res.json();
                    const mapped = (data.data ?? []).map((c) => ({
                        id: c.id,
                        title: c.title ?? 'Untitled',
                    }));
                    this.allConversations = mapped;
                    this.conversations = mapped;
                }
            } catch (_) {}
        },

        onConversationSearchInput(value) {
            this.conversationSearch = value;
            clearTimeout(this.searchDebounce);
            const query = value.trim();
            if (query.length < 2) {
                this.conversations = this.allConversations;
                return;
            }
            this.searchDebounce = setTimeout(async () => {
                try {
                    const res = await fetch(`/api/v1/conversations/search?q=${encodeURIComponent(query)}`, {
                        headers: { Authorization: `Bearer ${this.token}`, Accept: 'application/json' },
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.conversations = (data.data ?? []).map((c) => ({
                            id: c.id,
                            title: c.title ?? 'Untitled',
                        }));
                    }
                } catch (_) {}
            }, 300);
        },

        clearConversationSearch() {
            this.conversationSearch = '';
            this.conversations = this.allConversations;
        },

        async deleteConversation(id) {
            await fetch(`/api/v1/conversations/${id}`, {
                method: 'DELETE',
                headers: { Authorization: `Bearer ${this.token}` },
            });
            if (this.activeConversationId === id) {
                this.activeConversationId = null;
                this.messages = [];
            }
            this.allConversations = this.allConversations.filter((c) => c.id !== id);
            this.conversations = this.conversations.filter((c) => c.id !== id);
        },

        newConversation() {
            this.activeConversationId = null;
            this.messages = [];
            this.$refs.messageInput?.focus();
        },

        selectConversation(id) {
            this.activeConversationId = id;
        },

        async sendMessage() {
            const text = this.input.trim();
            if (!text || this.streaming) return;

            this.input = '';
            this.$refs.messageInput.style.height = 'auto';
            this.messages.push({ role: 'user', content: text, streaming: false });
            this.$nextTick(() => this.scrollToBottom());

            const assistantIndex = this.messages.length;
            this.messages.push({ role: 'assistant', content: '', streaming: true });
            this.streaming = true;

            try {
                const body = { message: text };
                if (this.activeConversationId) {
                    body.conversation_id = this.activeConversationId;
                }

                const response = await fetch('/api/v1/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'text/event-stream',
                        Authorization: `Bearer ${this.token}`,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify(body),
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() ?? '';

                    let eventType = null;
                    for (const line of lines) {
                        if (line.startsWith('event: ')) {
                            eventType = line.slice(7).trim();
                        } else if (line.startsWith('data: ')) {
                            const payload = JSON.parse(line.slice(6));
                            if (eventType === 'delta') {
                                this.messages[assistantIndex].content += payload.text;
                                this.$nextTick(() => this.scrollToBottom());
                            } else if (eventType === 'done') {
                                this.activeConversationId = payload.conversation_id;
                                this.loadConversations();
                                if (this.ttsEnabled && this.messages[assistantIndex]?.content) {
                                    this.speak(this.messages[assistantIndex].content);
                                }
                            } else if (eventType === 'error') {
                                this.messages[assistantIndex].content = '⚠️ ' + payload.message;
                            }
                            eventType = null;
                        }
                    }
                }
            } catch (err) {
                if (this.messages[assistantIndex]) {
                    this.messages[assistantIndex].content = '⚠️ ' + err.message;
                }
            } finally {
                if (this.messages[assistantIndex]) {
                    this.messages[assistantIndex].streaming = false;
                }
                this.streaming = false;
                this.$refs.messageInput?.focus();
            }
        },

        scrollToBottom() {
            const el = this.$refs.messages;
            if (el) el.scrollTop = el.scrollHeight;
        },

        saveTtsPreference() {
            localStorage.setItem('ttsEnabled', this.ttsEnabled ? 'true' : 'false');
        },

        async startRecording() {
            if (this.recording || this.streaming) return;
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.audioChunks = [];
                this.mediaRecorder = new MediaRecorder(stream);
                this.mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) this.audioChunks.push(e.data);
                };
                this.mediaRecorder.start();
                this.recording = true;
            } catch (_) {
                alert('Microphone access denied. Please allow microphone access to use voice input.');
            }
        },

        async stopRecording() {
            if (!this.recording || !this.mediaRecorder) return;
            this.recording = false;
            this.transcribing = true;

            await new Promise((resolve) => {
                this.mediaRecorder.onstop = resolve;
                this.mediaRecorder.stop();
                this.mediaRecorder.stream.getTracks().forEach((t) => t.stop());
            });

            try {
                const mimeType = this.mediaRecorder.mimeType || 'audio/webm';
                const blob = new Blob(this.audioChunks, { type: mimeType });
                const ext = mimeType.includes('ogg') ? 'ogg' : mimeType.includes('mp4') ? 'mp4' : 'webm';

                const form = new FormData();
                form.append('audio', blob, `recording.${ext}`);
                form.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

                const res = await fetch(transcribeUrl, { method: 'POST', body: form });
                if (res.ok) {
                    const data = await res.json();
                    if (data.transcript) {
                        this.input = (this.input + ' ' + data.transcript).trim();
                        this.$nextTick(() => {
                            const ta = this.$refs.messageInput;
                            if (ta) { ta.style.height = 'auto'; ta.style.height = ta.scrollHeight + 'px'; }
                        });
                    }
                }
            } catch (_) {}

            this.transcribing = false;
            this.mediaRecorder = null;
            this.audioChunks = [];
        },

        async speak(text) {
            if (this.currentAudio) {
                this.currentAudio.pause();
                this.currentAudio = null;
            }

            const plain = text.replace(/<[^>]+>/g, '').replace(/[*_`#>]/g, '').trim();
            if (!plain) return;

            try {
                const res = await fetch(ttsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ text: plain }),
                });

                if (!res.ok) return;

                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                this.currentAudio = new Audio(url);
                this.currentAudio.onended = () => { URL.revokeObjectURL(url); this.currentAudio = null; };
                this.currentAudio.play();
            } catch (_) {}
        },
    }));
});

// ─── Location auto-save ─────────────────────────────────────────────────────
// On every page load, silently save the user's location if permission is granted.
// If permission not yet asked, do nothing (user can opt in via dashboard card or Settings).

(function initLocationAutoSave() {
    if (!('geolocation' in navigator)) return;

    const getCsrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    async function saveLocation(lat, lon, source = 'browser', force = false) {
        try {
            const res = await fetch('/api/v1/location', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrf(),
                },
                body: JSON.stringify({ latitude: lat, longitude: lon, source, force }),
            });

            if (!res.ok) return null;
            const data = await res.json();
            if (data.updated && data.location) {
                showToast('📍 Saved your location as ' + data.location);
                // Refresh Livewire components that depend on location
                if (window.Livewire) {
                    window.Livewire.dispatch('location-updated');
                    // Also refresh any mounted WeatherCard so the new data shows immediately
                    setTimeout(() => { window.location.reload(); }, 1200);
                }
            } else if (force) {
                showToast('⏱️ Already up to date.');
            }
            return data;
        } catch (_) { return null; }
    }

    function showToast(message) {
        const existing = document.getElementById('location-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'location-toast';
        toast.className = 'fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg bg-gray-900 dark:bg-gray-700 text-white text-sm shadow-lg max-w-xs';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 300ms';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    async function detectAndSave(explicit = false) {
        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    // Explicit button clicks bypass the 10-min throttle
                    saveLocation(pos.coords.latitude, pos.coords.longitude, 'browser', explicit).then(resolve);
                },
                (err) => {
                    if (explicit) showToast('⚠️ Location access denied or unavailable.');
                    resolve(null);
                },
                { enableHighAccuracy: explicit, timeout: 10000, maximumAge: explicit ? 0 : 600000 },
            );
        });
    }

    // Silent auto-save if permission already granted
    async function autoSaveIfGranted() {
        if (!navigator.permissions) {
            return;
        }
        try {
            const status = await navigator.permissions.query({ name: 'geolocation' });
            if (status.state === 'granted') {
                await detectAndSave(false);
            }
        } catch (_) { /* ignore */ }
    }

    // Hook up explicit 'Detect my location' buttons anywhere in the page
    function wireButtons() {
        document.querySelectorAll('[data-action="detect-location"]').forEach((btn) => {
            if (btn.dataset.locationWired) return;
            btn.dataset.locationWired = '1';
            btn.addEventListener('click', () => detectAndSave(true));
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        autoSaveIfGranted();
        wireButtons();
    });

    // Also re-wire after Livewire morphs the DOM
    document.addEventListener('livewire:navigated', wireButtons);
    document.addEventListener('livewire:morph.updated', wireButtons);
})();

