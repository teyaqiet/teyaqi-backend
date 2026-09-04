<div
    x-show="showDetailsModal"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
    x-transition.opacity
>
    <div
        class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        @click.outside="closeDetailsModal()"
        x-transition
    >

        {{-- Header --}}
        <div class="shrink-0 border-b border-gray-100 bg-white p-6">
            <div class="flex items-start justify-between gap-4">

                <div class="flex items-start gap-4">

                    {{-- Deployment icon --}}
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-100">
                        <svg
                            class="h-5 w-5 text-gray-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6.75 3.75h7.19L18.75 8.56v11.69a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V4.5a.75.75 0 0 1 .75-.75Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M13.5 3.75v5.25h5.25M8.5 13h7M8.5 16h5"
                            />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Deployment Details
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            <template x-if="selectedDeployment">
                                <span>
                                    Deployment #
                                    <span
                                        class="font-medium text-gray-700"
                                        x-text="selectedDeployment.id"
                                    ></span>
                                </span>
                            </template>

                            <template x-if="!selectedDeployment">
                                <span>Loading deployment...</span>
                            </template>
                        </p>
                    </div>

                </div>

                {{-- Close --}}
                <button
                    type="button"
                    @click="closeDetailsModal()"
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
            <template x-if="!selectedDeployment">

                <div class="flex min-h-[300px] items-center justify-center">
                    <div class="flex items-center gap-2 text-sm text-gray-500">

                        <svg
                            class="h-5 w-5 animate-spin"
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

                        Loading deployment details...
                    </div>
                </div>

            </template>

            {{-- Details --}}
            <template x-if="selectedDeployment">

                <div class="space-y-6">

                    {{-- Summary --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                        {{-- Status --}}
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <p class="text-xs text-gray-500">
                                Status
                            </p>

                            <span
                                class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="deploymentStatusClass(selectedDeployment.status)"
                                x-text="formatStatus(selectedDeployment.status)"
                            ></span>
                        </div>

                        {{-- Environment --}}
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <p class="text-xs text-gray-500">
                                Environment
                            </p>

                            <p
                                class="mt-2 text-sm font-semibold text-gray-900"
                                x-text="selectedDeployment.environment || 'staging'"
                            ></p>
                        </div>

                        {{-- Branch --}}
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <p class="text-xs text-gray-500">
                                Branch
                            </p>

                            <code
                                class="mt-2 block text-sm font-semibold text-gray-900"
                                x-text="selectedDeployment.branch || 'main'"
                            ></code>
                        </div>

                        {{-- Duration --}}
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <p class="text-xs text-gray-500">
                                Duration
                            </p>

                            <p
                                class="mt-2 text-sm font-semibold text-gray-900"
                                x-text="formatDuration(selectedDeployment.duration_seconds)"
                            ></p>
                        </div>

                    </div>

                    {{-- Commit --}}
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Commit
                        </h3>

                        <div class="mt-3 rounded-lg border border-gray-100 bg-gray-50 p-4">

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                                <div class="min-w-0">
                                    <p class="text-xs text-gray-500">
                                        Commit Hash
                                    </p>

                                    <code
                                        class="mt-1 block break-all text-sm font-medium text-gray-900"
                                        x-text="selectedDeployment.commit_hash || '—'"
                                    ></code>
                                </div>

                                <div class="sm:max-w-[50%] sm:text-right">
                                    <p class="text-xs text-gray-500">
                                        Message
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-medium text-gray-900"
                                        x-text="selectedDeployment.commit_message || 'No commit message'"
                                    ></p>
                                </div>

                            </div>

                        </div>
                    </div>

                    {{-- Changed Files --}}
                    <div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Changed Files
                                </h3>

                                <p class="mt-1 text-xs text-gray-500">
                                    Files changed between the previous and deployed commit.
                                </p>
                            </div>

                            {{-- Change Summary --}}
                            <div
                                class="flex flex-wrap items-center gap-2"
                                x-show="selectedDeployment.metadata?.git?.changed_files"
                            >

                                {{-- Files --}}
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                    <span
                                        x-text="selectedDeployment.metadata?.git?.changed_files?.total ?? 0"
                                    ></span>

                                    <span class="ml-1">
                                        files
                                    </span>
                                </span>

                                {{-- Additions --}}
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                    +
                                    <span
                                        class="ml-0.5"
                                        x-text="selectedDeployment.metadata?.git?.changed_files?.additions ?? 0"
                                    ></span>
                                </span>

                                {{-- Deletions --}}
                                <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                    −
                                    <span
                                        class="ml-0.5"
                                        x-text="selectedDeployment.metadata?.git?.changed_files?.deletions ?? 0"
                                    ></span>
                                </span>

                            </div>

                        </div>

                        {{-- No changed files data --}}
                        <template
                            x-if="!selectedDeployment.metadata?.git?.changed_files"
                        >
                            <div class="mt-3 rounded-lg border border-gray-100 bg-gray-50 p-5 text-center">
                                <p class="text-sm text-gray-500">
                                    No changed-file information is available for this deployment.
                                </p>
                            </div>
                        </template>

                        {{-- Changed files available --}}
                        <template
                            x-if="selectedDeployment.metadata?.git?.changed_files"
                        >
                            <div class="mt-3 overflow-hidden rounded-lg border border-gray-200">

                                {{-- Previous / current commit --}}
                                <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">

                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                                        <div class="min-w-0">
                                            <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                                Previous Commit
                                            </p>

                                            <code
                                                class="mt-1 block truncate text-xs text-gray-600"
                                                x-text="selectedDeployment.metadata?.git?.previous_commit || 'First deployment'"
                                                :title="selectedDeployment.metadata?.git?.previous_commit || 'First deployment'"
                                            ></code>
                                        </div>

                                        <div class="min-w-0 sm:text-right">
                                            <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                                Deployed Commit
                                            </p>

                                            <code
                                                class="mt-1 block truncate text-xs text-gray-600"
                                                x-text="selectedDeployment.metadata?.git?.deployed_commit || selectedDeployment.commit_hash || '—'"
                                                :title="selectedDeployment.metadata?.git?.deployed_commit || selectedDeployment.commit_hash || '—'"
                                            ></code>
                                        </div>

                                    </div>

                                </div>

                                {{-- Files --}}
                                <div class="divide-y divide-gray-100">

                                    <template
                                        x-for="(file, index) in (selectedDeployment.metadata?.git?.changed_files?.files || [])"
                                        :key="index"
                                    >

                                        <div class="px-4 py-3 transition hover:bg-gray-50">

                                            <div class="flex items-start gap-3">

                                                {{-- Status Icon --}}
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

                                                {{-- File information --}}
                                                <div class="min-w-0 flex-1">

                                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                                                        <div class="min-w-0">

                                                            {{-- Current path --}}
                                                            <code
                                                                class="block break-all text-xs font-medium text-gray-800"
                                                                x-text="file.path || 'Unknown file'"
                                                            ></code>

                                                            {{-- Old path for rename --}}
                                                            <template
                                                                x-if="file.status === 'renamed' && file.old_path"
                                                            >
                                                                <p class="mt-1 text-[11px] text-gray-400">
                                                                    <span>from</span>

                                                                    <code
                                                                        class="ml-1 break-all"
                                                                        x-text="file.old_path"
                                                                    ></code>
                                                                </p>
                                                            </template>

                                                        </div>

                                                        {{-- Status --}}
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

                                                    {{-- Line changes --}}
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
                                        x-if="(selectedDeployment.metadata?.git?.changed_files?.files || []).length === 0"
                                    >
                                        <div class="p-6 text-center">
                                            <p class="text-sm text-gray-500">
                                                No files were changed between these commits.
                                            </p>
                                        </div>
                                    </template>

                                </div>

                            </div>
                        </template>

                    </div>

                    {{-- Timeline --}}
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Timeline
                        </h3>

                        <div class="mt-3 space-y-3">

                            {{-- Started --}}
                            <div class="flex items-center gap-3">

                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50">
                                    <svg
                                        class="h-4 w-4 text-blue-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.8"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M8.25 4.5v15l11.25-7.5L8.25 4.5Z"
                                        />
                                    </svg>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-500">
                                        Started
                                    </p>

                                    <p
                                        class="text-sm font-medium text-gray-900"
                                        x-text="formatDate(selectedDeployment.started_at)"
                                    ></p>
                                </div>

                            </div>

                            {{-- Completed --}}
                            <div class="flex items-center gap-3">

                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full"
                                    :class="selectedDeployment.status === 'failed'
                                        ? 'bg-red-50'
                                        : 'bg-green-50'"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        :class="selectedDeployment.status === 'failed'
                                            ? 'text-red-600'
                                            : 'text-green-600'"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.8"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 6v6l4 2"
                                        />
                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="8.25"
                                        />
                                    </svg>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-500">
                                        Completed
                                    </p>

                                    <p
                                        class="text-sm font-medium text-gray-900"
                                        x-text="formatDate(selectedDeployment.completed_at)"
                                    ></p>
                                </div>

                            </div>

                        </div>
                    </div>

                    {{-- Output --}}
                    <div>
                        <div class="flex items-center justify-between">

                            <h3 class="text-sm font-semibold text-gray-900">
                                Output
                            </h3>

                            <button
                                type="button"
                                x-show="selectedDeployment.output"
                                @click="navigator.clipboard.writeText(selectedDeployment.output || '')"
                                class="text-xs font-medium text-gray-500 transition hover:text-gray-900"
                            >
                                Copy
                            </button>

                        </div>

                        <div class="mt-3 max-h-80 overflow-auto rounded-lg bg-gray-950 p-4">
                            <pre
                                class="whitespace-pre-wrap break-words font-mono text-xs leading-5 text-gray-300"
                                x-text="selectedDeployment.output || 'No output recorded.'"
                            ></pre>
                        </div>
                    </div>

                    {{-- Error --}}
                    <div x-show="selectedDeployment.error">

                        <h3 class="text-sm font-semibold text-red-800">
                            Error
                        </h3>

                        <div class="mt-3 max-h-72 overflow-auto rounded-lg bg-red-50 p-4">
                            <pre
                                class="whitespace-pre-wrap break-words font-mono text-xs leading-5 text-red-800"
                                x-text="selectedDeployment.error"
                            ></pre>
                        </div>

                    </div>

                    {{-- Metadata --}}
                    <div x-show="selectedDeployment.metadata">

                        <h3 class="text-sm font-semibold text-gray-900">
                            Metadata
                        </h3>

                        <div class="mt-3 overflow-hidden rounded-lg border border-gray-100">
                            <pre
                                class="max-h-72 overflow-auto bg-gray-50 p-4 font-mono text-xs leading-5 text-gray-700"
                                x-text="JSON.stringify(selectedDeployment.metadata || {}, null, 2)"
                            ></pre>
                        </div>

                    </div>

                </div>

            </template>

        </div>

        <div class="shrink-0 border-t border-gray-100 bg-white p-5">

    <div class="flex items-center justify-between gap-3">

        {{-- Rollback availability --}}
        <div
            x-show="
                selectedDeployment &&
                selectedDeployment.status === 'completed' &&
                (selectedDeployment.type || 'deployment') === 'deployment' &&
                selectedDeployment.metadata?.git?.previous_commit
            "
            class="hidden text-xs text-gray-400 sm:block"
        >
            This deployment can be restored to its previous commit.
        </div>

        <div class="ml-auto flex items-center gap-3">

            {{-- Rollback --}}
            <button
                type="button"
                x-show="
                    selectedDeployment &&
                    selectedDeployment.status === 'completed' &&
                    (selectedDeployment.type || 'deployment') === 'deployment' &&
                    selectedDeployment.metadata?.git?.previous_commit
                "
                @click="openRollbackPreview(selectedDeployment.id)"
                :disabled="deploymentLock.locked"
                :title="
                    deploymentLock.locked
                        ? deploymentLockMessage()
                        : 'Preview rollback'
                "
                class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50"
            >
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
                        d="M9 15 4 10m0 0 5-5m-5 5h11a5 5 0 0 1 5 5v1"
                    />
                </svg>

                Rollback
            </button>

            {{-- Close --}}
            <button
                type="button"
                @click="closeDetailsModal()"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
                Close
            </button>

        </div>

    </div>

</div>

    </div>
</div>