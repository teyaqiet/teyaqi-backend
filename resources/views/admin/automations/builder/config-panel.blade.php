<aside
    class="flex w-80 shrink-0 flex-col overflow-hidden border-l border-gray-200 bg-white"
>

    {{-- ============================================================
         EMPTY STATE
    ============================================================= --}}

    <div
        x-show="selectedNode === null"
        x-cloak
        class="flex min-h-0 flex-1 items-center justify-center overflow-y-auto p-6"
    >

        <div class="max-w-[220px] text-center">

            <div
                class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border border-gray-200 bg-gray-50 text-gray-400"
            >
                <i class="ik ik-settings text-lg"></i>
            </div>

            <h3 class="mt-4 text-sm font-semibold text-gray-800">
                Node Configuration
            </h3>

            <p class="mt-1 text-xs leading-5 text-gray-400">
                Select a node on the canvas to configure its settings.
            </p>

        </div>

    </div>


    {{-- ============================================================
         SELECTED NODE PANEL
    ============================================================= --}}

    <div
        x-show="selectedNode !== null"
        x-cloak
        class="flex min-h-0 flex-1 flex-col"
    >

        {{-- ========================================================
             PANEL HEADER
        ========================================================= --}}

        <div
            class="shrink-0 border-b border-gray-200 bg-white px-5 py-4"
        >

            <div class="flex items-start justify-between gap-3">

                {{-- Node identity --}}

                <div class="min-w-0 flex-1">

                    <div class="flex items-center gap-3">

                        {{-- Node icon --}}

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                            :class="
                                getSelectedNode()
                                    ? nodeColors(getSelectedNode().type)
                                    : 'bg-gray-100 text-gray-400'
                            "
                        >

                            <span
                                x-text="
                                    getSelectedNode()
                                        ? nodeIcon(getSelectedNode().type)
                                        : '●'
                                "
                            ></span>

                        </div>


                        {{-- Node name --}}

                        <div class="min-w-0 flex-1">

                            <div class="flex min-w-0 items-center gap-2">

                                <h2
                                    class="min-w-0 truncate text-sm font-semibold text-gray-900"
                                    x-text="
                                        getSelectedNode()
                                            ? getSelectedNode().name
                                            : 'Node Configuration'
                                    "
                                ></h2>


                                {{-- Dirty indicator --}}

                                <span
                                    x-show="saveState === 'dirty'"
                                    x-cloak
                                    class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"
                                    title="Unsaved changes"
                                ></span>

                            </div>


                            <p
                                class="mt-0.5 truncate text-xs text-gray-400"
                                x-text="
                                    getSelectedNode()
                                        ? nodeLabel(getSelectedNode().type)
                                        : ''
                                "
                            ></p>

                        </div>

                    </div>

                </div>


                {{-- Close --}}

                <button
                    type="button"
                    @click="clearSelection()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    aria-label="Close configuration"
                    title="Close"
                >
                    <i class="ik ik-x text-sm"></i>
                </button>

            </div>


            {{-- ====================================================
                 NODE STATUS
            ===================================================== --}}

            <div
                x-show="
                    getSelectedNode() &&
                    (
                        nodeHasValidationError(getSelectedNode().id) ||
                        nodeHasValidationWarning(getSelectedNode().id)
                    )
                "
                x-cloak
                class="mt-3"
            >

                {{-- Error --}}

                <div
                    x-show="
                        getSelectedNode() &&
                        nodeHasValidationError(getSelectedNode().id)
                    "
                    x-cloak
                    class="flex items-center gap-2 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2"
                >

                    <div
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600"
                    >
                        <i class="ik ik-alert-circle text-xs"></i>
                    </div>

                    <div class="min-w-0">

                        <div class="text-[10px] font-semibold uppercase tracking-wide text-rose-600">
                            Configuration Error
                        </div>

                        <div class="mt-0.5 text-[10px] leading-4 text-rose-500">
                            This node requires attention before it can run.
                        </div>

                    </div>

                </div>


                {{-- Warning --}}

                <div
                    x-show="
                        getSelectedNode() &&
                        !nodeHasValidationError(getSelectedNode().id) &&
                        nodeHasValidationWarning(getSelectedNode().id)
                    "
                    x-cloak
                    class="flex items-center gap-2 rounded-lg border border-amber-100 bg-amber-50 px-3 py-2"
                >

                    <div
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600"
                    >
                        <i class="ik ik-alert-triangle text-xs"></i>
                    </div>

                    <div class="min-w-0">

                        <div class="text-[10px] font-semibold uppercase tracking-wide text-amber-600">
                            Configuration Warning
                        </div>

                        <div class="mt-0.5 text-[10px] leading-4 text-amber-500">
                            This node has a configuration warning.
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ============================================================
             CONFIGURATION SCROLL AREA
        ============================================================= --}}

        <div class="min-h-0 flex-1 overflow-y-auto">

            <div class="p-5">

                {{-- ====================================================
                     IMPORTANT
                     Use getSelectedNode() as the single source of truth.
                     Do NOT use selectedNodeData.
                ===================================================== --}}

                <template x-if="getSelectedNode()">

                    <div>

                        {{-- ====================================================
                             COMMON SETTINGS
                        ===================================================== --}}

                        @include(
                            'admin.automations.builder.configs.common'
                        )


                        {{-- ====================================================
                             DIVIDER
                        ===================================================== --}}

                        <div class="my-6 border-t border-gray-200"></div>


                        {{-- ====================================================
                             NODE CONFIGURATION HEADER
                        ===================================================== --}}

                        <div class="mb-4">

                            <div class="flex items-start justify-between gap-3">

                                <div>

                                    <h3
                                        class="text-xs font-semibold uppercase tracking-wide text-gray-900"
                                    >
                                        Configuration
                                    </h3>

                                    <p
                                        class="mt-1 text-xs leading-5 text-gray-400"
                                        x-text="
                                            getSelectedNode()
                                                ? nodeLabel(
                                                    getSelectedNode().type
                                                )
                                                : ''
                                        "
                                    ></p>

                                </div>


                                {{-- Unsaved status --}}

                                <div
                                    x-show="saveState === 'dirty'"
                                    x-cloak
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-amber-50 px-2 py-1 text-[9px] font-semibold text-amber-600 ring-1 ring-amber-100"
                                >

                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                    ></span>

                                    Unsaved

                                </div>

                            </div>

                        </div>


                        {{-- ====================================================
                             TRIGGER
                        ===================================================== --}}

                        <template
                            x-if="
                                getSelectedNode() &&
                                getSelectedNode().type === 'trigger'
                            "
                        >

                            @include(
                                'admin.automations.builder.configs.trigger'
                            )

                        </template>


                        {{-- ====================================================
                             CONDITION
                        ===================================================== --}}

                        <template
                            x-if="
                                getSelectedNode() &&
                                getSelectedNode().type === 'condition'
                            "
                        >

                            @include(
                                'admin.automations.builder.configs.condition'
                            )

                        </template>


                        {{-- ====================================================
                             TELEGRAM MESSAGE
                        ===================================================== --}}

                        <template
                            x-if="
                                getSelectedNode() &&
                                getSelectedNode().type === 'telegram_message'
                            "
                        >

                            @include(
                                'admin.automations.builder.configs.telegram-message'
                            )

                        </template>


                        {{-- ====================================================
                             DELAY
                        ===================================================== --}}

                        <template
                            x-if="
                                getSelectedNode() &&
                                getSelectedNode().type === 'delay'
                            "
                        >

                            @include(
                                'admin.automations.builder.configs.delay'
                            )

                        </template>


                        {{-- ====================================================
                             END
                        ===================================================== --}}

                        <template
                            x-if="
                                getSelectedNode() &&
                                getSelectedNode().type === 'end'
                            "
                        >

                            @include(
                                'admin.automations.builder.configs.end'
                            )

                        </template>


                        {{-- ====================================================
                             UNKNOWN NODE
                        ===================================================== --}}

                        <template
                            x-if="
                                getSelectedNode() &&
                                ![
                                    'trigger',
                                    'condition',
                                    'telegram_message',
                                    'delay',
                                    'end'
                                ].includes(
                                    getSelectedNode().type
                                )
                            "
                        >

                            <div
                                class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4"
                            >

                                <div class="flex items-start gap-3">

                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400"
                                    >
                                        <i class="ik ik-help-circle text-sm"></i>
                                    </div>

                                    <div class="min-w-0">

                                        <p class="text-xs font-medium text-gray-700">
                                            Unsupported Node
                                        </p>

                                        <p class="mt-1 text-xs leading-5 text-gray-400">
                                            No configuration is available for this node type yet.
                                        </p>

                                    </div>

                                </div>

                            </div>

                        </template>


                        {{-- ====================================================
                             NODE VALIDATION DETAILS
                        ===================================================== --}}

                        <div
                            x-show="
                                getSelectedNode() &&
                                (
                                    nodeHasValidationError(
                                        getSelectedNode().id
                                    ) ||
                                    nodeHasValidationWarning(
                                        getSelectedNode().id
                                    )
                                )
                            "
                            x-cloak
                            class="mt-6"
                        >

                            <div
                                class="rounded-xl border border-gray-200 bg-white p-4"
                            >

                                <div class="flex items-center justify-between">

                                    <div
                                        class="text-[10px] font-semibold uppercase tracking-wider text-gray-400"
                                    >
                                        Validation
                                    </div>


                                    <span
                                        x-show="
                                            getSelectedNode() &&
                                            nodeHasValidationError(
                                                getSelectedNode().id
                                            )
                                        "
                                        x-cloak
                                        class="text-[10px] font-semibold text-rose-500"
                                    >
                                        Error
                                    </span>


                                    <span
                                        x-show="
                                            getSelectedNode() &&
                                            !nodeHasValidationError(
                                                getSelectedNode().id
                                            ) &&
                                            nodeHasValidationWarning(
                                                getSelectedNode().id
                                            )
                                        "
                                        x-cloak
                                        class="text-[10px] font-semibold text-amber-500"
                                    >
                                        Warning
                                    </span>

                                </div>


                                {{-- Errors --}}

                                <div
                                    x-show="
                                        getSelectedNode() &&
                                        nodeHasValidationError(
                                            getSelectedNode().id
                                        )
                                    "
                                    x-cloak
                                    class="mt-3 space-y-2"
                                >

                                    <template
                                        x-for="
                                            error in validationErrorsForNode(
                                                getSelectedNode().id
                                            )
                                        "
                                        :key="
                                            error.code +
                                            '-' +
                                            (
                                                error.message || ''
                                            )
                                        "
                                    >

                                        <div
                                            class="flex items-start gap-2 rounded-lg bg-rose-50 px-3 py-2"
                                        >

                                            <i
                                                class="ik ik-alert-circle mt-0.5 shrink-0 text-[11px] text-rose-500"
                                            ></i>

                                            <span
                                                class="text-[10px] leading-4 text-rose-700"
                                                x-text="error.message"
                                            ></span>

                                        </div>

                                    </template>

                                </div>


                                {{-- Warnings --}}

                                <div
                                    x-show="
                                        getSelectedNode() &&
                                        !nodeHasValidationError(
                                            getSelectedNode().id
                                        ) &&
                                        nodeHasValidationWarning(
                                            getSelectedNode().id
                                        )
                                    "
                                    x-cloak
                                    class="mt-3 space-y-2"
                                >

                                    <template
                                        x-for="
                                            warning in validationWarningsForNode(
                                                getSelectedNode().id
                                            )
                                        "
                                        :key="
                                            warning.code +
                                            '-' +
                                            (
                                                warning.message || ''
                                            )
                                        "
                                    >

                                        <div
                                            class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2"
                                        >

                                            <i
                                                class="ik ik-alert-triangle mt-0.5 shrink-0 text-[11px] text-amber-500"
                                            ></i>

                                            <span
                                                class="text-[10px] leading-4 text-amber-700"
                                                x-text="warning.message"
                                            ></span>

                                        </div>

                                    </template>

                                </div>

                            </div>

                        </div>


                        {{-- ====================================================
                             CONFIGURATION SUMMARY
                        ===================================================== --}}

                        <div
                            x-show="getSelectedNode()"
                            x-cloak
                            class="mt-6 rounded-xl border border-gray-100 bg-gray-50/70 p-4"
                        >

                            <div
                                class="text-[10px] font-semibold uppercase tracking-wider text-gray-400"
                            >
                                Node Information
                            </div>


                            <div class="mt-3 space-y-2">

                                {{-- ID --}}

                                <div
                                    class="flex items-center justify-between gap-4"
                                >

                                    <span class="text-[10px] text-gray-400">
                                        Node ID
                                    </span>

                                    <span
                                        class="max-w-[150px] truncate text-right font-mono text-[10px] text-gray-600"
                                        x-text="
                                            getSelectedNode()
                                                ? getSelectedNode().id
                                                : '-'
                                        "
                                    ></span>

                                </div>


                                {{-- Type --}}

                                <div
                                    class="flex items-center justify-between gap-4"
                                >

                                    <span class="text-[10px] text-gray-400">
                                        Type
                                    </span>

                                    <span
                                        class="font-medium text-[10px] text-gray-600"
                                        x-text="
                                            getSelectedNode()
                                                ? getSelectedNode().type
                                                : '-'
                                        "
                                    ></span>

                                </div>


                                {{-- Position --}}

                                <div
                                    class="flex items-center justify-between gap-4"
                                >

                                    <span class="text-[10px] text-gray-400">
                                        Position
                                    </span>

                                    <span
                                        class="font-mono text-[10px] text-gray-600"
                                        x-text="
                                            getSelectedNode()
                                                ? (
                                                    Math.round(
                                                        getSelectedNode()
                                                            .position
                                                            ?.x || 0
                                                    ) +
                                                    ', ' +
                                                    Math.round(
                                                        getSelectedNode()
                                                            .position
                                                            ?.y || 0
                                                    )
                                                )
                                                : '-'
                                        "
                                    ></span>

                                </div>


                                {{-- Config status --}}

                                <div
                                    class="flex items-center justify-between gap-4"
                                >

                                    <span class="text-[10px] text-gray-400">
                                        Configuration
                                    </span>

                                    <span
                                        class="font-medium text-[10px] text-gray-600"
                                        x-text="
                                            getSelectedNode() &&
                                            getSelectedNode().config
                                                ? 'Configured'
                                                : 'Default'
                                        "
                                    ></span>

                                </div>

                            </div>

                        </div>

                    </div>

                </template>

            </div>

        </div>


        {{-- ============================================================
             PANEL FOOTER
        ============================================================= --}}

        <div
            class="shrink-0 border-t border-gray-200 bg-white p-4"
        >

            <div class="flex items-center justify-between gap-3">

                {{-- Save state --}}

                <div class="min-w-0">

                    {{-- Saving --}}

                    <div
                        x-show="saveState === 'saving'"
                        x-cloak
                        class="flex items-center gap-2 text-[10px] font-medium text-gray-500"
                    >

                        <svg
                            class="h-3.5 w-3.5 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                        >

                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                                stroke="currentColor"
                                stroke-width="2"
                                class="opacity-25"
                            ></circle>

                            <path
                                d="M21 12a9 9 0 0 0-9-9"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            ></path>

                        </svg>

                        Saving...

                    </div>


                    {{-- Testing --}}

                    <div
                        x-show="saveState === 'testing'"
                        x-cloak
                        class="flex items-center gap-2 text-[10px] font-medium text-indigo-600"
                    >

                        <svg
                            class="h-3.5 w-3.5 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                        >

                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                                stroke="currentColor"
                                stroke-width="2"
                                class="opacity-25"
                            ></circle>

                            <path
                                d="M21 12a9 9 0 0 0-9-9"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            ></path>

                        </svg>

                        Testing...

                    </div>


                    {{-- Activating --}}

                    <div
                        x-show="saveState === 'activating'"
                        x-cloak
                        class="flex items-center gap-2 text-[10px] font-medium text-emerald-600"
                    >

                        <svg
                            class="h-3.5 w-3.5 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                        >

                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                                stroke="currentColor"
                                stroke-width="2"
                                class="opacity-25"
                            ></circle>

                            <path
                                d="M21 12a9 9 0 0 1-9 9"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            ></path>

                        </svg>

                        Activating...

                    </div>


                    {{-- Dirty --}}

                    <div
                        x-show="saveState === 'dirty'"
                        x-cloak
                        class="flex items-center gap-2 text-[10px] font-medium text-amber-600"
                    >

                        <span
                            class="h-1.5 w-1.5 rounded-full bg-amber-500"
                        ></span>

                        Unsaved changes

                    </div>


                    {{-- Saved --}}

                    <div
                        x-show="
                            saveState === 'saved' &&
                            saveState !== 'saving' &&
                            saveState !== 'testing' &&
                            saveState !== 'activating'
                        "
                        x-cloak
                        class="flex items-center gap-2 text-[10px] font-medium text-emerald-600"
                    >

                        <i class="ik ik-check-circle text-xs"></i>

                        Saved

                    </div>

                </div>


                {{-- Save button --}}

                <button
                    type="button"
                    @click="saveWorkflow()"
                    :disabled="
                        saveState === 'saving' ||
                        saveState === 'testing' ||
                        saveState === 'activating' ||
                        saveState !== 'dirty'
                    "
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition"
                    :class="
                        saveState === 'dirty'
                            ? 'bg-gray-900 text-white shadow-sm hover:bg-gray-800'
                            : 'cursor-not-allowed bg-gray-100 text-gray-400'
                    "
                >

                    <i class="ik ik-save text-sm"></i>

                    Save

                </button>

            </div>

        </div>

    </div>

</aside>