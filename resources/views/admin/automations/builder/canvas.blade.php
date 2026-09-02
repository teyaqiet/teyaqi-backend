<main
    x-ref="canvas"
    class="relative flex-1 overflow-hidden bg-gray-50"
    @dragover.prevent
    @drop.prevent="dropNode($event)"
    @click="selectConnectionAt($event)"
>

    {{-- =========================================================
         BACKGROUND GRID
    ========================================================== --}}

    <div
        class="pointer-events-none absolute inset-0 z-0"
        style="
            background-image:
                radial-gradient(#d1d5db 1px, transparent 1px);
            background-size: 24px 24px;
        "
    ></div>


    {{-- =========================================================
         DROP INDICATOR
    ========================================================== --}}

    <div
        x-show="isDragging"
        x-cloak
        class="pointer-events-none absolute inset-0 z-40 border-2 border-dashed border-blue-300 bg-blue-50/20"
    ></div>


    {{-- =========================================================
         EMPTY STATE
    ========================================================== --}}

    <div
        x-show="nodes.length === 0"
        x-cloak
        class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center"
    >

        <div class="text-center">

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-200 bg-white text-2xl shadow-sm">
                +
            </div>

            <h3 class="mt-4 text-sm font-semibold text-gray-700">
                Start building your automation
            </h3>

            <p class="mt-1 text-xs text-gray-400">
                Drag a node from the library onto the canvas.
            </p>

        </div>

    </div>


    {{-- =========================================================
         CONNECTION SVG
    ========================================================== --}}

    <svg
        x-ref="connectionLayer"
        class="pointer-events-none absolute inset-0 z-30 h-full w-full overflow-visible"
    >

        {{-- Existing connections --}}
        <path
            :d="connectionsPath()"
            fill="none"
            :stroke="
                selectedConnection !== null
                    ? '#94a3b8'
                    : '#64748b'
            "
            stroke-width="3"
            stroke-linecap="round"
            stroke-linejoin="round"
        ></path>


        {{-- Active connection draft --}}
        <path
            x-show="connectionDraft.active"
            x-cloak
            :d="connectionPreviewPath()"
            fill="none"
            stroke="#C7FF4D"
            stroke-width="3"
            stroke-linecap="round"
            stroke-dasharray="7 7"
        ></path>

    </svg>


    {{-- =========================================================
         NODES
    ========================================================== --}}

    <template
        x-for="node in nodes"
        :key="node.id"
    >

        <div
            class="absolute z-20 w-64 select-none"
            :data-node-id="node.id"
            :style="`
                left:${node.position.x}px;
                top:${node.position.y}px;
            `"
            @click.stop="selectNode(node.id)"
            @pointerdown="startNodeMove(node.id, $event)"
        >

            {{-- =================================================
                 VALIDATION MARKER
            ================================================== --}}

            <div
                x-show="
                    nodeHasValidationError(node.id) ||
                    nodeHasValidationWarning(node.id)
                "
                x-cloak
                class="absolute -right-2 -top-2 z-[80]"
            >

                {{-- Error --}}
                <div
                    x-show="nodeHasValidationError(node.id)"
                    x-cloak
                    class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-rose-500 text-white shadow-md"
                    title="Validation error"
                >
                    <i class="ik ik-alert-circle text-xs"></i>
                </div>


                {{-- Warning --}}
                <div
                    x-show="
                        !nodeHasValidationError(node.id) &&
                        nodeHasValidationWarning(node.id)
                    "
                    x-cloak
                    class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-amber-500 text-white shadow-md"
                    title="Validation warning"
                >
                    <i class="ik ik-alert-triangle text-xs"></i>
                </div>

            </div>


            {{-- =================================================
                 NODE CARD
            ================================================== --}}

            <div
                class="relative min-h-[108px] rounded-xl border-2 bg-white shadow-sm transition duration-150"
                :class="{

                    /*
                    |--------------------------------------------------------------------------
                    | Validation error
                    |--------------------------------------------------------------------------
                    */

                    'border-rose-400 shadow-lg shadow-rose-500/10 ring-2 ring-rose-100':
                        nodeHasValidationError(node.id),

                    /*
                    |--------------------------------------------------------------------------
                    | Validation warning
                    |--------------------------------------------------------------------------
                    */

                    'border-amber-400 shadow-lg shadow-amber-500/10 ring-2 ring-amber-100':
                        !nodeHasValidationError(node.id) &&
                        nodeHasValidationWarning(node.id),

                    /*
                    |--------------------------------------------------------------------------
                    | Normal selected state
                    |--------------------------------------------------------------------------
                    */

                    'border-gray-900 shadow-lg':
                        !nodeHasValidationError(node.id) &&
                        !nodeHasValidationWarning(node.id) &&
                        selectedNode === node.id,

                    /*
                    |--------------------------------------------------------------------------
                    | Normal node
                    |--------------------------------------------------------------------------
                    */

                    'border-gray-200 hover:border-gray-400':
                        !nodeHasValidationError(node.id) &&
                        !nodeHasValidationWarning(node.id) &&
                        selectedNode !== node.id,

                }"
            >


                {{-- =================================================
                     VALIDATION ERROR STRIP
                ================================================== --}}

                <div
                    x-show="nodeHasValidationError(node.id)"
                    x-cloak
                    class="flex items-center gap-1.5 border-b border-rose-100 bg-rose-50 px-3 py-1.5 text-[10px] font-semibold text-rose-600"
                >

                    <i class="ik ik-alert-circle text-[11px]"></i>

                    <span>
                        Configuration error
                    </span>

                </div>


                {{-- =================================================
                     VALIDATION WARNING STRIP
                ================================================== --}}

                <div
                    x-show="
                        !nodeHasValidationError(node.id) &&
                        nodeHasValidationWarning(node.id)
                    "
                    x-cloak
                    class="flex items-center gap-1.5 border-b border-amber-100 bg-amber-50 px-3 py-1.5 text-[10px] font-semibold text-amber-600"
                >

                    <i class="ik ik-alert-triangle text-[11px]"></i>

                    <span>
                        Configuration warning
                    </span>

                </div>


                {{-- =================================================
                     INPUT HANDLE
                ================================================== --}}

                <button
                    x-show="node.type !== 'trigger'"
                    type="button"
                    :data-automation-input="node.id"
                    :data-automation-handle="'input'"
                    :data-automation-node="node.id"
                    class="absolute -left-4 top-1/2 z-50 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border-2 border-gray-400 bg-white text-gray-500 shadow-sm transition"
                    :class="
                        connectionDraft.active
                            ? 'scale-110 border-blue-500 bg-blue-50 text-blue-600'
                            : 'hover:border-blue-500 hover:bg-blue-50'
                    "
                    title="Input"
                    @pointerdown.stop="handleConnectionHandlePointerDown($event)"
                >
                    <span class="h-3 w-3 rounded-full bg-current"></span>
                </button>


                {{-- =================================================
                     HEADER
                ================================================== --}}

                <div class="cursor-grab border-b border-gray-100 px-4 py-3 active:cursor-grabbing">

                    <div class="flex items-center gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                            :class="nodeColors(node.type)"
                        >
                            <span
                                x-text="nodeIcon(node.type)"
                            ></span>
                        </div>


                        <div class="min-w-0 flex-1">

                            <div
                                class="truncate text-sm font-semibold text-gray-900"
                                x-text="node.name"
                            ></div>

                            <div
                                class="text-[11px] text-gray-400"
                                x-text="nodeLabel(node.type)"
                            ></div>

                        </div>


                        {{-- Delete --}}
                        <button
                            type="button"
                            @click.stop="deleteNode(node.id)"
                            class="flex h-6 w-6 items-center justify-center rounded-md text-gray-300 hover:bg-red-50 hover:text-red-500"
                            title="Delete node"
                        >
                            ×
                        </button>

                    </div>

                </div>


                {{-- =================================================
                     BODY
                ================================================== --}}

                <div class="px-4 py-3">

                    <p
                        class="text-xs leading-5 text-gray-500"
                        x-text="
                            node.description ||
                            defaultDescription(node.type)
                        "
                    ></p>


                    {{-- Validation message preview --}}
                    <template
                        x-if="nodeHasValidationError(node.id)"
                    >

                        <div class="mt-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2">

                            <template
                                x-for="
                                    error in validationErrorsForNode(node.id)
                                "
                                :key="
                                    error.code +
                                    '-' +
                                    (error.message ?? '')
                                "
                            >

                                <div class="flex items-start gap-2">

                                    <span class="mt-0.5 shrink-0 text-rose-500">
                                        <i class="ik ik-alert-circle text-[10px]"></i>
                                    </span>

                                    <span
                                        class="text-[10px] leading-4 text-rose-700"
                                        x-text="error.message"
                                    ></span>

                                </div>

                            </template>

                        </div>

                    </template>

                </div>


                {{-- =================================================
                     STANDARD OUTPUT
                ================================================== --}}

                <button
                    x-show="
                        node.type !== 'end' &&
                        node.type !== 'condition'
                    "
                    type="button"
                    :data-automation-handle="'output'"
                    :data-automation-node="node.id"
                    class="absolute -right-3 top-1/2 z-50 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full border-2 border-gray-400 bg-white text-gray-500 transition hover:border-gray-700 hover:bg-gray-50"
                    title="Output"
                    @pointerdown.stop="
                        startConnection(
                            node.id,
                            'output',
                            $event
                        )
                    "
                >
                    <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                </button>


                {{-- =================================================
                     CONDITION TRUE OUTPUT
                ================================================== --}}

                <button
                    x-show="node.type === 'condition'"
                    type="button"
                    :data-automation-handle="'true'"
                    :data-automation-node="node.id"
                    class="absolute -right-3 top-[35%] z-50 flex h-6 w-6 items-center justify-center rounded-full border-2 border-emerald-500 bg-white text-[8px] font-bold text-emerald-600 transition hover:bg-emerald-50"
                    title="True"
                    @pointerdown.stop="
                        startConnection(
                            node.id,
                            'true',
                            $event
                        )
                    "
                >
                    T
                </button>


                {{-- =================================================
                     CONDITION FALSE OUTPUT
                ================================================== --}}

                <button
                    x-show="node.type === 'condition'"
                    type="button"
                    :data-automation-handle="'false'"
                    :data-automation-node="node.id"
                    class="absolute -right-3 top-[65%] z-50 flex h-6 w-6 items-center justify-center rounded-full border-2 border-rose-500 bg-white text-[8px] font-bold text-rose-600 transition hover:bg-rose-50"
                    title="False"
                    @pointerdown.stop="
                        startConnection(
                            node.id,
                            'false',
                            $event
                        )
                    "
                >
                    F
                </button>

            </div>

        </div>

    </template>


    {{-- =========================================================
         SELECTED CONNECTION ACTION
    ========================================================== --}}

    <div
        x-show="selectedConnection !== null"
        x-cloak
        class="absolute left-1/2 top-4 z-[70] -translate-x-1/2"
    >

        <button
            type="button"
            @click.stop="
                deleteConnection(
                    selectedConnection
                )
            "
            class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-rose-600 shadow-lg ring-1 ring-gray-200 hover:bg-rose-50"
        >
            Delete connection
        </button>

    </div>


    {{-- =========================================================
         CONNECTION MODE INDICATOR
    ========================================================== --}}

    <div
        x-show="connectionDraft.active"
        x-cloak
        class="absolute left-1/2 top-4 z-[60] -translate-x-1/2 rounded-lg bg-gray-900 px-4 py-2 text-xs font-medium text-white shadow-lg"
    >

        <div class="flex items-center gap-2">

            <span class="h-2 w-2 animate-pulse rounded-full bg-blue-400"></span>

            <span>
                Drag to an input handle
            </span>

            <button
                type="button"
                @click.stop="cancelConnection()"
                class="ml-2 text-gray-400 hover:text-white"
            >
                Cancel
            </button>

        </div>

    </div>

</main>