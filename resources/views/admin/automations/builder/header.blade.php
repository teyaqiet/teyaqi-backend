<header
    class="z-30 flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-5"
>

    {{-- =========================================================
         LEFT
    ========================================================== --}}

    <div class="flex min-w-0 items-center gap-4">

        <a
            href="{{ route('admin.automations.index') }}"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
            title="Back to Automations"
        >
            <i class="ik ik-arrow-left text-sm"></i>
        </a>

        <div class="min-w-0">

            <input
                type="text"
                x-model="automation.name"
                @input="markDirty()"
                class="w-64 border-0 bg-transparent p-0 text-sm font-semibold text-gray-900 outline-none focus:ring-0"
                placeholder="Automation name"
            >

            <div class="mt-0.5 flex items-center gap-2">

                <span class="text-xs text-gray-400">
                    Automation Builder
                </span>

                <span class="text-gray-300">
                    ·
                </span>

                <span
                    class="text-xs font-medium"
                    :class="{
                        'text-emerald-600': automation.status === 'active',
                        'text-amber-600': automation.status === 'draft',
                        'text-gray-500': automation.status === 'paused'
                    }"
                    x-text="
                        automation.status === 'active'
                            ? 'Active'
                            : automation.status === 'paused'
                                ? 'Paused'
                                : 'Draft'
                    "
                ></span>

            </div>

        </div>

    </div>


    {{-- =========================================================
         RIGHT
    ========================================================== --}}

    <div class="flex items-center gap-2">

        {{-- =====================================================
             SAVE STATE
        ====================================================== --}}

        <div class="mr-2 hidden text-xs sm:block">

            <span
                x-show="saveState === 'saved'"
                x-cloak
                class="text-gray-400"
            >
                Saved
            </span>

            <span
                x-show="saveState === 'dirty'"
                x-cloak
                class="text-amber-600"
            >
                Unsaved changes
            </span>

            <span
                x-show="saveState === 'saving'"
                x-cloak
                class="text-blue-600"
            >
                Saving...
            </span>

            <span
                x-show="saveState === 'testing'"
                x-cloak
                class="text-blue-600"
            >
                Running test...
            </span>

            <span
                x-show="saveState === 'activating'"
                x-cloak
                class="text-emerald-600"
            >
                Activating...
            </span>

        </div>


        {{-- =====================================================
             VALIDATE
        ====================================================== --}}

        <button
            type="button"
            @click="validateAutomation()"
            :disabled="
                saveState === 'saving' ||
                saveState === 'testing' ||
                saveState === 'activating'
            "
            class="hidden items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 sm:inline-flex"
        >

            <i class="ik ik-check-circle text-sm"></i>

            <span>
                Validate
            </span>

        </button>


        {{-- =====================================================
             TEST RUN
        ====================================================== --}}

        <button
            type="button"
            @click="testRun()"
            :disabled="
                saveState === 'saving' ||
                saveState === 'testing' ||
                saveState === 'activating'
            "
            class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50"
        >

            <i
                x-show="saveState !== 'testing'"
                class="ik ik-play text-sm"
            ></i>

            <i
                x-show="saveState === 'testing'"
                x-cloak
                class="ik ik-loader animate-spin text-sm"
            ></i>

            <span
                x-text="
                    saveState === 'testing'
                        ? 'Running...'
                        : 'Test Run'
                "
            ></span>

        </button>


        {{-- =====================================================
             PAUSE
        ====================================================== --}}

        <button
            x-show="automation.status === 'active'"
            x-cloak
            type="button"
            @click="pauseAutomation()"
            :disabled="
                saveState === 'saving' ||
                saveState === 'testing' ||
                saveState === 'activating'
            "
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
        >

            <i class="ik ik-pause text-sm"></i>

            <span>
                Pause
            </span>

        </button>


        {{-- =====================================================
             ACTIVATE
        ====================================================== --}}

        <button
            x-show="automation.status !== 'active'"
            x-cloak
            type="button"
            @click="activateAutomation()"
            :disabled="
                saveState === 'saving' ||
                saveState === 'testing' ||
                saveState === 'activating'
            "
            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
        >

            <i
                x-show="saveState !== 'activating'"
                class="ik ik-play text-sm"
            ></i>

            <i
                x-show="saveState === 'activating'"
                x-cloak
                class="ik ik-loader animate-spin text-sm"
            ></i>

            <span
                x-text="
                    saveState === 'activating'
                        ? 'Activating...'
                        : 'Activate'
                "
            ></span>

        </button>


        {{-- =====================================================
             SAVE
        ====================================================== --}}

        <button
            type="button"
            @click="saveWorkflow()"
            :disabled="
                saveState === 'saving' ||
                saveState === 'testing' ||
                saveState === 'activating'
            "
            class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
        >

            <i
                x-show="saveState !== 'saving'"
                class="ik ik-save text-sm"
            ></i>

            <i
                x-show="saveState === 'saving'"
                x-cloak
                class="ik ik-loader animate-spin text-sm"
            ></i>

            <span
                x-text="
                    saveState === 'saving'
                        ? 'Saving...'
                        : 'Save'
                "
            ></span>

        </button>

    </div>

</header>