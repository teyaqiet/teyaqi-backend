<div
    x-show="showPreflightModal"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
    x-transition.opacity
>
    <div
        class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        @click.outside="!preflightLoading && closePreflightModal()"
        x-transition
    >


    {{-- ============================================================
         FIXED HEADER
    ============================================================= --}}

    <div class="shrink-0 border-b border-gray-100 bg-white p-6">

        <div class="flex items-start justify-between gap-4">

            <div class="flex items-start gap-4">

                {{-- Header Icon --}}
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                    :class="
                        preflightLoading
                            ? 'bg-blue-50 text-blue-600'
                            : preflightResult?.status === 'failed' || preflightResult?.success === false
                                ? 'bg-red-50 text-red-600'
                                : preflightResult?.status === 'warning'
                                    ? 'bg-amber-50 text-amber-600'
                                    : 'bg-green-50 text-green-600'
                    "
                >

                    {{-- Loading --}}
                    <template x-if="preflightLoading">
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
                                d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M8.757 15.243l-2.121 2.121m8.486 8.486 2.121 2.121"
                            />
                        </svg>
                    </template>

                    {{-- Failed --}}
                    <template x-if="
                        !preflightLoading &&
                        (
                            preflightResult?.status === 'failed' ||
                            preflightResult?.success === false
                        )
                    ">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="8.25"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m9 9 6 6m0-6-6 6"
                            />
                        </svg>
                    </template>

                    {{-- Warning --}}
                    <template x-if="
                        !preflightLoading &&
                        preflightResult?.status === 'warning'
                    ">
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
                                d="M12 4.5 20 19.5H4L12 4.5Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v4.5m0 3h.008"
                            />
                        </svg>
                    </template>

                    {{-- Ready --}}
                    <template x-if="
                        !preflightLoading &&
                        preflightResult?.status !== 'failed' &&
                        preflightResult?.status !== 'warning' &&
                        preflightResult?.success !== false
                    ">
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
                                d="M12 3.75 19.5 6v5.25c0 4.25-2.75 7.25-7.5 9-4.75-1.75-7.5-4.75-7.5-9V6L12 3.75Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m8.75 12 2.25 2.25 4.5-4.5"
                            />
                        </svg>
                    </template>

                </div>

                <div>

                    <h2 class="text-lg font-semibold text-gray-900">
                        Pre-flight Check
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Verify that the deployment environment is ready.
                    </p>

                </div>

            </div>

            {{-- Close --}}
            <button
                type="button"
                @click="closePreflightModal()"
                :disabled="preflightLoading"
                class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
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


    {{-- ============================================================
         SCROLLABLE CONTENT
    ============================================================= --}}

    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-6">

        {{-- ========================================================
             LOADING
        ========================================================= --}}

        <template x-if="preflightLoading">

            <div class="space-y-4">

                <div class="flex items-center gap-3 rounded-lg bg-blue-50 p-4">

                    <svg
                        class="h-5 w-5 shrink-0 animate-spin text-blue-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M8.757 15.243l-2.121 2.121m8.486 8.486 2.121 2.121"
                        />
                    </svg>

                    <div>

                        <p class="text-sm font-medium text-blue-900">
                            Running pre-flight checks...
                        </p>

                        <p class="mt-1 text-xs text-blue-700">
                            Please wait while the server environment is checked.
                        </p>

                    </div>

                </div>

                <div class="space-y-3">

                    <template x-for="i in 6" :key="i">

                        <div class="animate-pulse rounded-lg border border-gray-100 p-4">

                            <div class="h-3 w-32 rounded bg-gray-200"></div>

                            <div class="mt-2 h-2 w-48 rounded bg-gray-100"></div>

                        </div>

                    </template>

                </div>

            </div>

        </template>


        {{-- ========================================================
             RESULTS
        ========================================================= --}}

        <template x-if="!preflightLoading && preflightResult">

            <div class="space-y-5">

                {{-- ==================================================
                     OVERALL RESULT
                =================================================== --}}

                <div
                    class="rounded-lg p-4"
                    :class="
                        preflightResult.status === 'failed' ||
                        preflightResult.success === false
                            ? 'bg-red-50'
                            : preflightResult.status === 'warning'
                                ? 'bg-amber-50'
                                : 'bg-green-50'
                    "
                >

                    <div class="flex items-start gap-3">

                        {{-- Failed --}}
                        <template x-if="
                            preflightResult.status === 'failed' ||
                            preflightResult.success === false
                        ">
                            <svg
                                class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="8.25"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m9 9 6 6m0-6-6 6"
                                />
                            </svg>
                        </template>

                        {{-- Warning --}}
                        <template x-if="
                            preflightResult.status === 'warning' &&
                            preflightResult.success !== false
                        ">
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
                                    d="M12 4.5 20 19.5H4L12 4.5Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v4.5m0 3h.008"
                                />
                            </svg>
                        </template>

                        {{-- Ready --}}
                        <template x-if="
                            preflightResult.status !== 'failed' &&
                            preflightResult.status !== 'warning' &&
                            preflightResult.success !== false
                        ">
                            <svg
                                class="mt-0.5 h-5 w-5 shrink-0 text-green-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="8.25"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m8.5 12.25 2.25 2.25 4.75-5"
                                />
                            </svg>
                        </template>


                        <div class="min-w-0 flex-1">

                            {{-- Failed title --}}
                            <template x-if="
                                preflightResult.status === 'failed' ||
                                preflightResult.success === false
                            ">
                                <p class="text-sm font-semibold text-red-900">
                                    Environment is not ready
                                </p>
                            </template>

                            {{-- Warning title --}}
                            <template x-if="
                                preflightResult.status === 'warning' &&
                                preflightResult.success !== false
                            ">
                                <p class="text-sm font-semibold text-amber-900">
                                    Environment is ready with warnings
                                </p>
                            </template>

                            {{-- Ready title --}}
                            <template x-if="
                                preflightResult.status !== 'failed' &&
                                preflightResult.status !== 'warning' &&
                                preflightResult.success !== false
                            ">
                                <p class="text-sm font-semibold text-green-900">
                                    Environment is ready
                                </p>
                            </template>


                            <p
                                class="mt-1 text-xs leading-5"
                                :class="
                                    preflightResult.status === 'failed' ||
                                    preflightResult.success === false
                                        ? 'text-red-700'
                                        : preflightResult.status === 'warning'
                                            ? 'text-amber-700'
                                            : 'text-green-700'
                                "
                                x-text="preflightResult.message || preflightResult.error || ''"
                            ></p>

                        </div>

                    </div>

                </div>


                {{-- ==================================================
                     CHECKS
                =================================================== --}}

                <div
                    x-show="preflightResult.checks"
                    class="space-y-3"
                >

                    <div class="flex items-center justify-between">

                        <h3 class="text-sm font-semibold text-gray-900">
                            Environment Checks
                        </h3>

                        {{-- Summary --}}
                        <div class="flex items-center gap-3 text-xs">

                            <span
                                x-show="preflightResult.failed > 0"
                                class="font-medium text-red-600"
                                x-text="`${preflightResult.failed} failed`"
                            ></span>

                            <span
                                x-show="preflightResult.warnings > 0"
                                class="font-medium text-amber-600"
                                x-text="`${preflightResult.warnings} warning${preflightResult.warnings === 1 ? '' : 's'}`"
                            ></span>

                        </div>

                    </div>


                    <template
                        x-for="(check, key) in (preflightResult.checks || {})"
                        :key="key"
                    >

                        <div class="rounded-lg border border-gray-100 p-4">

                            <div class="flex items-start gap-3">

                                {{-- ==================================================
                                     CHECK STATUS ICON
                                =================================================== --}}

                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                                    :class="
                                        check.status === 'failed' ||
                                        check.success === false ||
                                        check.passed === false
                                            ? 'bg-red-50 text-red-600'
                                            : check.status === 'warning'
                                                ? 'bg-amber-50 text-amber-600'
                                                : check.status === 'skipped'
                                                    ? 'bg-gray-100 text-gray-500'
                                                    : 'bg-green-50 text-green-600'
                                    "
                                >

                                    {{-- Failed --}}
                                    <template x-if="
                                        check.status === 'failed' ||
                                        check.success === false ||
                                        check.passed === false
                                    ">
                                        <svg
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
                                    </template>

                                    {{-- Warning --}}
                                    <template x-if="
                                        check.status === 'warning'
                                    ">
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 4.5 20 19.5H4L12 4.5Z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 9v4.5m0 3h.008"
                                            />
                                        </svg>
                                    </template>

                                    {{-- Skipped --}}
                                    <template x-if="
                                        check.status === 'skipped'
                                    ">
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
                                                d="M6.75 6.75 17.25 17.25M17.25 6.75 6.75 17.25"
                                            />
                                        </svg>
                                    </template>

                                    {{-- Passed --}}
                                    <template x-if="
                                        check.status !== 'failed' &&
                                        check.status !== 'warning' &&
                                        check.status !== 'skipped' &&
                                        check.success !== false &&
                                        check.passed !== false
                                    ">
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="2"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m5 12.5 4.5 4.5L19 7.5"
                                            />
                                        </svg>
                                    </template>

                                </div>


                                {{-- ==================================================
                                     CHECK CONTENT
                                =================================================== --}}

                                <div class="min-w-0 flex-1">

                                    <div class="flex items-center justify-between gap-3">

                                        <p
                                            class="text-sm font-medium text-gray-900"
                                            x-text="formatStatus(key)"
                                        ></p>


                                        {{-- Status Label --}}
                                        <span
                                            class="shrink-0 text-xs font-medium"
                                            :class="
                                                check.status === 'failed' ||
                                                check.success === false ||
                                                check.passed === false
                                                    ? 'text-red-600'
                                                    : check.status === 'warning'
                                                        ? 'text-amber-600'
                                                        : check.status === 'skipped'
                                                            ? 'text-gray-500'
                                                            : 'text-green-600'
                                            "
                                            x-text="
                                                check.status === 'failed' ||
                                                check.success === false ||
                                                check.passed === false
                                                    ? 'Failed'
                                                    : check.status === 'warning'
                                                        ? 'Warning'
                                                        : check.status === 'skipped'
                                                            ? 'Skipped'
                                                            : 'Passed'
                                            "
                                        ></span>

                                    </div>


                                    <p
                                        x-show="check.message || check.error"
                                        class="mt-1 text-xs leading-5 text-gray-500 break-words"
                                        x-text="check.message || check.error"
                                    ></p>

                                </div>

                            </div>

                        </div>

                    </template>

                </div>


                {{-- ==================================================
                     GENERIC RESULT DATA
                =================================================== --}}

                <div
                    x-show="!preflightResult.checks && Object.keys(preflightResult).length"
                    class="space-y-3"
                >

                    <template
                        x-for="(value, key) in preflightResult"
                        :key="key"
                    >

                        <template x-if="
                            ![
                                'success',
                                'message',
                                'error',
                                'status',
                                'ready',
                                'passed',
                                'failed',
                                'warnings'
                            ].includes(key)
                        ">

                            <div class="rounded-lg border border-gray-100 p-4">

                                <div class="flex items-center justify-between gap-4">

                                    <span
                                        class="text-sm font-medium text-gray-700"
                                        x-text="formatStatus(key)"
                                    ></span>

                                    <span
                                        class="max-w-[60%] truncate text-right text-xs text-gray-500"
                                        x-text="
                                            typeof value === 'object'
                                                ? JSON.stringify(value)
                                                : value
                                        "
                                    ></span>

                                </div>

                            </div>

                        </template>

                    </template>

                </div>

            </div>

        </template>


        {{-- ========================================================
             NO RESULT
        ========================================================= --}}

        <template x-if="!preflightLoading && !preflightResult">

            <div class="py-10 text-center">

                <svg
                    class="mx-auto h-10 w-10 text-gray-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.6"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 3.75 19.5 6v5.25c0 4.25-2.75 7.25-7.5 9-4.75-1.75-7.5-4.75-7.5-9V6L12 3.75Z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v3.75m0 3h.008"
                    />
                </svg>

                <p class="mt-3 text-sm font-medium text-gray-900">
                    No pre-flight result
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    Run the pre-flight check to continue.
                </p>

            </div>

        </template>

    </div>


    {{-- ============================================================
         FIXED FOOTER
    ============================================================= --}}

    <div class="shrink-0 border-t border-gray-100 bg-white p-5">

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

            {{-- Cancel --}}
            <button
                type="button"
                @click="closePreflightModal()"
                :disabled="preflightLoading"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Cancel
            </button>


            {{-- Continue --}}
            <button
                type="button"
                @click="continueDeployment()"
                :disabled="
                    preflightLoading ||
                    !preflightResult ||
                    preflightResult.success === false ||
                    preflightResult.ready === false ||
                    preflightResult.passed === false
                "
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-40"
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
                        d="M12 16.5V3.75m0 0 4.5 4.5M12 3.75l-4.5 4.5"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M5.25 14.25v3.375A2.625 2.625 0 0 0 7.875 20.25h8.25a2.625 2.625 0 0 0 2.625-2.625V14.25"
                    />
                </svg>

                Continue to Deployment

            </button>

        </div>

    </div>

</div>


</div>