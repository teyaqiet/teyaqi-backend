<aside
    class="w-64 shrink-0 overflow-y-auto border-r border-gray-200 bg-white"
>

    {{-- =========================================================
        HEADER
    ========================================================== --}}

    <div class="border-b border-gray-200 p-5">

        <h2 class="text-sm font-semibold text-gray-900">
            Node Library
        </h2>

        <p class="mt-1 text-xs text-gray-400">
            Drag a component onto the canvas.
        </p>

    </div>


    {{-- =========================================================
        NODE LIST
    ========================================================== --}}

    <div class="space-y-6 p-4">


        {{-- =====================================================
            TRIGGERS
        ====================================================== --}}

        <div>

            <div class="mb-3 flex items-center gap-2">

                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
                    Triggers
                </span>

            </div>


            <div
                draggable="true"
                @dragstart="startDrag('trigger')"
                @dragend="endDrag()"
                class="group cursor-grab rounded-xl border border-gray-200 bg-white p-3 transition hover:border-gray-400 hover:shadow-sm active:cursor-grabbing"
            >

                <div class="flex items-center gap-3">

                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-yellow-50 text-lg">
                        ⚡
                    </div>


                    <div class="min-w-0">

                        <div class="text-sm font-medium text-gray-800">
                            Trigger
                        </div>

                        <div class="text-xs text-gray-400">
                            Start workflow
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
            LOGIC
        ====================================================== --}}

        <div>

            <div class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                Logic
            </div>


            <div
                draggable="true"
                @dragstart="startDrag('condition')"
                @dragend="endDrag()"
                class="group cursor-grab rounded-xl border border-gray-200 bg-white p-3 transition hover:border-gray-400 hover:shadow-sm active:cursor-grabbing"
            >

                <div class="flex items-center gap-3">

                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-lg">
                        ◇
                    </div>


                    <div class="min-w-0">

                        <div class="text-sm font-medium text-gray-800">
                            Condition
                        </div>

                        <div class="text-xs text-gray-400">
                            Check a value
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
            ACTIONS
        ====================================================== --}}

        <div>

            <div class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                Actions
            </div>


            <div
                draggable="true"
                @dragstart="startDrag('telegram_message')"
                @dragend="endDrag()"
                class="group cursor-grab rounded-xl border border-gray-200 bg-white p-3 transition hover:border-gray-400 hover:shadow-sm active:cursor-grabbing"
            >

                <div class="flex items-center gap-3">

                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-lg">
                        ✈
                    </div>


                    <div class="min-w-0">

                        <div class="text-sm font-medium text-gray-800">
                            Telegram Message
                        </div>

                        <div class="text-xs text-gray-400">
                            Send a message
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
            FLOW CONTROL
        ====================================================== --}}

        <div>

            <div class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                Flow Control
            </div>


            <div class="space-y-2">


                {{-- Delay --}}

                <div
                    draggable="true"
                    @dragstart="startDrag('delay')"
                    @dragend="endDrag()"
                    class="group cursor-grab rounded-xl border border-gray-200 bg-white p-3 transition hover:border-gray-400 hover:shadow-sm active:cursor-grabbing"
                >

                    <div class="flex items-center gap-3">

                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange-50">
                            ⏱
                        </div>


                        <div class="min-w-0">

                            <div class="text-sm font-medium text-gray-800">
                                Delay
                            </div>

                            <div class="text-xs text-gray-400">
                                Wait before continuing
                            </div>

                        </div>

                    </div>

                </div>


                {{-- End --}}

                <div
                    draggable="true"
                    @dragstart="startDrag('end')"
                    @dragend="endDrag()"
                    class="group cursor-grab rounded-xl border border-gray-200 bg-white p-3 transition hover:border-gray-400 hover:shadow-sm active:cursor-grabbing"
                >

                    <div class="flex items-center gap-3">

                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100">
                            ■
                        </div>


                        <div class="min-w-0">

                            <div class="text-sm font-medium text-gray-800">
                                End
                            </div>

                            <div class="text-xs text-gray-400">
                                Finish workflow
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</aside>