<div
    class="absolute z-20 w-64 select-none"
    :data-node-id="node.id"
    :style="`
        left:${node.position.x}px;
        top:${node.position.y}px;
    `"
    @click.stop="selectNode(node.id)"
>

    {{-- =========================================================
         VALIDATION INDICATOR
    ========================================================== --}}

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


    {{-- =========================================================
         NODE CARD
    ========================================================== --}}

    <div
        class="relative min-h-[108px] rounded-xl border-2 bg-white shadow-sm transition duration-150"
        :class="{

            'border-rose-400 shadow-lg shadow-rose-500/10 ring-2 ring-rose-100':
                nodeHasValidationError(node.id),

            'border-amber-400 shadow-lg shadow-amber-500/10 ring-2 ring-amber-100':
                !nodeHasValidationError(node.id) &&
                nodeHasValidationWarning(node.id),

            'border-gray-900 shadow-lg':
                !nodeHasValidationError(node.id) &&
                !nodeHasValidationWarning(node.id) &&
                selectedNode === node.id,

            'border-gray-200 hover:border-gray-400':
                !nodeHasValidationError(node.id) &&
                !nodeHasValidationWarning(node.id) &&
                selectedNode !== node.id,

        }"
    >


        {{-- =========================================================
             VALIDATION ERROR STRIP
        ========================================================== --}}

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


        {{-- =========================================================
             VALIDATION WARNING STRIP
        ========================================================== --}}

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


        {{-- =========================================================
             INPUT HANDLE
        ========================================================== --}}

        <button
            x-show="node.type !== 'trigger'"
            x-cloak
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
            @click.stop
        >

            <span
                class="h-3 w-3 rounded-full bg-current"
            ></span>

        </button>


        {{-- =========================================================
             HEADER / DRAG HANDLE
        ========================================================== --}}

        <div
            class="cursor-grab border-b border-gray-100 px-4 py-3 active:cursor-grabbing"
            @pointerdown.stop="startNodeMove(node.id, $event)"
        >

            <div class="flex items-center gap-3">

                {{-- Node Icon --}}
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                    :class="nodeColors(node.type)"
                >

                    <span
                        x-text="nodeIcon(node.type)"
                    ></span>

                </div>


                {{-- Node Information --}}
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


                {{-- =================================================
                     DELETE NODE
                ================================================== --}}

                <button
                    type="button"
                    @pointerdown.stop.prevent
                    @mousedown.stop.prevent
                    @click.stop.prevent="deleteNode(node.id)"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-gray-300 transition hover:bg-red-50 hover:text-rose-500"
                    title="Delete node"
                    aria-label="Delete node"
                >

                    <i class="ik ik-trash-2 text-sm"></i>

                </button>

            </div>

        </div>


        {{-- =========================================================
             BODY
        ========================================================== --}}

        <div class="px-4 py-3">

            {{-- Description --}}
            <p
                class="text-xs leading-5 text-gray-500"
                x-text="
                    node.description ||
                    defaultDescription(node.type)
                "
            ></p>


            {{-- =====================================================
                 TELEGRAM MESSAGE PREVIEW
            ====================================================== --}}

            <template
                x-if="
                    node.type === 'telegram_message' &&
                    node.config
                "
            >

                <div
                    class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2"
                >

                    {{-- Message --}}
                    <div
                        x-show="node.config.message"
                        class="line-clamp-3 text-[10px] leading-4 text-blue-700"
                        x-text="node.config.message"
                    ></div>


                    {{-- Empty message --}}
                    <div
                        x-show="!node.config.message"
                        class="text-[10px] italic text-blue-400"
                    >
                        No message configured
                    </div>


                    {{-- Recipient --}}
                    <div
                        class="mt-1.5 truncate text-[9px] text-blue-400"
                        x-show="
                            node.config.recipient ||
                            node.config.chat_id
                        "
                    >

                        <span
                            x-text="
                                node.config.chat_id ||
                                node.config.recipient ||
                                'No recipient'
                            "
                        ></span>

                    </div>

                </div>

            </template>


            {{-- =====================================================
                 VALIDATION MESSAGE PREVIEW
            ====================================================== --}}

            <template
                x-if="nodeHasValidationError(node.id)"
            >

                <div
                    class="mt-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2"
                >

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

                            <span
                                class="mt-0.5 shrink-0 text-rose-500"
                            >
                                <i
                                    class="ik ik-alert-circle text-[10px]"
                                ></i>
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


        {{-- =========================================================
             STANDARD OUTPUT
        ========================================================== --}}

        <button
            x-show="
                node.type !== 'end' &&
                node.type !== 'condition'
            "
            x-cloak
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
            @click.stop
        >

            <span
                class="h-2.5 w-2.5 rounded-full bg-current"
            ></span>

        </button>


        {{-- =========================================================
             CONDITION TRUE OUTPUT
        ========================================================== --}}

        <button
            x-show="node.type === 'condition'"
            x-cloak
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
            @click.stop
        >
            T
        </button>


        {{-- =========================================================
             CONDITION FALSE OUTPUT
        ========================================================== --}}

        <button
            x-show="node.type === 'condition'"
            x-cloak
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
            @click.stop
        >
            F
        </button>

    </div>

</div>