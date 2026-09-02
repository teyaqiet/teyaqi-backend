<div class="space-y-5">

    {{-- ============================================================
         TRIGGER EVENT
    ============================================================= --}}

    <div>

        <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-600">
            Trigger Event
        </label>

        <div class="relative">

            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <i class="ik ik-zap"></i>
            </span>

            <select
    x-model="getSelectedNode().config.event"
    @change="markDirty()"
    class="w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-9 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
>
    <option value="">
        Select an event
    </option>

    @foreach ($automationTriggers as $type => $trigger)
        <option value="{{ $type }}">
            {{ $trigger['label'] }}
        </option>
    @endforeach
</select>

            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                <i class="ik ik-chevron-down"></i>
            </span>

        </div>

        <p class="mt-1.5 text-xs text-gray-400">
            Choose the game event that should start this automation.
        </p>

    </div>


    {{-- ============================================================
         SELECTED EVENT INFORMATION
    ============================================================= --}}

    <div
        x-show="
            getSelectedNode() &&
            getSelectedNode().config &&
            getSelectedNode().config.event
        "
        x-cloak
        class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4"
    >

        <div class="flex items-start gap-3">

            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm">
                <i class="ik ik-info text-sm"></i>
            </div>

            <div class="min-w-0">

                <div class="text-xs font-semibold text-indigo-900">
                    Event Trigger
                </div>

                <div class="mt-1 text-xs leading-5 text-indigo-700">
                    This automation will start automatically when the selected
                    event is dispatched by the Teyaqi game system.
                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================
         EVENT DESCRIPTION
    ============================================================= --}}

    <div>

        <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-600">
            Description
        </label>

        <div class="relative">

            <span class="pointer-events-none absolute left-0 top-0 flex items-center pl-3 pt-3 text-gray-400">
                <i class="ik ik-file-text"></i>
            </span>

            <textarea
                x-model="getSelectedNode().config.description"
                @input="markDirty()"
                rows="3"
                class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                placeholder="Describe what should start this automation..."
            ></textarea>

        </div>

        <p class="mt-1.5 text-xs text-gray-400">
            Optional internal description for administrators.
        </p>

    </div>


    {{-- ============================================================
         ENABLED
    ============================================================= --}}

    <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 p-4">

        <div>

            <div class="text-sm font-semibold text-gray-800">
                Enabled
            </div>

            <div class="mt-0.5 text-xs text-gray-400">
                Allow this trigger to start the automation.
            </div>

        </div>

        <label class="relative inline-flex cursor-pointer items-center">

            <input
                type="checkbox"
                x-model="getSelectedNode().config.enabled"
                @change="markDirty()"
                class="peer sr-only"
            >

            <div class="h-5 w-9 rounded-full bg-gray-300 transition peer-checked:bg-indigo-600"></div>

            <div class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></div>

        </label>

    </div>


    {{-- ============================================================
         CURRENT CONFIGURATION
    ============================================================= --}}

    <div class="rounded-xl border border-gray-100 bg-white p-4">

        <div class="flex items-center justify-between">

            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                Current Configuration
            </div>

            <span
                class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                :class="
                    getSelectedNode() &&
                    getSelectedNode().config &&
                    getSelectedNode().config.enabled
                        ? 'bg-emerald-50 text-emerald-600'
                        : 'bg-gray-100 text-gray-500'
                "
                x-text="
                    getSelectedNode() &&
                    getSelectedNode().config &&
                    getSelectedNode().config.enabled
                        ? 'Enabled'
                        : 'Disabled'
                "
            ></span>

        </div>


        <div class="mt-3 space-y-2 text-xs">

            {{-- Event --}}

            <div class="flex items-center justify-between gap-4">

                <span class="shrink-0 text-gray-400">
                    Event
                </span>

                <span
                    class="truncate text-right font-medium text-gray-700"
                    x-text="
                        getSelectedNode() &&
                        getSelectedNode().config
                            ? (
                                getSelectedNode().config.event ||
                                'Not configured'
                            )
                            : 'Not configured'
                    "
                ></span>

            </div>


            {{-- Description --}}

            <div class="flex items-center justify-between gap-4">

                <span class="shrink-0 text-gray-400">
                    Description
                </span>

                <span
                    class="max-w-[60%] truncate text-right font-medium text-gray-700"
                    x-text="
                        getSelectedNode() &&
                        getSelectedNode().config
                            ? (
                                getSelectedNode().config.description ||
                                'None'
                            )
                            : 'None'
                    "
                ></span>

            </div>

        </div>

    </div>

</div>