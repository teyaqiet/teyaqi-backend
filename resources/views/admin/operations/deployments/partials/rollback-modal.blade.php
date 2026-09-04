<div
    x-show="showRollbackModal"
    x-cloak
    class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 p-4"
    x-transition.opacity
>
    <div
        class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        @click.outside="closeRollbackModal()"
        x-transition
    >

        {{-- Header --}}
        <div class="shrink-0 border-b border-gray-100 bg-white p-6">
            <div class="flex items-start justify-between gap-4">

                <div class="flex items-start gap-4">

                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50">
                        <svg
                            class="h-5 w-5 text-amber-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 15 4 10m0 0 5-5m-5 5h11a5 5 0 0 1 5 5v1"
                            />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Rollback Preview
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Review the changes before restoring the previous version.
                        </p>
                    </div>

                </div>

                <button
                    type="button"
                    @click="closeRollbackModal()"
                    class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    aria-label="Close"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 6l12 12M18 6 6 18"
                        />
                    </svg>
                </button>

            </div>
        </div>

        {{-- Scrollable Content --}}
        <div class="min-h-0 flex-1 overflow-y-auto p-6">

            {{-- Loading --}}
            <template x-if="rollbackLoading">

                <div class="flex min-h-[360px] items-center justify-center">

                    <div class="text-center">

                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                            <svg
                                class="h-6 w-6 animate-spin text-gray-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M8.757 15.243l-2.121 2.121m0-11.728 2.121 2.121m8.486 8.486 2.121 2.121"
                                />
                            </svg>
                        </div>

                        <p class="mt-4 text-sm font-medium text-gray-700">
                            Preparing rollback preview...
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            Checking Git history and changed files.
                        </p>

                    </div>

                </div>

            </template>

            {{-- Preview --}}
            <template x-if="!rollbackLoading && rollbackPreview">

                <div class="space-y-6">

                    {{-- Warning --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">

                        <div class="flex items-start gap-3">

                            <svg
                                class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3m0 4h.01M10.29 3.86 2.82 17a2 2 0 0 0 1.74 3h14.88a2 2 0 0 0 1.74-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
                                />
                            </svg>

                            <div>
                                <p class="text-sm font-semibold text-amber-900">
                                    This action changes the application version
                                </p>

                                <p class="mt-1 text-xs leading-5 text-amber-800">
                                    The application will be reset to the target Git commit and the deployment pipeline will run again.
                                </p>
                            </div>

                        </div>

                    </div>

                    {{-- Current → Target --}}
                    <div>

                        <h3 class="text-sm font-semibold text-gray-900">
                            Version Change
                        </h3>

                        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-[1fr_auto_1fr] md:items-stretch">

                            {{-- Current --}}
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">

                                <div class="flex items-center justify-between gap-3">

                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                        Current
                                    </p>

                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-[10px] font-semibold text-gray-600">
                                        Will be removed
                                    </span>

                                </div>

                                <code
                                    class="mt-3 block break-all text-sm font-semibold text-gray-900"
                                    x-text="rollbackPreview.current_commit || '—'"
                                ></code>

                                <p
                                    class="mt-2 text-xs leading-5 text-gray-500"
                                    x-text="rollbackPreview.current_commit_message || 'No commit message'"
                                ></p>

                            </div>

                            {{-- Arrow --}}
                            <div class="flex items-center justify-center">

                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-500">

                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.8"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5 12h14m-6-6 6 6-6 6"
                                        />
                                    </svg>

                                </div>

                            </div>

                            {{-- Target --}}
                            <div class="rounded-xl border border-green-200 bg-green-50/40 p-4">

                                <div class="flex items-center justify-between gap-3">

                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-green-600">
                                        Target
                                    </p>

                                    <span class="rounded-full bg-green-100 px-2 py-1 text-[10px] font-semibold text-green-700">
                                        Will be restored
                                    </span>

                                </div>

                                <code
                                    class="mt-3 block break-all text-sm font-semibold text-gray-900"
                                    x-text="rollbackPreview.target_commit || '—'"
                                ></code>

                                <p
                                    class="mt-2 text-xs leading-5 text-gray-500"
                                    x-text="rollbackPreview.target_commit_message || 'No commit message'"
                                ></p>

                            </div>

                        </div>

                    </div>

                    {{-- Deployment Information --}}
                    <div>

                        <h3 class="text-sm font-semibold text-gray-900">
                            Deployment
                        </h3>

                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">

                            {{-- Deployment --}}
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">

                                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                    Deployment
                                </p>

                                <p
                                    class="mt-2 text-sm font-semibold text-gray-900"
                                    x-text="`#${rollbackPreview.deployment_id || '—'}`"
                                ></p>

                            </div>

                            {{-- Environment --}}
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">

                                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                    Environment
                                </p>

                                <p
                                    class="mt-2 text-sm font-semibold capitalize text-gray-900"
                                    x-text="rollbackPreview.environment || 'staging'"
                                ></p>

                            </div>

                            {{-- Branch --}}
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">

                                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                    Branch
                                </p>

                                <code
                                    class="mt-2 block truncate text-sm font-semibold text-gray-900"
                                    x-text="rollbackPreview.branch || 'main'"
                                ></code>

                            </div>

                        </div>

                    </div>

                    {{-- Original Target Deployment --}}
                    <div x-show="rollbackPreview.target_deployment_id">

                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">

                            <div class="flex items-center justify-between gap-3">

                                <div>
                                    <p class="text-xs font-medium text-gray-500">
                                        Target originally deployed by
                                    </p>

                                    <p class="mt-1 text-sm font-semibold text-gray-900">
                                        Deployment
                                        <span
                                            x-text="`#${rollbackPreview.target_deployment_id || '—'}`"
                                        ></span>
                                    </p>
                                </div>

                                <span class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                    Previous version
                                </span>

                            </div>

                            <p
                                class="mt-3 text-xs text-gray-500"
                                x-show="rollbackPreview.target_created_at"
                                x-text="rollbackPreview.target_created_at ? `Created ${formatDate(rollbackPreview.target_created_at)}` : ''"
                            ></p>

                        </div>

                    </div>

                    {{-- Changed Files --}}
                    <div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Changes Being Removed
                                </h3>

                                <p class="mt-1 text-xs text-gray-500">
                                    Changes between the rollback target and the current version.
                                </p>
                            </div>

                            <div
                                class="flex flex-wrap items-center gap-2"
                                x-show="rollbackPreview.changed_files"
                            >

                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                    <span
                                        x-text="rollbackPreview.changed_files?.total ?? 0"
                                    ></span>

                                    <span class="ml-1">
                                        files
                                    </span>
                                </span>

                                <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                    +
                                    <span
                                        class="ml-0.5"
                                        x-text="rollbackPreview.changed_files?.additions ?? 0"
                                    ></span>
                                </span>

                                <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                    −
                                    <span
                                        class="ml-0.5"
                                        x-text="rollbackPreview.changed_files?.deletions ?? 0"
                                    ></span>
                                </span>

                            </div>

                        </div>

                        {{-- Files --}}
                        <div class="mt-3 overflow-hidden rounded-lg border border-gray-200">

                            <div class="divide-y divide-gray-100">

                                <template
                                    x-for="(file, index) in (rollbackPreview.changed_files?.files || [])"
                                    :key="index"
                                >

                                    <div class="px-4 py-3 transition hover:bg-gray-50">

                                        <div class="flex items-start gap-3">

                                            {{-- Status --}}
                                            <div
                                                class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md"
                                                :class="{
                                                    'bg-green-50 text-green-600': file.status === 'added',
                                                    'bg-blue-50 text-blue-600': file.status === 'modified',
                                                    'bg-red-50 text-red-600': file.status === 'deleted',
                                                    'bg-purple-50 text-purple-600': file.status === 'renamed',
                                                    'bg-indigo-50 text-indigo-600': file.status === 'copied'
                                                }"
                                            >

                                                {{-- Added --}}
                                                <svg
                                                    x-show="file.status === 'added'"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="2"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M12 5v14m-7-7h14"
                                                    />
                                                </svg>

                                                {{-- Modified --}}
                                                <svg
                                                    x-show="file.status === 'modified'"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="2"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M16.862 4.487 19.5 7.125m-2.638-2.638a2.25 2.25 0 0 1 3.182 3.182L8.25 19.563 4.5 20.25l.688-3.75L16.862 4.487Z"
                                                    />
                                                </svg>

                                                {{-- Deleted --}}
                                                <svg
                                                    x-show="file.status === 'deleted'"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="2"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M6 6l12 12M18 6 6 18"
                                                    />
                                                </svg>

                                                {{-- Renamed --}}
                                                <svg
                                                    x-show="file.status === 'renamed'"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="2"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M7.5 7.5h9m0 0-3-3m3 3-3 3M16.5 16.5h-9m0 0 3-3m-3 3 3 3"
                                                    />
                                                </svg>

                                                {{-- Copied --}}
                                                <svg
                                                    x-show="file.status === 'copied'"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="2"
                                                    stroke="currentColor"
                                                >
                                                    <rect
                                                        x="8"
                                                        y="8"
                                                        width="11"
                                                        height="11"
                                                        rx="2"
                                                    />
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"
                                                    />
                                                </svg>

                                            </div>

                                            {{-- File --}}
                                            <div class="min-w-0 flex-1">

                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                                                    <code
                                                        class="break-all text-xs font-medium text-gray-800"
                                                        x-text="file.path || 'Unknown file'"
                                                    ></code>

                                                    <span
                                                        class="inline-flex w-fit shrink-0 rounded-full px-2 py-1 text-[11px] font-semibold"
                                                        :class="{
                                                            'bg-green-50 text-green-700': file.status === 'added',
                                                            'bg-blue-50 text-blue-700': file.status === 'modified',
                                                            'bg-red-50 text-red-700': file.status === 'deleted',
                                                            'bg-purple-50 text-purple-700': file.status === 'renamed',
                                                            'bg-indigo-50 text-indigo-700': file.status === 'copied'
                                                        }"
                                                        x-text="formatStatus(file.status)"
                                                    ></span>

                                                </div>

                                                <template
                                                    x-if="file.status === 'renamed' && file.old_path"
                                                >
                                                    <p class="mt-1 text-[11px] text-gray-400">
                                                        from
                                                        <code
                                                            class="ml-1 break-all"
                                                            x-text="file.old_path"
                                                        ></code>
                                                    </p>
                                                </template>

                                                <div class="mt-2 flex items-center gap-3 text-[11px]">

                                                    <span
                                                        class="font-medium text-green-600"
                                                        x-show="Number(file.additions || 0) > 0"
                                                    >
                                                        +
                                                        <span
                                                            x-text="file.additions || 0"
                                                        ></span>
                                                        additions
                                                    </span>

                                                    <span
                                                        class="font-medium text-red-600"
                                                        x-show="Number(file.deletions || 0) > 0"
                                                    >
                                                        −
                                                        <span
                                                            x-text="file.deletions || 0"
                                                        ></span>
                                                        deletions
                                                    </span>

                                                    <span
                                                        class="text-gray-400"
                                                        x-show="Number(file.additions || 0) === 0 && Number(file.deletions || 0) === 0"
                                                    >
                                                        No line statistics available
                                                    </span>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </template>

                                {{-- No files --}}
                                <template
                                    x-if="(rollbackPreview.changed_files?.files || []).length === 0"
                                >
                                    <div class="p-6 text-center">

                                        <p class="text-sm text-gray-500">
                                            No file changes were detected.
                                        </p>

                                    </div>
                                </template>

                            </div>

                        </div>

                    </div>

                    {{-- Lock Warning --}}
                    <div
                        x-show="rollbackPreview.locked"
                        class="rounded-xl border border-red-200 bg-red-50 p-4"
                    >

                        <div class="flex items-start gap-3">

                            <svg
                                class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3m0 4h.01M10.29 3.86 2.82 17a2 2 0 0 0 1.74 3h14.88a2 2 0 0 0 1.74-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
                                />
                            </svg>

                            <div>

                                <p class="text-sm font-semibold text-red-900">
                                    Another operation is running
                                </p>

                                <p
                                    class="mt-1 text-xs leading-5 text-red-800"
                                    x-text="rollbackPreview.lock?.deployment_id
                                        ? `Deployment #${rollbackPreview.lock.deployment_id} is currently running.`
                                        : 'A deployment operation is currently running.'"
                                ></p>

                            </div>

                        </div>

                    </div>

                </div>

            </template>

        </div>

        {{-- Footer --}}
        <div class="shrink-0 border-t border-gray-100 bg-white p-5">

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">

                <p class="text-xs text-gray-400">
                    Rollback creates a new operation record. The original deployment will not be modified.
                </p>

                <div class="flex items-center justify-end gap-3">

                    <button
                        type="button"
                        @click="closeRollbackModal()"
                        :disabled="rollbackCreating"
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="confirmRollback()"
                        :disabled="
                            rollbackLoading ||
                            rollbackCreating ||
                            !rollbackCanProceed()
                        "
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >

                        <svg
                            x-show="rollbackCreating"
                            class="h-4 w-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M8.757 15.243l-2.121 2.121m0-11.728 2.121 2.121m8.486 8.486 2.121 2.121"
                            />
                        </svg>

                        <svg
                            x-show="!rollbackCreating"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 15 4 10m0 0 5-5m-5 5h11a5 5 0 0 1 5 5v1"
                            />
                        </svg>

                        <span
                            x-text="rollbackCreating ? 'Starting Rollback...' : 'Rollback'"
                        ></span>

                    </button>

                </div>

            </div>

        </div>

    </div>
</div>
