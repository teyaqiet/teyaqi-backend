<!-- Chat drawer (toggled by header message icon via the shared `chatOpen` state) -->
<div x-data="chatDrawer()" 
     x-show="chatOpen" 
     x-transition.opacity
     @keydown.escape.window="active ? (active = null) : (chatOpen = false)"
     x-effect="if (! chatOpen) active = null"
     class="fixed inset-0 z-50 bg-black/40" 
     style="display:none"
     @click.self="chatOpen = false">

    {{-- ===================== CHAT LIST DRAWER ===================== --}}
    <aside class="absolute inset-y-0 right-0 flex w-80 max-w-full flex-col bg-white shadow-xl"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0">

        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <h6 class="font-semibold text-gray-700">🤖 Teyaqi Assistant</h6>
            <button @click="chatOpen = false" class="text-gray-400 hover:text-gray-600">
                <i class="ik ik-x"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-for="c in contacts" :key="c.handle">
                <button type="button" 
                        @click="open(c)"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-gray-50"
                        :class="active && active.handle === c.handle ? 'bg-primary-50' : ''">
                    <span class="relative shrink-0">
                        <img :src="c.img" class="h-10 w-10 rounded-full object-cover" alt="">
                        <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white" :class="c.status"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-gray-700" x-text="c.name"></span>
                            <span class="shrink-0 text-[11px] text-gray-400" x-text="c.time"></span>
                        </span>
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate text-xs text-gray-400" x-text="c.preview"></span>
                            <span x-show="c.unread" x-cloak class="flex h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-primary-500 px-1 text-[10px] font-semibold text-white" x-text="c.unread"></span>
                        </span>
                    </span>
                </button>
            </template>
            <p x-show="filtered.length === 0" x-cloak class="px-4 py-10 text-center text-sm text-gray-400">{{ __('No conversations found') }}</p>
        </div>
    </aside>

    {{-- ===================== FLOATING CHAT PANEL ===================== --}}
    <div x-show="active" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute inset-x-3 bottom-3 flex h-[36rem] max-h-[calc(100%-2rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 sm:inset-x-auto sm:bottom-4 sm:right-[21rem] sm:w-[32rem]">

        <div class="flex items-center gap-2.5 border-b border-gray-100 bg-white px-4 py-3">
            <span class="relative shrink-0">
                <img :src="active?.img" class="h-8 w-8 rounded-full object-cover" alt="">
                <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white" :class="active?.status"></span>
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-gray-700" x-text="active?.name"></p>
                <p class="truncate text-[11px]" :class="active?.online ? 'text-green-500' : 'text-gray-400'" x-text="active?.online ? '{{ __('Online') }}' : '{{ __('Offline') }}'"></p>
            </div>
            <button @click="active = null" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="{{ __('Close chat') }}">
                <i class="ik ik-x text-sm"></i>
            </button>
        </div>

        <div x-ref="thread" class="flex-1 space-y-3 overflow-y-auto bg-gray-50 p-4">
            <template x-for="(m, i) in (active?.messages || [])" :key="i">
                <div class="flex" :class="m.from === 'me' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[92%]">
                        <div class="prose-chat rounded-2xl px-4 py-2.5 text-[13px] leading-relaxed break-words shadow-sm"
                             :class="m.from === 'me' ? 'rounded-br-sm bg-primary-500 text-white' : 'rounded-bl-sm bg-white text-gray-800'"
                             x-html="renderMarkdown(m.text)"></div>
                        <p class="mt-1 text-[10px] text-gray-400" :class="m.from === 'me' ? 'text-right' : ''" x-text="m.time"></p>
                    </div>
                </div>
            </template>
        </div>

        <form @submit.prevent="send()" class="flex items-center gap-2 border-t border-gray-100 px-3 py-2.5">
            <input type="text" 
                   x-model="draft" 
                   placeholder="Ask Teyaqi Assistant..."
                   class="flex-1 rounded-full border border-gray-200 bg-gray-50 px-4 py-2 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100">
            <button type="submit" 
                    :disabled="! draft.trim()"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-500 text-white transition hover:bg-primary-600 disabled:opacity-40">
                <i class="ik ik-send text-sm"></i>
            </button>
        </form>
    </div>
</div>

<style>
    .prose-chat h1, .prose-chat h2, .prose-chat h3, .prose-chat h4 {
        font-weight: 700;
        margin-top: 0.75rem;
        margin-bottom: 0.25rem;
        color: inherit;
    }
    .prose-chat h1 { font-size: 1.1rem; }
    .prose-chat h2 { font-size: 1.0rem; }
    .prose-chat h3 { font-size: 0.9rem; }
    .prose-chat h4 { font-size: 0.85rem; }

    .prose-chat p {
        margin-bottom: 0.5rem;
    }
    .prose-chat p:last-child {
        margin-bottom: 0;
    }

    .prose-chat ul {
        list-style-type: disc;
        padding-left: 1.25rem;
        margin-top: 0.25rem;
        margin-bottom: 0.5rem;
    }
    .prose-chat ol {
        list-style-type: decimal;
        padding-left: 1.25rem;
        margin-top: 0.25rem;
        margin-bottom: 0.5rem;
    }
    .prose-chat li {
        margin-bottom: 0.125rem;
    }

    .prose-chat code {
        background-color: rgba(0, 0, 0, 0.06);
        padding: 0.15rem 0.35rem;
        border-radius: 0.25rem;
        font-family: monospace;
        font-size: 0.825em;
    }

    .prose-chat pre {
        background-color: #1e293b;
        color: #f8fafc;
        padding: 0.75rem;
        border-radius: 0.5rem;
        overflow-x: auto;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .prose-chat pre code {
        background-color: transparent;
        padding: 0;
        color: inherit;
    }

    .prose-chat table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.8rem;
    }
    .prose-chat th, .prose-chat td {
        border: 1px solid rgba(0, 0, 0, 0.1);
        padding: 0.35rem 0.5rem;
        text-align: left;
    }
    .prose-chat th {
        background-color: rgba(0, 0, 0, 0.04);
        font-weight: 600;
    }
</style>

<script>
function chatDrawer() {
    return {
        search: '',
        draft: '',
        active: null,

        contacts: [
            {
                name: 'Teyaqi AI Assistant',
                handle: '@teyaqi_ai',
                img: "{{ asset('img/ai.png') }}",
                status: 'bg-green-500',
                online: true,
                time: 'now',
                unread: 0,
                preview: 'Ready to help you manage Teyaqi.',
                messages: [
                    {
                        from: 'ai',
                        text: 'Hello 👋 I am your Teyaqi Admin Assistant. Ask me anything about players, questions, analytics, or challenges.',
                        time: 'now'
                    }
                ]
            }
        ],

        init() {
            this.active = this.contacts[0];
            this.configureMarked();
            this.$nextTick(() => this.scrollDown());
        },

        configureMarked() {
            if (typeof marked === 'undefined') return;

            marked.setOptions({
                gfm: true,
                breaks: true
            });
        },

        extractText(data) {
            let raw = data.reply || data.text || data;

            // If it's an object with a text/reply property
            if (typeof raw === 'object' && raw !== null) {
                raw = raw.text || raw.reply || JSON.stringify(raw);
            }

            // If it's a JSON string representing an object, decode it
            if (typeof raw === 'string' && raw.trim().startsWith('{')) {
                try {
                    const parsed = JSON.parse(raw);
                    return parsed.text || parsed.reply || raw;
                } catch (e) {
                    return raw;
                }
            }

            return typeof raw === 'string' ? raw : JSON.stringify(raw);
        },

        renderMarkdown(text) {
            if (!text) return '';
            
            const cleanText = typeof text === 'string' ? text : this.extractText(text);

            if (typeof marked !== 'undefined') {
                return marked.parse(cleanText);
            }

            return cleanText.replace(/\n/g, '<br>');
        },

        get filtered() {
            const q = this.search.toLowerCase();
            return this.contacts.filter(c => 
                c.name.toLowerCase().includes(q) || 
                c.handle.toLowerCase().includes(q)
            );
        },

        open(c) {
            this.active = c;
            c.unread = 0;
            this.draft = '';
            this.$nextTick(() => this.scrollDown());
        },

        async send() {
            const text = this.draft.trim();
            if (!text || !this.active) return;

            this.active.messages.push({
                from: 'me',
                text: text,
                time: 'now'
            });

            this.active.preview = text;
            this.active.time = 'now';
            this.draft = '';
            this.$nextTick(() => this.scrollDown());

            const loadingMsg = {
                from: 'ai',
                text: 'Thinking...',
                time: 'now'
            };
            this.active.messages.push(loadingMsg);
            this.$nextTick(() => this.scrollDown());

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                const response = await fetch('/admin/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ message: text })
                });

                if (!response.ok) {
                    throw new Error(`Server returned HTTP ${response.status}`);
                }

                const data = await response.json();

                this.active.messages = this.active.messages.filter(m => m !== loadingMsg);
                
                // Safely parse out string content using extractText helper
                const replyText = this.extractText(data);

                this.active.messages.push({
                    from: 'ai',
                    text: replyText,
                    time: 'now'
                });
            } catch (error) {
                console.error('Chat API error:', error);

                this.active.messages = this.active.messages.filter(m => m !== loadingMsg);
                this.active.messages.push({
                    from: 'ai',
                    text: 'Sorry, something went wrong while processing your request.',
                    time: 'now'
                });
            }

            this.$nextTick(() => this.scrollDown());
        },

        scrollDown() {
            const el = this.$refs.thread;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        }
    };
}
</script>