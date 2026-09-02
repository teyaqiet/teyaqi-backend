@php
    $isEdit = isset($broadcast);

    $formAction = $isEdit
        ? route('admin.broadcasts.update', $broadcast)
        : route('admin.broadcasts.store');

    $existingFilters = $isEdit
        ? ($broadcast->filters ?? [])
        : [];

    $existingButtons = $isEdit
        ? ($broadcast->buttons ?? [])
        : [];

    $oldButtons = old('buttons', $existingButtons);

    $oldAudienceType = old(
        'audience_type',
        $isEdit ? $broadcast->audience_type : 'all'
    );

    $oldChannel = old(
        'channel',
        $isEdit ? $broadcast->channel : 'telegram'
    );

    $oldType = old(
        'type',
        $isEdit ? $broadcast->type : 'manual'
    );

    $oldScheduledAt = old(
        'scheduled_at',
        $isEdit && $broadcast->scheduled_at
            ? \Carbon\Carbon::parse($broadcast->scheduled_at)->format('Y-m-d\TH:i')
            : ''
    );

    $existingMediaPath = $isEdit
        ? ($broadcast->media_path ?? null)
        : null;

    $existingMediaUrl = $isEdit
        ? ($broadcast->media_url ?? null)
        : null;

    $existingMediaType = $isEdit
        ? ($broadcast->media_type ?? null)
        : null;
@endphp


<form
    method="POST"
    action="{{ $formAction }}"
    enctype="multipart/form-data"
    class="space-y-6"
    x-data="broadcastForm()"
    @submit="submitting = true"
