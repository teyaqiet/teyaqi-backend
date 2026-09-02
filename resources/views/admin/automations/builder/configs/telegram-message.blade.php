{{-- ============================================================
     TELEGRAM MESSAGE NODE CONFIGURATION
============================================================= --}}

<div
    class="space-y-5"
    x-data="{
        get node() {
            return getSelectedNode();
        }
    }"
>


    {{-- ========================================================
         RECIPIENT
    ========================================================= --}}

    <div>

        <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-600">
            Recipient
        </label>

        <div class="relative">

            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <i class="ik ik-send"></i>
            </span>

            <select
                x-model="node.config.recipient"
                @change="markDirty()"
                class="w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-9 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            >

                <option value="context.telegram_id">
                    Current Player
                </option>

                <option value="config.chat_id">
                    Custom Chat ID
                </option>

            </select>

            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                <i class="ik ik-chevron-down"></i>
            </span>

        </div>

        <p class="mt-1.5 text-xs text-gray-400">
            Current Player automatically uses the Telegram ID from the automation context.
        </p>

    </div>


    {{-- ========================================================
         CUSTOM CHAT ID
    ========================================================= --}}

    <div
        x-show="node && node.config.recipient === 'config.chat_id'"
        x-cloak
    >

        <label
            for="automation-chat-id"
            class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-600"
        >
            Chat ID
        </label>

        <div class="relative">

            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <i class="ik ik-hash"></i>
            </span>

            <input
                id="automation-chat-id"
                type="text"
                x-model="node.config.chat_id"
                @input="markDirty()"
                class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                placeholder="387743546"
            >

        </div>

        <p class="mt-1.5 text-xs text-gray-400">
            Enter a Telegram chat ID or a supported automation context variable.
        </p>

    </div>


    {{-- ========================================================
         MESSAGE
    ========================================================= --}}

    <div>

        <div class="mb-2 flex items-center justify-between">

            <label
                for="automation-message"
                class="block text-xs font-semibold uppercase tracking-wider text-gray-600"
            >
                Message
            </label>

            <span
                class="text-[10px] font-medium text-gray-400"
                x-text="
                    (node.config.message || '').length +
                    ' characters'
                "
            ></span>

        </div>

        <div class="relative">

            <span class="pointer-events-none absolute left-0 top-0 flex items-center pl-3 pt-3 text-gray-400">
                <i class="ik ik-message-square"></i>
            </span>

            <textarea
                id="automation-message"
                x-model="node.config.message"
                @input="markDirty()"
                rows="7"
                class="w-full resize-y rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-sm leading-6 text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                placeholder="🔥 Hey @{{name}}! You reached a @{{streak}} day streak!"
            ></textarea>

        </div>

        <p class="mt-1.5 text-xs leading-5 text-gray-400">
            Use automation context variables such as
            <span class="font-medium text-gray-500">@{{name}}</span>,
            <span class="font-medium text-gray-500">@{{streak}}</span>,
            <span class="font-medium text-gray-500">@{{current_streak}}</span>,
            and
            <span class="font-medium text-gray-500">@{{total_xp}}</span>.
        </p>

    </div>


    {{-- ========================================================
         MEDIA
    ========================================================= --}}

    <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4">

        <div class="mb-4 flex items-start justify-between gap-4">

            <div class="flex items-start gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm">
                    <i class="ik ik-image text-sm"></i>
                </div>

                <div>

                    <div class="text-sm font-semibold text-gray-800">
                        Media
                    </div>

                    <p class="mt-0.5 text-xs leading-5 text-gray-400">
                        Optionally attach an image, video, GIF, audio file, or document.
                    </p>

                </div>

            </div>


            {{-- Media Toggle --}}

            <button
                type="button"
                role="switch"
                :aria-checked="
                    node.config.media?.enabled
                        ? 'true'
                        : 'false'
                "
                @click="toggleTelegramMedia()"
                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full transition"
                :class="
                    node.config.media?.enabled
                        ? 'bg-indigo-600'
                        : 'bg-gray-300'
                "
            >

                <span
                    class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition"
                    :class="
                        node.config.media?.enabled
                            ? 'translate-x-4'
                            : 'translate-x-0'
                    "
                ></span>

            </button>

        </div>


        {{-- ====================================================
             MEDIA SETTINGS
        ===================================================== --}}

        <div
            x-show="node.config.media?.enabled"
            x-cloak
            class="space-y-4"
        >


            {{-- MEDIA TYPE --}}

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-600">
                    Media Type
                </label>

                <div class="relative">

                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="ik ik-file"></i>
                    </span>

                    <select
                        x-model="node.config.media.type"
                        @change="
                            node.config.media.source = '';
                            markDirty();
                        "
                        class="w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-9 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >

                        <option value="photo">
                            Photo
                        </option>

                        <option value="video">
                            Video
                        </option>

                        <option value="animation">
                            GIF / Animation
                        </option>

                        <option value="audio">
                            Audio
                        </option>

                        <option value="document">
                            Document
                        </option>

                    </select>

                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                        <i class="ik ik-chevron-down"></i>
                    </span>

                </div>

            </div>


            {{-- UPLOAD MEDIA --}}

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-600">
                    Upload Media
                </label>

                <label
                    class="group flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white px-5 py-7 text-center transition hover:border-indigo-300 hover:bg-indigo-50/30"
                >

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-100">
                        <i class="ik ik-upload-cloud text-lg"></i>
                    </div>

                    <div class="mt-3 text-sm font-semibold text-gray-700">
                        Choose a file
                    </div>

                    <div class="mt-1 text-xs text-gray-400">
                        Upload an image, video, GIF, audio, or document
                    </div>

                    <input
                        type="file"
                        class="hidden"
                        accept="image/*,video/*,audio/*,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                        @change="handleTelegramMediaUpload($event)"
                    >

                </label>


                {{-- Selected File --}}

                <div
                    x-show="node.config.media?.file_name"
                    x-cloak
                    class="mt-3 flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3"
                >

                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                        <i class="ik ik-file text-sm"></i>
                    </div>

                    <div class="min-w-0 flex-1">

                        <div
                            class="truncate text-xs font-semibold text-gray-700"
                            x-text="node.config.media.file_name"
                        ></div>

                        <div
                            class="mt-0.5 text-[10px] text-gray-400"
                            x-text="
                                node.config.media.file_size
                                    ? (
                                        node.config.media.file_size /
                                        1024 /
                                        1024
                                    ).toFixed(2) + ' MB'
                                    : ''
                            "
                        ></div>

                    </div>


                    <button
                        type="button"
                        @click="removeTelegramMedia()"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500"
                        title="Remove file"
                        aria-label="Remove file"
                    >

                        <i class="ik ik-x text-sm"></i>

                    </button>

                </div>

                <p class="mt-1.5 text-xs text-gray-400">
                    Uploaded files remain available in this browser until the automation is saved.
                </p>

            </div>


            {{-- EXTERNAL SOURCE --}}

            <div>

                <div class="mb-2 flex items-center justify-between">

                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                        URL / Telegram File ID
                    </label>

                    <span
                        x-show="node.config.media?.source"
                        x-cloak
                        class="text-[10px] font-medium text-indigo-500"
                    >
                        External source
                    </span>

                </div>

                <div class="relative">

                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="ik ik-link"></i>
                    </span>

                    <input
                        type="text"
                        x-model="node.config.media.source"
                        @input="markDirty()"
                        class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="https://example.com/image.jpg or Telegram file ID"
                    >

                </div>

                <p class="mt-1.5 text-xs text-gray-400">
                    Use a public URL or an existing Telegram file ID.
                </p>

            </div>


            {{-- SOURCE INFORMATION --}}

            <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 px-3 py-2.5">

                <div class="flex items-start gap-2">

                    <i class="ik ik-info mt-0.5 text-xs text-indigo-500"></i>

                    <p class="text-[11px] leading-5 text-indigo-700">
                        If an uploaded file exists, it will be used before
                        the external URL or Telegram file ID.
                    </p>

                </div>

            </div>


            {{-- CAPTION --}}

            <div>

                <div class="mb-2 flex items-center justify-between">

                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                        Caption
                    </label>

                    <span
                        class="text-[10px] text-gray-400"
                        x-text="
                            (node.config.media.caption || '').length +
                            ' characters'
                        "
                    ></span>

                </div>

                <textarea
                    x-model="node.config.media.caption"
                    @input="markDirty()"
                    rows="4"
                    class="w-full resize-y rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm leading-6 text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    placeholder="Your media caption..."
                ></textarea>

                <p class="mt-1.5 text-xs text-gray-400">
                    The caption supports the same automation variables as the message.
                </p>

            </div>

        </div>

    </div>


    {{-- ========================================================
         INLINE BUTTONS
    ========================================================= --}}

    <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4">

        <div class="mb-4 flex items-start justify-between gap-4">

            <div class="flex items-start gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm">
                    <i class="ik ik-mouse-pointer text-sm"></i>
                </div>

                <div>

                    <div class="text-sm font-semibold text-gray-800">
                        Inline Buttons
                    </div>

                    <p class="mt-0.5 text-xs leading-5 text-gray-400">
                        Add buttons underneath the Telegram message.
                    </p>

                </div>

            </div>


            <button
                type="button"
                @click="addTelegramButton()"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700"
            >

                <i class="ik ik-plus text-sm"></i>

                Add Button

            </button>

        </div>


        {{-- Empty State --}}

        <div
            x-show="
                !node.config.buttons ||
                node.config.buttons.length === 0
            "
            x-cloak
            class="rounded-xl border border-dashed border-gray-200 bg-white p-5 text-center"
        >

            <div class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-gray-50 text-gray-400">
                <i class="ik ik-mouse-pointer text-sm"></i>
            </div>

            <div class="mt-2 text-xs font-medium text-gray-600">
                No buttons configured
            </div>

            <p class="mt-1 text-[11px] text-gray-400">
                Add a button to give the player an action.
            </p>

        </div>


        {{-- Button List --}}

        <div
            x-show="
                node.config.buttons &&
                node.config.buttons.length > 0
            "
            x-cloak
            class="space-y-3"
        >

            <template
                x-for="
                    (button, buttonIndex) in
                    (node.config.buttons || [])
                "
                :key="buttonIndex"
            >

                <div class="rounded-xl border border-gray-200 bg-white p-4">

                    {{-- Header --}}

                    <div class="mb-3 flex items-center justify-between">

                        <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">

                            Button
                            <span x-text="buttonIndex + 1"></span>

                        </div>

                        <button
                            type="button"
                            @click="removeTelegramButton(buttonIndex)"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500"
                            title="Remove button"
                        >

                            <i class="ik ik-trash-2 text-sm"></i>

                        </button>

                    </div>


                    {{-- Fields --}}

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                        {{-- Text --}}

                        <div>

                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">
                                Button Text
                            </label>

                            <input
                                type="text"
                                x-model="button.text"
                                @input="markDirty()"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                placeholder="Open Teyaqi"
                            >

                        </div>


                        {{-- Type --}}

                        <div>

                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">
                                Button Type
                            </label>

                            <select
                                x-model="button.type"
                                @change="markDirty()"
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >

                                <option value="url">
                                    URL
                                </option>

                                <option value="web_app">
                                    Telegram Web App
                                </option>

                            </select>

                        </div>

                    </div>


                    {{-- URL --}}

                    <div class="mt-3">

                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">

                            <span
                                x-text="
                                    button.type === 'web_app'
                                        ? 'Web App URL'
                                        : 'URL'
                                "
                            ></span>

                        </label>

                        <div class="relative">

                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <i class="ik ik-link text-sm"></i>
                            </span>

                            <input
                                type="url"
                                x-model="button.url"
                                @input="markDirty()"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                :placeholder="
                                    button.type === 'web_app'
                                        ? 'https://teyaqi.com/app'
                                        : 'https://teyaqi.com'
                                "
                            >

                        </div>

                    </div>


                    {{-- Preview --}}

                    <div class="mt-3 rounded-lg border border-indigo-100 bg-indigo-50/50 p-3">

                        <div class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-indigo-500">
                            Preview
                        </div>

                        <div class="flex justify-center">

                            <div
                                class="w-full rounded-lg border border-indigo-200 bg-white px-5 py-2 text-center text-xs font-semibold text-indigo-600 shadow-sm"
                                x-text="
                                    button.text ||
                                    'Button'
                                "
                            ></div>

                        </div>

                    </div>

                </div>

            </template>

        </div>

    </div>


    {{-- ========================================================
         PARSE MODE
    ========================================================= --}}

    <div>

        <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-600">
            Parse Mode
        </label>

        <div class="relative">

            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <i class="ik ik-code"></i>
            </span>

            <select
                x-model="node.config.parse_mode"
                @change="markDirty()"
                class="w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-9 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            >

                <option value="">
                    Plain Text
                </option>

                <option value="HTML">
                    HTML
                </option>

                <option value="Markdown">
                    Markdown
                </option>

                <option value="MarkdownV2">
                    Markdown V2
                </option>

            </select>

            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                <i class="ik ik-chevron-down"></i>
            </span>

        </div>

    </div>


    {{-- ========================================================
         MESSAGE OPTIONS
    ========================================================= --}}

    <div class="space-y-3">

        <div class="text-xs font-semibold uppercase tracking-wider text-gray-600">
            Message Options
        </div>


        {{-- Link Preview --}}

        <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 p-4">

            <div class="flex items-start gap-3">

                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-gray-500 shadow-sm">
                    <i class="ik ik-link text-sm"></i>
                </div>

                <div>

                    <div class="text-sm font-semibold text-gray-800">
                        Disable Link Preview
                    </div>

                    <div class="mt-0.5 text-xs leading-5 text-gray-400">
                        Prevent Telegram from generating website previews.
                    </div>

                </div>

            </div>

            <button
                type="button"
                role="switch"
                :aria-checked="
                    node.config.disable_web_page_preview
                        ? 'true'
                        : 'false'
                "
                @click="
                    node.config.disable_web_page_preview =
                        !node.config.disable_web_page_preview;

                    markDirty();
                "
                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full transition"
                :class="
                    node.config.disable_web_page_preview
                        ? 'bg-indigo-600'
                        : 'bg-gray-300'
                "
            >

                <span
                    class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition"
                    :class="
                        node.config.disable_web_page_preview
                            ? 'translate-x-4'
                            : 'translate-x-0'
                    "
                ></span>

            </button>

        </div>


        {{-- Silent Notification --}}

        <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 p-4">

            <div class="flex items-start gap-3">

                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-gray-500 shadow-sm">
                    <i class="ik ik-volume-x text-sm"></i>
                </div>

                <div>

                    <div class="text-sm font-semibold text-gray-800">
                        Silent Notification
                    </div>

                    <div class="mt-0.5 text-xs leading-5 text-gray-400">
                        Send without notification sound.
                    </div>

                </div>

            </div>

            <button
                type="button"
                role="switch"
                :aria-checked="
                    node.config.disable_notification
                        ? 'true'
                        : 'false'
                "
                @click="
                    node.config.disable_notification =
                        !node.config.disable_notification;

                    markDirty();
                "
                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full transition"
                :class="
                    node.config.disable_notification
                        ? 'bg-indigo-600'
                        : 'bg-gray-300'
                "
            >

                <span
                    class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition"
                    :class="
                        node.config.disable_notification
                            ? 'translate-x-4'
                            : 'translate-x-0'
                    "
                ></span>

            </button>

        </div>

    </div>


    {{-- ========================================================
         VARIABLES
    ========================================================= --}}

    <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">

        <div class="flex items-start gap-3">

            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm">
                <i class="ik ik-braces text-sm"></i>
            </div>

            <div class="min-w-0 flex-1">

                <div class="text-xs font-semibold text-indigo-900">
                    Available Variables
                </div>

                <p class="mt-1 text-xs leading-5 text-indigo-700">
                    Variables are resolved from the current automation context when the node executes.
                </p>

                <div class="mt-3 flex flex-wrap gap-2">

                    @foreach([
                        'name',
                        'streak',
                        'current_streak',
                        'total_xp',
                        'username',
                        'xp',
                        'sr'
                    ] as $variable)

                        <button
                            type="button"
                            @click="
                                node.config.message =
                                    (node.config.message || '') +
                                    '@{{{{ $variable }}}}';

                                markDirty();
                            "
                            class="rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-medium text-indigo-700 shadow-sm ring-1 ring-indigo-100 transition hover:bg-indigo-50"
                        >

                            @{{{{ $variable }}}}

                        </button>

                    @endforeach

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================
         TELEGRAM PREVIEW
    ========================================================= --}}

    <div class="rounded-2xl border border-sky-100 bg-sky-50/60 p-4">

        <div class="flex items-start gap-3">

            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-sky-600">
                <i class="ik ik-send text-sm"></i>
            </div>

            <div class="min-w-0 flex-1">

                <div class="text-xs font-semibold text-sky-900">
                    Telegram Preview
                </div>

                <p class="mt-0.5 text-[11px] text-sky-600">
                    Preview of the message configured for this node.
                </p>


                <div class="mt-3 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-sky-100">


                    {{-- MEDIA PREVIEW --}}

                    <div
                        x-show="
                            node.config.media?.enabled &&
                            (
                                node.config.media.preview_url ||
                                node.config.media.source
                            )
                        "
                        x-cloak
                        class="border-b border-gray-100"
                    >

                        {{-- Photo --}}

                        <template
                            x-if="
                                node.config.media?.type === 'photo' &&
                                (
                                    node.config.media.preview_url ||
                                    node.config.media.source
                                )
                            "
                        >

                            <img
                                :src="
                                    node.config.media.preview_url ||
                                    node.config.media.source
                                "
                                class="max-h-64 w-full object-cover"
                                alt="Telegram media preview"
                            >

                        </template>


                        {{-- Animation --}}

                        <template
                            x-if="
                                node.config.media?.type === 'animation' &&
                                (
                                    node.config.media.preview_url ||
                                    node.config.media.source
                                )
                            "
                        >

                            <img
                                :src="
                                    node.config.media.preview_url ||
                                    node.config.media.source
                                "
                                class="max-h-64 w-full object-cover"
                                alt="Telegram GIF preview"
                            >

                        </template>


                        {{-- Video --}}

                        <template
                            x-if="
                                node.config.media?.type === 'video' &&
                                (
                                    node.config.media.preview_url ||
                                    node.config.media.source
                                )
                            "
                        >

                            <video
                                :src="
                                    node.config.media.preview_url ||
                                    node.config.media.source
                                "
                                controls
                                class="max-h-64 w-full bg-black object-contain"
                            ></video>

                        </template>


                        {{-- Audio --}}

                        <template
                            x-if="
                                node.config.media?.type === 'audio' &&
                                (
                                    node.config.media.preview_url ||
                                    node.config.media.source
                                )
                            "
                        >

                            <div class="bg-gray-50 p-4">

                                <audio
                                    :src="
                                        node.config.media.preview_url ||
                                        node.config.media.source
                                    "
                                    controls
                                    class="w-full"
                                ></audio>

                            </div>

                        </template>


                        {{-- Document --}}

                        <template
                            x-if="
                                node.config.media?.type === 'document'
                            "
                        >

                            <div class="flex h-32 items-center justify-center bg-gray-100">

                                <div class="text-center">

                                    <i class="ik ik-file text-2xl text-gray-400"></i>

                                    <div class="mt-2 text-xs font-medium text-gray-500">
                                        Document
                                    </div>

                                    <div
                                        x-show="node.config.media?.file_name"
                                        class="mt-1 max-w-[220px] truncate text-[10px] text-gray-400"
                                        x-text="node.config.media.file_name"
                                    ></div>

                                </div>

                            </div>

                        </template>

                    </div>


                    {{-- TELEGRAM MESSAGE --}}

                    <div class="p-4">

                        <div class="flex items-center gap-2">

                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-xs">
                                🤖
                            </div>

                            <div>

                                <div class="text-[11px] font-semibold text-gray-800">
                                    devteyaqibot
                                </div>

                                <div class="text-[9px] text-gray-400">
                                    Telegram
                                </div>

                            </div>

                        </div>


                        {{-- Caption --}}

                        <div
                            x-show="
                                node.config.media?.enabled &&
                                node.config.media?.caption
                            "
                            x-cloak
                            class="mt-3 whitespace-pre-wrap break-words text-xs leading-5 text-gray-700"
                            x-text="node.config.media.caption"
                        ></div>


                        {{-- Message --}}

                        <div
                            class="mt-3 whitespace-pre-wrap break-words rounded-xl bg-gray-50 p-3 text-xs leading-5 text-gray-700"
                            x-text="
                                node.config.message ||
                                'Your Telegram message will appear here...'
                            "
                        ></div>


                        {{-- Buttons --}}

                        <div
                            x-show="
                                node.config.buttons &&
                                node.config.buttons.length > 0
                            "
                            x-cloak
                            class="mt-3 space-y-1.5"
                        >

                            <template
                                x-for="
                                    (button, buttonIndex) in
                                    (node.config.buttons || [])
                                "
                                :key="buttonIndex"
                            >

                                <div
                                    class="rounded-lg border border-indigo-100 bg-white px-3 py-2 text-center text-xs font-semibold text-indigo-600"
                                    x-text="
                                        button.text ||
                                        'Button'
                                    "
                                ></div>

                            </template>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================
         CONFIGURATION SUMMARY
    ========================================================= --}}

    <div class="rounded-xl border border-gray-100 bg-white p-4">

        <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
            Configuration
        </div>

        <div class="mt-3 space-y-2 text-xs">


            {{-- Recipient --}}

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Recipient
                </span>

                <span
                    class="max-w-[180px] truncate text-right font-medium text-gray-700"
                    x-text="
                        node.config.recipient === 'config.chat_id'
                            ? (
                                node.config.chat_id ||
                                'Custom Chat ID'
                            )
                            : 'Current Player'
                    "
                ></span>

            </div>


            {{-- Message --}}

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Message
                </span>

                <span
                    class="max-w-[180px] truncate text-right font-medium text-gray-700"
                    x-text="
                        node.config.message
                            ? node.config.message.length +
                              ' characters'
                            : 'Empty'
                    "
                ></span>

            </div>


            {{-- Media --}}

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Media
                </span>

                <span
                    class="font-medium capitalize text-gray-700"
                    x-text="
                        node.config.media?.enabled
                            ? (
                                node.config.media.type ||
                                'Enabled'
                            )
                            : 'None'
                    "
                ></span>

            </div>


            {{-- File --}}

            <div
                x-show="
                    node.config.media?.enabled &&
                    node.config.media?.file_name
                "
                x-cloak
                class="flex items-center justify-between gap-4"
            >

                <span class="text-gray-400">
                    File
                </span>

                <span
                    class="max-w-[180px] truncate text-right font-medium text-gray-700"
                    x-text="node.config.media.file_name"
                ></span>

            </div>


            {{-- External Source --}}

            <div
                x-show="
                    node.config.media?.enabled &&
                    node.config.media?.source &&
                    !node.config.media?.file_name
                "
                x-cloak
                class="flex items-center justify-between gap-4"
            >

                <span class="text-gray-400">
                    Source
                </span>

                <span
                    class="max-w-[180px] truncate text-right font-medium text-gray-700"
                    x-text="node.config.media.source"
                ></span>

            </div>


            {{-- Buttons --}}

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Buttons
                </span>

                <span
                    class="font-medium text-gray-700"
                    x-text="
                        (node.config.buttons || []).length +
                        ' button' +
                        (
                            (node.config.buttons || []).length === 1
                                ? ''
                                : 's'
                        )
                    "
                ></span>

            </div>


            {{-- Parse Mode --}}

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Parse Mode
                </span>

                <span
                    class="font-medium text-gray-700"
                    x-text="
                        node.config.parse_mode ||
                        'Plain Text'
                    "
                ></span>

            </div>


            {{-- Link Preview --}}

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Link Preview
                </span>

                <span
                    class="font-medium text-gray-700"
                    x-text="
                        node.config.disable_web_page_preview
                            ? 'Disabled'
                            : 'Enabled'
                    "
                ></span>

            </div>


            {{-- Notification --}}

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Notification
                </span>

                <span
                    class="font-medium text-gray-700"
                    x-text="
                        node.config.disable_notification
                            ? 'Silent'
                            : 'Normal'
                    "
                ></span>

            </div>

        </div>

    </div>

</div>