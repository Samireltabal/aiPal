import { marked } from 'marked';

marked.setOptions({ breaks: true });

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        dark: localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        },
    });

    Alpine.data('chatApp', (token, initialConversations, initialConversationId) => ({
        token,
        input: '',
        messages: [],
        streaming: false,
        conversations: initialConversations ?? [],
        activeConversationId: initialConversationId ?? null,

        init() {
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
                    this.conversations = (data.data ?? []).map((c) => ({
                        id: c.id,
                        title: c.title ?? 'Untitled',
                    }));
                }
            } catch (_) {}
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
    }));
});