>
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif


    {{-- ============================================================ --}}
    {{-- TWO COLUMN LAYOUT --}}
    {{-- ============================================================ --}}

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">


        {{-- ======================================================== --}}
        {{-- LEFT COLUMN --}}
        {{-- ======================================================== --}}

        <div class="space-y-6 xl:col-span-7">


            {{-- ==================================================== --}}
            {{-- BASIC INFORMATION --}}
            {{-- ==================================================== --}}

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

                <div class="border-b border-gray-100 px-5 py-4">

                    <div class="flex items-center gap-3">

                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <i class="ik ik-send text-base"></i>
                        </div>

                        <div>
                            <h2 class="text-sm font-semibold text-gray-800">
                                Broadcast Information
                            </h2>

                            <p class="mt-0.5 text-xs text-gray-500">
                                Define the message you want to send.
                            </p>
                        </div>

                    </div>

                </div>


                <div class="space-y-5 p-5">


                    {{-- TITLE --}}

                    <div>

                        <label
                            for="title"
                            class="block text-xs font-semibold text-gray-600"
                        >
                            Broadcast Title
                            <span class="text-rose-500">*</span>
                        </label>

                        <input
                            id="title"
                            name="title"
                            type="text"
                            value="{{ old('title', $isEdit ? $broadcast->title : '') }}"
                            placeholder="Example: Weekend Challenge Reminder"
                            required
                            class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-primary-500 focus:ring-primary-500/20"
                        >

                        <p class="mt-1.5 text-xs text-gray-400">
                            Internal title used to identify this broadcast.
                        </p>

                    </div>


                    {{-- MESSAGE --}}

                    <div>

                        <div class="flex items-center justify-between">

                            <label
                                for="message"
                                class="block text-xs font-semibold text-gray-600"
                            >
                                Message
                                <span class="text-rose-500">*</span>
                            </label>

                            <span
                                class="text-xs text-gray-400"
                                x-text="message.length + ' characters'"
                            ></span>

                        </div>

                        <textarea
                            id="message"
                            name="message"
                            rows="9"
                            x-model="message"
                            placeholder="Write your message..."
                            required
                            class="mt-2 block w-full resize-y rounded-lg border-gray-200 bg-white px-3.5 py-3 text-sm text-gray-700 shadow-sm transition focus:border-primary-500 focus:ring-primary-500/20"
                        >{{ old('message', $isEdit ? $broadcast->message : '') }}</textarea>

                        <div class="mt-2 flex items-start gap-2 text-xs text-gray-400">

                            <i class="ik ik-info mt-0.5 text-sm"></i>

                            <p>
                                You can use player variables such as

                                <code class="rounded bg-gray-100 px-1 py-0.5 text-gray-600">
                                    @{{name}}
                                </code>

                                ,

                                <code class="rounded bg-gray-100 px-1 py-0.5 text-gray-600">
                                    @{{streak}}
                                </code>

                                ,

                                <code class="rounded bg-gray-100 px-1 py-0.5 text-gray-600">
                                    @{{xp}}
                                </code>.
                            </p>

                        </div>

                    </div>


                    {{-- ==================================================== --}}
                    {{-- MEDIA --}}
                    {{-- ==================================================== --}}

                    <div
                        x-data="broadcastMedia()"
                        class="overflow-hidden rounded-xl bg-white"
                    >

                        <div class="border-b border-gray-100 px-0 py-4">

                            <div class="flex items-center gap-3">

                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                                    <i class="ik ik-image text-base"></i>
                                </div>

                                <div>
                                    <h2 class="text-sm font-semibold text-gray-800">
                                        Media
                                    </h2>

                                    <p class="mt-0.5 text-xs text-gray-500">
                                        Attach an image, video, audio, or document.
                                    </p>
                                </div>

                            </div>

                        </div>


                        <div class="space-y-5 pt-5">


                            {{-- ================================================= --}}
                            {{-- UPLOAD AREA --}}
                            {{-- ================================================= --}}

                            <div>

                                <label class="block text-xs font-semibold text-gray-600">
                                    Upload Media
                                </label>


                                <div
                                    class="relative mt-2 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-8 text-center transition"
                                    :class="dragging
                                        ? 'border-primary-400 bg-primary-50'
                                        : 'hover:border-gray-300 hover:bg-gray-100'"
                                    @dragover.prevent="dragging = true"
                                    @dragleave.prevent="dragging = false"
                                    @drop.prevent="handleDrop($event)"
                                >


                                    {{-- EMPTY STATE --}}

                                    <template x-if="!file">

                                        <div>

                                            <button
                                                type="button"
                                                @click="$refs.mediaInput.click()"
                                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-white text-gray-400 shadow-sm ring-1 ring-gray-200 transition hover:text-primary-600 hover:ring-primary-200"
                                            >
                                                <i class="ik ik-upload-cloud text-xl"></i>
                                            </button>


                                            <p class="mt-4 text-sm font-semibold text-gray-700">
                                                Drop your media here
                                            </p>

                                            <p class="mt-1 text-xs text-gray-400">
                                                or click to browse from your computer
                                            </p>


                                            <button
                                                type="button"
                                                @click="$refs.mediaInput.click()"
                                                class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-primary-600"
                                            >
                                                <i class="ik ik-folder text-sm"></i>
                                                Choose File
                                            </button>


                                            <p class="mt-4 text-[11px] text-gray-400">
                                                Images, videos, audio, PDF and documents
                                            </p>

                                        </div>

                                    </template>


                                    {{-- FILE SELECTED --}}

                                    <template x-if="file">

                                        <div class="text-left">

                                            <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4">


                                                {{-- FILE ICON --}}

                                                <div
                                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600"
                                                >
                                                    <i
                                                        class="text-xl"
                                                        :class="fileIcon"
                                                    ></i>
                                                </div>


                                                {{-- FILE INFORMATION --}}

                                                <div class="min-w-0 flex-1">

                                                    <p
                                                        class="truncate text-sm font-semibold text-gray-700"
                                                        x-text="file.name"
                                                    ></p>

                                                    <div class="mt-1 flex items-center gap-2 text-xs text-gray-400">

                                                        <span x-text="fileType"></span>

                                                        <span>•</span>

                                                        <span x-text="fileSize"></span>

                                                    </div>

                                                </div>


                                                {{-- REMOVE --}}

                                                <button
                                                    type="button"
                                                    @click="removeFile()"
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-rose-200 bg-white text-rose-500 transition hover:bg-rose-50"
                                                    title="Remove media"
                                                >
                                                    <i class="ik ik-trash-2 text-sm"></i>
                                                </button>

                                            </div>


                                            {{-- CHANGE FILE --}}

                                            <div class="mt-3 flex items-center justify-between">

                                                <p class="text-xs text-gray-400">
                                                    This file will be attached to the broadcast.
                                                </p>

                                                <button
                                                    type="button"
                                                    @click="$refs.mediaInput.click()"
                                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-700"
                                                >
                                                    <i class="ik ik-refresh-cw text-xs"></i>
                                                    Change file
                                                </button>

                                            </div>

                                        </div>

                                    </template>


                                    {{-- ================================================= --}}
                                    {{-- IMPORTANT: ACTUAL FILE INPUT --}}
                                    {{-- ================================================= --}}

                                    <input
                                        x-ref="mediaInput"
                                        id="media"
                                        name="media"
                                        type="file"
                                        class="hidden"
                                        accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
                                        @change="handleFile($event)"
                                    >

                                </div>

                            </div>


                            {{-- ================================================= --}}
                            {{-- EXISTING MEDIA --}}
                            {{-- ================================================= --}}

                            @if($isEdit && $existingMediaPath)

                                <div class="rounded-xl border border-primary-100 bg-primary-50 p-4">

                                    <div class="flex items-center gap-3">

                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-primary-600 shadow-sm">
                                            <i class="ik ik-paperclip"></i>
                                        </div>

                                        <div class="min-w-0 flex-1">

                                            <p class="text-xs font-semibold text-primary-700">
                                                Current Media
                                            </p>

                                            <p class="mt-1 truncate text-xs text-primary-600/70">
                                                {{ $existingMediaPath }}
                                            </p>

                                        </div>

                                    </div>

                                    <p class="mt-3 text-[11px] text-primary-600/70">
                                        Upload a new file above to replace the current media.
                                    </p>

                                </div>

                            @endif


                            {{-- ================================================= --}}
                            {{-- OR --}}
                            {{-- ================================================= --}}

                            <div class="relative">

                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-100"></div>
                                </div>

                                <div class="relative flex justify-center">

                                    <span class="bg-white px-3 text-[11px] font-medium uppercase tracking-wider text-gray-400">
                                        or use a URL
                                    </span>

                                </div>

                            </div>


                            {{-- ================================================= --}}
                            {{-- MEDIA URL --}}
                            {{-- ================================================= --}}

                            <div>

                                <label
                                    for="media_url"
                                    class="block text-xs font-semibold text-gray-600"
                                >
                                    Media URL
                                </label>

                                <div class="relative mt-2">

                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                        <i class="ik ik-link text-sm text-gray-400"></i>
                                    </div>

                                    <input
                                        id="media_url"
                                        name="media_url"
                                        type="url"
                                        value="{{ old('media_url', $existingMediaUrl ?? '') }}"
                                        placeholder="https://example.com/media.jpg"
                                        class="block w-full rounded-lg border-gray-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-gray-700 shadow-sm transition focus:border-primary-500 focus:ring-primary-500/20"
                                    >

                                </div>

                                <p class="mt-1.5 text-xs text-gray-400">
                                    Use this if the media is already hosted online.
                                </p>

                            </div>


                            {{-- ================================================= --}}
                            {{-- MEDIA TYPE --}}
                            {{-- ================================================= --}}

                            <div>

                                <label
                                    for="media_type"
                                    class="block text-xs font-semibold text-gray-600"
                                >
                                    Media Type
                                </label>

                                <select
                                    id="media_type"
                                    name="media_type"
                                    class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-primary-500 focus:ring-primary-500/20"
                                >

                                    <option
                                        value=""
                                        @selected(!$existingMediaType)
                                    >
                                        Auto Detect
                                    </option>

                                    <option
                                        value="photo"
                                        @selected(old('media_type', $existingMediaType) === 'photo')
                                    >
                                        Image
                                    </option>

                                    <option
                                        value="video"
                                        @selected(old('media_type', $existingMediaType) === 'video')
                                    >
                                        Video
                                    </option>

                                    <option
                                        value="audio"
                                        @selected(old('media_type', $existingMediaType) === 'audio')
                                    >
                                        Audio
                                    </option>

                                    <option
                                        value="document"
                                        @selected(old('media_type', $existingMediaType) === 'document')
                                    >
                                        Document
                                    </option>

                                </select>

                                <p class="mt-1.5 text-xs text-gray-400">
                                    Auto Detect will determine the appropriate Telegram media type.
                                </p>

                            </div>


                            {{-- INFO --}}

                            <div class="flex items-start gap-3 rounded-lg bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-100">

                                <i class="ik ik-info mt-0.5 text-sm text-gray-400"></i>

                                <p class="text-xs leading-5 text-gray-500">
                                    You can either upload a file directly or provide a publicly accessible media URL.
                                    Leave both empty if this broadcast contains text only.
                                </p>

                            </div>

                        </div>

                    </div>


                    {{-- ==================================================== --}}
                    {{-- CHANNEL / TYPE --}}
                    {{-- ==================================================== --}}

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        {{-- CHANNEL --}}

                        <div>

                            <label
                                for="channel"
                                class="block text-xs font-semibold text-gray-600"
                            >
                                Delivery Channel
                            </label>

                            <select
                                id="channel"
                                name="channel"
                                class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-primary-500 focus:ring-primary-500/20"
                            >

                                <option
                                    value="telegram"
                                    @selected($oldChannel === 'telegram')
                                >
                                    Telegram
                                </option>

                            </select>

                        </div>


                        {{-- TYPE --}}

                        <div>

                            <label
                                for="type"
                                class="block text-xs font-semibold text-gray-600"
                            >
                                Broadcast Type
                            </label>

                            <select
                                id="type"
                                name="type"
                                class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-primary-500 focus:ring-primary-500/20"
                            >

                                <option
                                    value="manual"
                                    @selected($oldType === 'manual')
                                >
                                    Manual
                                </option>

                                <option
                                    value="automation"
                                    @selected($oldType === 'automation')
                                >
                                    Automation
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ==================================================== --}}
            {{-- TELEGRAM BUTTONS --}}
            {{-- ==================================================== --}}

            <div
                x-data="telegramButtons()"
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100"
            >

                <div class="border-b border-gray-100 px-5 py-4">

                    <div class="flex items-center justify-between gap-4">

                        <div class="flex items-center gap-3">

                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                                <i class="ik ik-mouse-pointer text-base"></i>
                            </div>

                            <div>

                                <h2 class="text-sm font-semibold text-gray-800">
                                    Telegram Buttons
                                </h2>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    Add actions players can tap below your message.
                                </p>

                            </div>

                        </div>


                        <button
                            type="button"
                            @click="addButton()"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-primary-600"
                        >
                            <i class="ik ik-plus text-sm"></i>
                            Add Button
                        </button>

                    </div>

                </div>


                <div class="space-y-4 p-5">

                    <div class="space-y-3">

                        <template
                            x-for="(button, index) in buttons"
                            :key="index"
                        >

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-12">


                                    {{-- TEXT --}}

                                    <div class="md:col-span-4">

                                        <label class="block text-xs font-semibold text-gray-600">
                                            Button Text
                                        </label>

                                        <input
                                            type="text"
                                            :name="`buttons[${index}][text]`"
                                            x-model="button.text"
                                            placeholder="Play Now"
                                            maxlength="64"
                                            class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                                        >

                                    </div>


                                    {{-- TYPE --}}

                                    <div class="md:col-span-3">

                                        <label class="block text-xs font-semibold text-gray-600">
                                            Action
                                        </label>

                                        <select
                                            :name="`buttons[${index}][type]`"
                                            x-model="button.type"
                                            class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                                        >

                                            <option value="url">
                                                Open URL
                                            </option>

                                            <option value="web_app">
                                                Open Web App
                                            </option>

                                        </select>

                                    </div>


                                    {{-- URL --}}

                                    <div class="md:col-span-4">

                                        <label class="block text-xs font-semibold text-gray-600">

                                            <span x-show="button.type === 'url'">
                                                URL
                                            </span>

                                            <span x-show="button.type === 'web_app'">
                                                Web App URL
                                            </span>

                                        </label>

                                        <input
                                            type="url"
                                            :name="`buttons[${index}][url]`"
                                            x-model="button.url"
                                            :placeholder="button.type === 'web_app'
                                                ? 'https://your-domain.com'
                                                : 'https://example.com'"
                                            class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                                        >

                                    </div>


                                    {{-- REMOVE --}}

                                    <div class="flex items-end justify-end md:col-span-1">

                                        <button
                                            type="button"
                                            @click="removeButton(index)"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-rose-200 bg-white text-rose-500 transition hover:bg-rose-50"
                                            title="Remove button"
                                        >
                                            <i class="ik ik-trash-2 text-base"></i>
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </template>


                        {{-- EMPTY STATE --}}

                        <div
                            x-show="buttons.length === 0"
                            x-cloak
                            class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-5 py-8 text-center"
                        >

                            <i class="ik ik-mouse-pointer text-xl text-gray-300"></i>

                            <p class="mt-2 text-xs font-medium text-gray-500">
                                No buttons added
                            </p>

                            <p class="mt-1 text-xs text-gray-400">
                                Add a button to give players an action.
                            </p>

                        </div>

                    </div>


                    {{-- TELEGRAM PREVIEW --}}

                    <div
                        x-show="buttons.length > 0"
                        x-cloak
                        x-transition
                        class="rounded-xl bg-gray-900 p-5"
                    >

                        <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Telegram Preview
                        </div>

                        <div class="rounded-lg bg-white p-4">

                            <div
                                class="whitespace-pre-wrap text-sm text-gray-700"
                                x-text="message || 'Your message will appear here...'"
                            ></div>

                            <div class="mt-4 space-y-2">

                                <template
                                    x-for="(button, index) in buttons"
                                    :key="index"
                                >

                                    <div
                                        x-show="button.text"
                                        class="rounded-lg bg-primary-50 px-4 py-2.5 text-center text-sm font-semibold text-primary-600"
                                    >
                                        <span x-text="button.text"></span>
                                    </div>

                                </template>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================== --}}
        {{-- RIGHT COLUMN --}}
        {{-- ======================================================== --}}

        <div class="space-y-6 xl:col-span-5">


            {{-- ==================================================== --}}
            {{-- AUDIENCE --}}
            {{-- ==================================================== --}}

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

                <div class="border-b border-gray-100 px-5 py-4">

                    <div class="flex items-center gap-3">

                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <i class="ik ik-users text-base"></i>
                        </div>

                        <div>

                            <h2 class="text-sm font-semibold text-gray-800">
                                Audience
                            </h2>

                            <p class="mt-0.5 text-xs text-gray-500">
                                Choose which players should receive this broadcast.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="space-y-5 p-5">


                    {{-- AUDIENCE TYPE --}}

                    <div>

                        <label
                            for="audience_type"
                            class="block text-xs font-semibold text-gray-600"
                        >
                            Target Audience
                        </label>

                        <select
                            id="audience_type"
                            name="audience_type"
                            x-model="audienceType"
                            @change="previewAudience()"
                            class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700 shadow-sm transition focus:border-primary-500 focus:ring-primary-500/20"
                        >

                            <option value="all">
                                All Players
                            </option>

                            <option value="active">
                                Active Players
                            </option>

                            <option value="inactive">
                                Inactive Players
                            </option>

                            <option value="filtered">
                                Filtered Players
                            </option>

                        </select>

                    </div>


                    {{-- FILTERS --}}

                    <div
                        x-show="audienceType === 'filtered'"
                        x-cloak
                        x-transition
                        class="space-y-5 rounded-xl bg-gray-50 p-5 ring-1 ring-inset ring-gray-100"
                    >

                        <div>

                            <h3 class="text-sm font-semibold text-gray-800">
                                Player Filters
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Combine filters to create a specific player audience.
                            </p>

                        </div>


                        {{-- XP --}}

                        <div>

                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Experience
                            </div>

                            <div class="grid grid-cols-1 gap-4">

                                <div>

                                    <label class="block text-xs font-semibold text-gray-600">
                                        Minimum XP
                                    </label>

                                    <input
                                        type="number"
                                        name="filters[total_xp_min]"
                                        value="{{ old('filters.total_xp_min', $existingFilters['total_xp_min'] ?? '') }}"
                                        @input.debounce.500ms="previewAudience()"
                                        min="0"
                                        placeholder="0"
                                        class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                                    >

                                </div>

                                <div>

                                    <label class="block text-xs font-semibold text-gray-600">
                                        Maximum XP
                                    </label>

                                    <input
                                        type="number"
                                        name="filters[total_xp_max]"
                                        value="{{ old('filters.total_xp_max', $existingFilters['total_xp_max'] ?? '') }}"
                                        @input.debounce.500ms="previewAudience()"
                                        min="0"
                                        placeholder="No limit"
                                        class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                                    >

                                </div>

                            </div>

                        </div>


                        {{-- SR --}}

                        <div>

                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Skill Rating
                            </div>

                            <div class="grid grid-cols-1 gap-4">

                                <div>

                                    <label class="block text-xs font-semibold text-gray-600">
                                        Minimum SR
                                    </label>

                                    <input
                                        type="number"
                                        name="filters[current_sr_min]"
                                        value="{{ old('filters.current_sr_min', $existingFilters['current_sr_min'] ?? '') }}"
                                        @input.debounce.500ms="previewAudience()"
                                        min="0"
                                        placeholder="0"
                                        class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                                    >

                                </div>

                                <div>

                                    <label class="block text-xs font-semibold text-gray-600">
                                        Maximum SR
                                    </label>

                                    <input
                                        type="number"
                                        name="filters[current_sr_max]"
                                        value="{{ old('filters.current_sr_max', $existingFilters['current_sr_max'] ?? '') }}"
                                        @input.debounce.500ms="previewAudience()"
                                        min="0"
                                        placeholder="No limit"
                                        class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                                    >

                                </div>

                            </div>

                        </div>


                        {{-- STREAK --}}

                        <div>

                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Streak
                            </div>

                            <div class="grid grid-cols-1 gap-4">

                                <div>

                                    <label class="block text-xs font-semibold text-gray-600">
                                        Minimum Streak
                                    </label>

                                    <input
                                        type="number"
                                        name="filters[current_streak_min]"
                                        value="{{ old('filters.current_streak_min', $existingFilters['current_streak_min'] ?? '') }}"
                                        @input.debounce.500ms="previewAudience()"
                                        min="0"
                                        placeholder="0"
                                        class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                                    >

                                </div>

                                <div>

                                    <label class="block text-xs font-semibold text-gray-600">
                                        Maximum Streak
                                    </label>

                                    <input
                                        type="number"
                                        name="filters[current_streak_max]"
                                        value="{{ old('filters.current_streak_max', $existingFilters['current_streak_max'] ?? '') }}"
                                        @input.debounce.500ms="previewAudience()"
                                        min="0"
                                        placeholder="No limit"
                                        class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                                    >

                                </div>

                            </div>

                        </div>


                        {{-- LANGUAGE --}}

                        <div>

                            <label
                                for="language"
                                class="block text-xs font-semibold text-gray-600"
                            >
                                Language
                            </label>

                            <select
                                id="language"
                                name="filters[language]"
                                @change="previewAudience()"
                                class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                            >

                                <option value="">
                                    All Languages
                                </option>

                                <option
                                    value="am"
                                    @selected(old('filters.language', $existingFilters['language'] ?? '') === 'am')
                                >
                                    Amharic
                                </option>

                                <option
                                    value="en"
                                    @selected(old('filters.language', $existingFilters['language'] ?? '') === 'en')
                                >
                                    English
                                </option>

                            </select>

                        </div>


                        {{-- ONBOARDING --}}

                        <div>

                            <label
                                for="has_onboarded"
                                class="block text-xs font-semibold text-gray-600"
                            >
                                Onboarding Status
                            </label>

                            @php
                                $onboarded = old(
                                    'filters.has_onboarded',
                                    $existingFilters['has_onboarded'] ?? ''
                                );

                                if (is_bool($onboarded)) {
                                    $onboarded = $onboarded ? '1' : '0';
                                }
                            @endphp

                            <select
                                id="has_onboarded"
                                name="filters[has_onboarded]"
                                @change="previewAudience()"
                                class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                            >

                                <option value="">
                                    All Players
                                </option>

                                <option
                                    value="1"
                                    @selected($onboarded === '1')
                                >
                                    Completed Onboarding
                                </option>

                                <option
                                    value="0"
                                    @selected($onboarded === '0')
                                >
                                    Not Completed
                                </option>

                            </select>

                        </div>

                    </div>


                    {{-- AUDIENCE PREVIEW --}}

                    <div class="rounded-xl bg-primary-50 p-5 ring-1 ring-inset ring-primary-600/10">

                        <div class="flex items-center justify-between gap-4">

                            <div class="flex items-center gap-3">

                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600">
                                    <i class="ik ik-users text-lg"></i>
                                </div>

                                <div>

                                    <p class="text-xs font-semibold uppercase tracking-wide text-primary-700">
                                        Estimated Recipients
                                    </p>

                                    <p class="mt-0.5 text-xs text-primary-600/70">
                                        Players matching your audience.
                                    </p>

                                </div>

                            </div>


                            <div class="text-right text-2xl font-bold text-indigo-700">

                                <span
                                    x-show="loadingPreview"
                                    x-cloak
                                    class="inline-flex items-center gap-2 text-base"
                                >
                                    <i class="ik ik-loader animate-spin"></i>
                                </span>

                                <span
                                    x-show="!loadingPreview"
                                    x-text="recipientCount"
                                ></span>

                            </div>

                        </div>


                        <div
                            x-show="previewError"
                            x-cloak
                            class="mt-4 rounded-lg bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10"
                        >
                            <span x-text="previewError"></span>
                        </div>

                    </div>

                </div>

            </div>


            {{-- ==================================================== --}}
            {{-- DELIVERY --}}
            {{-- ==================================================== --}}

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

                <div class="border-b border-gray-100 px-5 py-4">

                    <div class="flex items-center gap-3">

                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <i class="ik ik-clock text-base"></i>
                        </div>

                        <div>

                            <h2 class="text-sm font-semibold text-gray-800">
                                Delivery
                            </h2>

                            <p class="mt-0.5 text-xs text-gray-500">
                                Choose when this broadcast should be sent.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="p-5">

                    <label
                        for="scheduled_at"
                        class="block text-xs font-semibold text-gray-600"
                    >
                        Schedule Send
                    </label>

                    <input
                        id="scheduled_at"
                        name="scheduled_at"
                        type="datetime-local"
                        value="{{ $oldScheduledAt }}"
                        class="mt-2 block w-full rounded-lg border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500/20"
                    >

                    <p class="mt-1.5 text-xs text-gray-400">
                        Leave empty to save this broadcast as a draft.
                    </p>

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================ --}}
    {{-- ACTIONS --}}
    {{-- ============================================================ --}}

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">

        <a
            href="{{ route('admin.broadcasts.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-600 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-800"
        >
            Cancel
        </a>


        <button
            type="submit"
            :disabled="submitting"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:opacity-60"
        >

            <template x-if="!submitting">

                <span class="inline-flex items-center gap-2">

                    <i class="ik ik-save text-base"></i>

                    {{ $isEdit ? 'Update Broadcast' : 'Save Broadcast' }}

                </span>

            </template>


            <template x-if="submitting">

                <span class="inline-flex items-center gap-2">

                    <i class="ik ik-loader animate-spin text-base"></i>

                    Saving...

                </span>

            </template>

        </button>

    </div>

