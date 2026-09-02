{{-- ============================================================
     END NODE CONFIGURATION
============================================================= --}}

<div class="space-y-5">

    {{-- ========================================================
         COMPLETION STATUS
    ========================================================= --}}

    <div>

        <label
            class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-600"
        >
            Completion Status
        </label>

        <div class="relative">

            <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"
            >
                <i class="ik ik-check-circle text-sm"></i>
            </span>

            <select
                x-model="getSelectedNode().config.status"
                @change="markDirty()"
                class="w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-9 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            >

                <option value="success">
                    Success
                </option>

                <option value="failed">
                    Failed
                </option>

                <option value="cancelled">
                    Cancelled
                </option>

            </select>

            <span
                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"
            >
                <i class="ik ik-chevron-down"></i>
            </span>

        </div>

        <p class="mt-1.5 text-xs leading-5 text-gray-400">
            Determines the final status recorded for this automation execution.
        </p>

    </div>


    {{-- ========================================================
         COMPLETION MESSAGE
    ========================================================= --}}

    <div>

        <div class="mb-2 flex items-center justify-between">

            <label
                class="block text-xs font-semibold uppercase tracking-wider text-gray-600"
            >
                Completion Message
            </label>

            <span
                class="text-[10px] font-medium text-gray-400"
                x-text="
                    (
                        getSelectedNode()?.config?.message || ''
                    ).length + ' characters'
                "
            ></span>

        </div>

        <div class="relative">

            <span
                class="pointer-events-none absolute left-0 top-0 flex items-center pl-3 pt-3 text-gray-400"
            >
                <i class="ik ik-message-square text-sm"></i>
            </span>

            <textarea
                x-model="getSelectedNode().config.message"
                @input="markDirty()"
                rows="4"
                class="w-full resize-y rounded-xl border border-gray-200 py-2.5 pl-9 pr-3 text-sm leading-6 text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                placeholder="Optional message describing the final result..."
            ></textarea>

        </div>

        <p class="mt-1.5 text-xs leading-5 text-gray-400">
            Optional information stored with the execution result.
        </p>

    </div>


    {{-- ========================================================
         AVAILABLE VARIABLES
    ========================================================= --}}

    <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">

        <div class="flex items-start gap-3">

            <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm"
            >
                <i class="ik ik-braces text-sm"></i>
            </div>

            <div class="min-w-0">

                <div class="text-xs font-semibold text-indigo-900">
                    Available Variables
                </div>

                <p class="mt-1 text-xs leading-5 text-indigo-700">
                    Values are resolved from the current automation context when the node executes.
                </p>

                <div class="mt-3 flex flex-wrap gap-2">

                    {{-- ====================================================
                         NAME
                    ===================================================== --}}

                    <button
                        type="button"
                        @click="
                            getSelectedNode().config.message =
                                (getSelectedNode().config.message || '') +
                                '\u007B\u007Bname\u007D\u007D';

                            markDirty();
                        "
                        class="rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-medium text-indigo-700 shadow-sm ring-1 ring-indigo-100 transition hover:bg-indigo-50"
                    >
                        &#123;&#123;name&#125;&#125;
                    </button>


                    {{-- ====================================================
                         STREAK
                    ===================================================== --}}

                    <button
                        type="button"
                        @click="
                            getSelectedNode().config.message =
                                (getSelectedNode().config.message || '') +
                                '\u007B\u007Bstreak\u007D\u007D';

                            markDirty();
                        "
                        class="rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-medium text-indigo-700 shadow-sm ring-1 ring-indigo-100 transition hover:bg-indigo-50"
                    >
                        &#123;&#123;streak&#125;&#125;
                    </button>


                    {{-- ====================================================
                         CURRENT STREAK
                    ===================================================== --}}

                    <button
                        type="button"
                        @click="
                            getSelectedNode().config.message =
                                (getSelectedNode().config.message || '') +
                                '\u007B\u007Bcurrent_streak\u007D\u007D';

                            markDirty();
                        "
                        class="rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-medium text-indigo-700 shadow-sm ring-1 ring-indigo-100 transition hover:bg-indigo-50"
                    >
                        &#123;&#123;current_streak&#125;&#125;
                    </button>


                    {{-- ====================================================
                         TOTAL XP
                    ===================================================== --}}

                    <button
                        type="button"
                        @click="
                            getSelectedNode().config.message =
                                (getSelectedNode().config.message || '') +
                                '\u007B\u007Btotal_xp\u007D\u007D';

                            markDirty();
                        "
                        class="rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-medium text-indigo-700 shadow-sm ring-1 ring-indigo-100 transition hover:bg-indigo-50"
                    >
                        &#123;&#123;total_xp&#125;&#125;
                    </button>


                    {{-- ====================================================
                         USERNAME
                    ===================================================== --}}

                    <button
                        type="button"
                        @click="
                            getSelectedNode().config.message =
                                (getSelectedNode().config.message || '') +
                                '\u007B\u007Busername\u007D\u007D';

                            markDirty();
                        "
                        class="rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-medium text-indigo-700 shadow-sm ring-1 ring-indigo-100 transition hover:bg-indigo-50"
                    >
                        &#123;&#123;username&#125;&#125;
                    </button>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================
         EXECUTION PREVIEW
    ========================================================= --}}

    <div
        class="rounded-2xl p-4"
        :class="
            getSelectedNode()?.config?.status === 'failed'
                ? 'border border-rose-100 bg-rose-50/60'
                : getSelectedNode()?.config?.status === 'cancelled'
                    ? 'border border-amber-100 bg-amber-50/60'
                    : 'border border-emerald-100 bg-emerald-50/60'
        "
    >

        <div class="flex items-start gap-3">

            {{-- Status icon --}}

            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm"
                :class="
                    getSelectedNode()?.config?.status === 'failed'
                        ? 'text-rose-600'
                        : getSelectedNode()?.config?.status === 'cancelled'
                            ? 'text-amber-600'
                            : 'text-emerald-600'
                "
            >

                <span
                    class="text-sm font-bold"
                    x-text="
                        getSelectedNode()?.config?.status === 'failed'
                            ? '!'
                            : getSelectedNode()?.config?.status === 'cancelled'
                                ? '!'
                                : '✓'
                    "
                ></span>

            </div>


            {{-- Preview content --}}

            <div class="min-w-0 flex-1">

                <div
                    class="text-xs font-semibold"
                    :class="
                        getSelectedNode()?.config?.status === 'failed'
                            ? 'text-rose-900'
                            : getSelectedNode()?.config?.status === 'cancelled'
                                ? 'text-amber-900'
                                : 'text-emerald-900'
                    "
                >
                    Workflow Completion
                </div>


                <div
                    class="mt-1 text-[11px] leading-5"
                    :class="
                        getSelectedNode()?.config?.status === 'failed'
                            ? 'text-rose-700'
                            : getSelectedNode()?.config?.status === 'cancelled'
                                ? 'text-amber-700'
                                : 'text-emerald-700'
                    "
                >

                    Execution will finish with

                    <strong
                        x-text="
                            getSelectedNode()?.config?.status || 'success'
                        "
                    ></strong>

                    status.

                </div>


                {{-- ====================================================
                     RESULT MESSAGE PREVIEW
                ===================================================== --}}

                <div
                    x-show="getSelectedNode()?.config?.message"
                    x-cloak
                    class="mt-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100"
                >

                    <div
                        class="mb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-400"
                    >
                        Result Message
                    </div>

                    <div
                        class="whitespace-pre-wrap break-words text-xs leading-5 text-gray-700"
                        x-text="
                            getSelectedNode()?.config?.message || ''
                        "
                    ></div>

                </div>

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

            {{-- ====================================================
                 STATUS
            ===================================================== --}}

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Status
                </span>

                <span
                    class="font-medium capitalize text-gray-700"
                    x-text="
                        getSelectedNode()?.config?.status || 'success'
                    "
                ></span>

            </div>


            {{-- ====================================================
                 RESULT MESSAGE
            ===================================================== --}}

            <div class="flex items-center justify-between gap-4">

                <span class="text-gray-400">
                    Result Message
                </span>

                <span
                    class="max-w-[160px] truncate text-right font-medium text-gray-700"
                    x-text="
                        getSelectedNode()?.config?.message
                            ? 'Configured'
                            : 'None'
                    "
                ></span>

            </div>

        </div>

    </div>

</div>