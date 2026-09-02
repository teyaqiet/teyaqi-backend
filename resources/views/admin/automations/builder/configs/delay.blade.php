{{-- ============================================================
     DELAY NODE CONFIGURATION
============================================================= --}}

<div class="space-y-5">

    {{-- ========================================================
         DURATION
    ========================================================= --}}

    <div>

        <label
            for="delay-duration"
            class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-600"
        >
            Duration
        </label>

        <div class="relative">

            <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"
            >
                <i class="ik ik-clock text-sm"></i>
            </span>

            <input
                id="delay-duration"
                type="number"
                min="1"
                step="1"

                :value="getNodeConfigValue(
                    selectedNode,
                    'duration',
                    1
                )"

                @input="
                    updateNodeConfig(
                        selectedNode,
                        'duration',
                        Math.max(
                            1,
                            Number($event.target.value) || 1
                        )
                    )
                "

                class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"

                placeholder="1"
            >

        </div>

        <p class="mt-1.5 text-xs leading-5 text-gray-400">
            How long the automation should wait before continuing.
        </p>

    </div>


    {{-- ========================================================
         TIME UNIT
    ========================================================= --}}

    <div>

        <label
            for="delay-unit"
            class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-600"
        >
            Time Unit
        </label>

        <div class="relative">

            <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"
            >
                <i class="ik ik-clock text-sm"></i>
            </span>

            <select
                id="delay-unit"

                :value="getNodeConfigValue(
                    selectedNode,
                    'unit',
                    'minutes'
                )"

                @change="
                    updateNodeConfig(
                        selectedNode,
                        'unit',
                        $event.target.value
                    )
                "

                class="w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-9 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            >

                <option value="seconds">
                    Seconds
                </option>

                <option value="minutes">
                    Minutes
                </option>

                <option value="hours">
                    Hours
                </option>

                <option value="days">
                    Days
                </option>

            </select>

            <span
                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"
            >
                <i class="ik ik-chevron-down text-sm"></i>
            </span>

        </div>

    </div>


    {{-- ========================================================
         WAIT PREVIEW
    ========================================================= --}}

    <div
        class="rounded-2xl border border-orange-100 bg-orange-50/60 p-4"
    >

        <div class="flex items-start gap-3">

            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-orange-600 shadow-sm"
            >
                <i class="ik ik-clock text-sm"></i>
            </div>

            <div class="min-w-0 flex-1">

                <div class="text-xs font-semibold text-orange-900">
                    Wait Time
                </div>

                <p class="mt-1 text-[11px] leading-5 text-orange-700">

                    Wait

                    <span
                        class="font-semibold"
                        x-text="
                            getNodeConfigValue(
                                selectedNode,
                                'duration',
                                1
                            )
                        "
                    ></span>

                    <span
                        class="font-semibold"
                        x-text="
                            getNodeConfigValue(
                                selectedNode,
                                'unit',
                                'minutes'
                            )
                        "
                    ></span>

                    before continuing to the next node.

                </p>

            </div>

        </div>

    </div>


    {{-- ========================================================
         EXECUTION BEHAVIOR
    ========================================================= --}}

    <div
        class="rounded-xl border border-gray-100 bg-gray-50 p-4"
    >

        <div class="flex items-start gap-3">

            <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-gray-500 shadow-sm"
            >
                <i class="ik ik-info text-sm"></i>
            </div>

            <div>

                <div class="text-xs font-semibold text-gray-700">
                    Execution Behavior
                </div>

                <p class="mt-1 text-[11px] leading-5 text-gray-500">
                    The workflow pauses at this node until the configured
                    delay has elapsed, then continues through the connected
                    output node.
                </p>

            </div>

        </div>

    </div>


    {{-- ========================================================
         CONFIGURATION SUMMARY
    ========================================================= --}}

    <div class="rounded-xl border border-gray-100 bg-white p-4">

        <div
            class="text-[10px] font-semibold uppercase tracking-wider text-gray-400"
        >
            Configuration
        </div>

        <div class="mt-3 space-y-2 text-xs">

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Duration
                </span>

                <span
                    class="font-medium text-gray-700"
                    x-text="
                        getNodeConfigValue(
                            selectedNode,
                            'duration',
                            1
                        )
                        +
                        ' '
                        +
                        getNodeConfigValue(
                            selectedNode,
                            'unit',
                            'minutes'
                        )
                    "
                ></span>

            </div>

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Behavior
                </span>

                <span class="font-medium text-gray-700">
                    Pause execution
                </span>

            </div>

        </div>

    </div>

</div>