</form>


{{-- ================================================================ --}}
{{-- ALPINE --}}
{{-- ================================================================ --}}

@once

    @push('script')

        <script>

            document.addEventListener('alpine:init', () => {


                /* =====================================================
                 * BROADCAST FORM
                 * ===================================================== */

                Alpine.data('broadcastForm', () => ({

                    message: @js(
                        old(
                            'message',
                            $isEdit ? $broadcast->message : ''
                        )
                    ),

                    audienceType: @js($oldAudienceType),

                    recipientCount: 0,

                    loadingPreview: false,

                    previewError: '',

                    previewTimer: null,

                    submitting: false,


                    init() {

                        this.previewAudience();

                    },


                    previewAudience() {

                        clearTimeout(this.previewTimer);

                        this.previewTimer = setTimeout(() => {

                            this.loadAudiencePreview();

                        }, 300);

                    },


                    async loadAudiencePreview() {

                        this.loadingPreview = true;

                        this.previewError = '';


                        try {

                            const form = this.$root;

                            const formData = new FormData(form);


                            const data = {

                                audience_type:
                                    formData.get('audience_type'),

                                filters: {

                                    total_xp_min:
                                        formData.get(
                                            'filters[total_xp_min]'
                                        ),

                                    total_xp_max:
                                        formData.get(
                                            'filters[total_xp_max]'
                                        ),

                                    current_sr_min:
                                        formData.get(
                                            'filters[current_sr_min]'
                                        ),

                                    current_sr_max:
                                        formData.get(
                                            'filters[current_sr_max]'
                                        ),

                                    current_streak_min:
                                        formData.get(
                                            'filters[current_streak_min]'
                                        ),

                                    current_streak_max:
                                        formData.get(
                                            'filters[current_streak_max]'
                                        ),

                                    language:
                                        formData.get(
                                            'filters[language]'
                                        ),

                                    has_onboarded:
                                        formData.get(
                                            'filters[has_onboarded]'
                                        )

                                }

                            };


                            const csrfToken =
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    ?.getAttribute('content') || '';


                            const response = await fetch(

                                @js(
                                    route(
                                        'admin.broadcasts.audience-preview'
                                    )
                                ),

                                {

                                    method: 'POST',

                                    headers: {

                                        'Content-Type':
                                            'application/json',

                                        'Accept':
                                            'application/json',

                                        'X-CSRF-TOKEN':
                                            csrfToken

                                    },

                                    body:
                                        JSON.stringify(data)

                                }

                            );


                            if (!response.ok) {

                                throw new Error(
                                    `Audience preview failed with status ${response.status}`
                                );

                            }


                            const result =
                                await response.json();


                            if (!result.success) {

                                throw new Error(
                                    'Audience preview failed.'
                                );

                            }


                            this.recipientCount =
                                Number(result.count) || 0;


                        } catch (error) {

                            console.error(
                                'Failed to load audience preview:',
                                error
                            );

                            this.recipientCount = 0;

                            this.previewError =
                                'Unable to calculate audience size.';

                        } finally {

                            this.loadingPreview = false;

                        }

                    }

                }));



                /* =====================================================
                 * BROADCAST MEDIA
                 * ===================================================== */

                Alpine.data('broadcastMedia', () => ({

                    file: null,

                    dragging: false,


                    get fileSize() {

                        if (!this.file) {
                            return '';
                        }

                        const bytes = this.file.size;


                        if (bytes < 1024) {

                            return bytes + ' B';

                        }


                        if (bytes < 1024 * 1024) {

                            return (
                                bytes / 1024
                            ).toFixed(1) + ' KB';

                        }


                        if (bytes < 1024 * 1024 * 1024) {

                            return (
                                bytes /
                                (1024 * 1024)
                            ).toFixed(1) + ' MB';

                        }


                        return (
                            bytes /
                            (1024 * 1024 * 1024)
                        ).toFixed(1) + ' GB';

                    },


                    get fileType() {

                        if (!this.file) {
                            return '';
                        }


                        if (
                            this.file.type.startsWith(
                                'image/'
                            )
                        ) {

                            return 'Image';

                        }


                        if (
                            this.file.type.startsWith(
                                'video/'
                            )
                        ) {

                            return 'Video';

                        }


                        if (
                            this.file.type.startsWith(
                                'audio/'
                            )
                        ) {

                            return 'Audio';

                        }


                        return 'Document';

                    },


                    get fileIcon() {

                        if (!this.file) {

                            return 'ik-file';

                        }


                        if (
                            this.file.type.startsWith(
                                'image/'
                            )
                        ) {

                            return 'ik-image';

                        }


                        if (
                            this.file.type.startsWith(
                                'video/'
                            )
                        ) {

                            return 'ik-video';

                        }


                        if (
                            this.file.type.startsWith(
                                'audio/'
                            )
                        ) {

                            return 'ik-music';

                        }


                        if (
                            this.file.type ===
                            'application/pdf'
                        ) {

                            return 'ik-file-text';

                        }


                        return 'ik-file';

                    },


                    /* =============================================
                     * NORMAL FILE SELECT
                     * ============================================= */

                    handleFile(event) {

                        const selectedFile =
                            event.target.files?.[0];


                        if (!selectedFile) {

                            return;

                        }


                        this.file =
                            selectedFile;


                        this.dragging = false;

                    },


                    /* =============================================
                     * DRAG AND DROP
                     * ============================================= */

                    handleDrop(event) {

                        this.dragging = false;


                        const files =
                            event.dataTransfer.files;


                        if (
                            !files ||
                            files.length === 0
                        ) {

                            return;

                        }


                        const droppedFile =
                            files[0];


                        this.file =
                            droppedFile;


                        /*
                         * Important:
                         *
                         * A dropped file is not automatically
                         * submitted by the form.
                         *
                         * We explicitly place it into the
                         * real file input.
                         */

                        const dataTransfer =
                            new DataTransfer();


                        dataTransfer.items.add(
                            droppedFile
                        );


                        this.$refs.mediaInput.files =
                            dataTransfer.files;

                    },


                    /* =============================================
                     * REMOVE FILE
                     * ============================================= */

                    removeFile() {

                        this.file = null;

                        this.$refs.mediaInput.value = '';

                    }

                }));



                /* =====================================================
                 * TELEGRAM BUTTONS
                 * ===================================================== */

                Alpine.data('telegramButtons', () => ({

                    buttons: @js($oldButtons),


                    addButton() {

                        if (
                            this.buttons.length >= 10
                        ) {

                            return;

                        }


                        this.buttons.push({

                            text: '',

                            type: 'url',

                            url: ''

                        });

                    },


                    removeButton(index) {

                        this.buttons.splice(
                            index,
                            1
                        );

                    }

                }));

            });

        </script>

    @endpush

@endonce