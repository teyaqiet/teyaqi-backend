<div
    x-show="showDetailsModal"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
    x-transition.opacity
>
    <div
        class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        @click.outside="closeDetailsModal()"
        x-transition
    >

        {{-- Header --}}
        <div class="shrink-0 border-b border-gray-100 bg-white p-6">
            <div class="flex items-start justify-between gap-4">

                <div class="flex items-start gap-4">

                    {{-- File icon --}}
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

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                                <div class="min-w-0">
                                    <p class="text-xs text-gray-500">
                                        Commit Hash
                                    </p>

                                    <code
                                        class="mt-1 block break-all text-sm font-medium text-gray-900"
                                        x-text="selectedDeployment.commit_hash || '—'"
                                    ></code>
                                </div>

                                <div class="sm:text-right">
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

        {{-- Footer --}}
        <div class="shrink-0 border-t border-gray-100 bg-white p-5">
            <div class="flex justify-end">

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