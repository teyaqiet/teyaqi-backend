<div class="space-y-5">


{{-- ============================================================
     FIELD
============================================================= --}}

<div>

    <label class="mb-2 block text-sm font-semibold text-gray-700">
        Field
    </label>

    <div class="relative">

        <select
            :value="getNodeConfigValue(selectedNode, 'field')"
            @change="changeConditionField($event.target.value)"
            class="w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-10 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
        >

            <option value="">
                Select a field
            </option>


            {{-- ========================================================
                 PLAYER
            ========================================================= --}}

            <optgroup label="Player">

                <option value="user_id">
                    User ID
                </option>

                <option value="name">
                    Player Name
                </option>

                <option value="username">
                    Username
                </option>

                <option value="telegram_id">
                    Telegram ID
                </option>

                <option value="level">
                    Level
                </option>

                <option value="total_xp">
                    Total XP
                </option>

                <option value="current_streak">
                    Current Streak
                </option>

                <option value="best_streak">
                    Best Streak
                </option>

                <option value="daily_lives">
                    Daily Lives
                </option>

            </optgroup>


            {{-- ========================================================
                 TRIGGER
            ========================================================= --}}

            <optgroup label="Trigger">

                <option value="trigger.type">
                    Trigger Type
                </option>

            </optgroup>


            {{-- ========================================================
                 STREAK REACHED
            ========================================================= --}}

            <optgroup label="Streak Reached">

                <option value="trigger.data.streak">
                    Streak Reached
                </option>

            </optgroup>


            {{-- ========================================================
                 XP MILESTONE
            ========================================================= --}}

            <optgroup label="XP Milestone">

                <option value="trigger.data.previous_xp">
                    Previous XP
                </option>

                <option value="trigger.data.new_xp">
                    New XP
                </option>

                <option value="trigger.data.milestone">
                    XP Milestone
                </option>

            </optgroup>


            {{-- ========================================================
                 GAME COMPLETED
            ========================================================= --}}

            <optgroup label="Game Completed">

                <option value="trigger.data.session_id">
                    Session ID
                </option>

                <option value="trigger.data.correct_answers">
                    Correct Answers
                </option>

                <option value="trigger.data.total_questions">
                    Total Questions
                </option>

                <option value="trigger.data.lives_lost">
                    Lives Lost
                </option>

                <option value="trigger.data.xp_earned">
                    XP Earned
                </option>

                <option value="trigger.data.perfect_bonus">
                    Perfect Bonus XP
                </option>

            </optgroup>


            {{-- ========================================================
                 LEVEL UP
            ========================================================= --}}

            <optgroup label="Level Up">

                <option value="trigger.data.previous_level">
                    Previous Level
                </option>

                <option value="trigger.data.new_level">
                    New Level
                </option>

            </optgroup>


            {{-- ========================================================
                 PLAYER INACTIVE
            ========================================================= --}}

            <optgroup label="Player Inactive">

                <option value="trigger.data.days_inactive">
                    Days Inactive
                </option>

                <option value="trigger.data.previous_streak">
                    Previous Streak
                </option>

                <option value="trigger.data.last_activity_at">
                    Last Activity
                </option>

            </optgroup>


            {{-- ========================================================
                 PLAYER RETURNED
            ========================================================= --}}

            <optgroup label="Player Returned">

                <option value="trigger.data.days_inactive">
                    Days Inactive
                </option>

                <option value="trigger.data.previous_streak">
                    Previous Streak
                </option>

            </optgroup>


            {{-- ========================================================
                 STREAK AT RISK
            ========================================================= --}}

            <optgroup label="Streak At Risk">

                <option value="trigger.data.current_streak">
                    Current Streak
                </option>

                <option value="trigger.data.hours_remaining">
                    Hours Remaining
                </option>

            </optgroup>


            {{-- ========================================================
                 FIELD DESCRIPTION
            ========================================================= --}}

        </select>

    </div>


    <p
        x-show="
            getNodeConfigValue(
                selectedNode,
                'field'
            )
        "
        x-text="
            getConditionFieldDescription(
                getNodeConfigValue(
                    selectedNode,
                    'field'
                )
            )
        "
        class="mt-2 text-xs text-gray-500"
    ></p>

</div>



{{-- ============================================================
     OPERATOR
============================================================= --}}

<div
    x-show="
        getNodeConfigValue(
            selectedNode,
            'field'
        )
    "
    x-transition
>

    <label class="mb-2 block text-sm font-semibold text-gray-700">
        Operator
    </label>

    <div class="relative">

        <select
            :value="
                getNodeConfigValue(
                    selectedNode,
                    'operator'
                )
            "
            @change="
                changeConditionOperator(
                    $event.target.value
                )
            "
            class="w-full appearance-none rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
        >

            <option value="">
                Select an operator
            </option>

            <template
                x-for="
                    operator in getConditionOperators(
                        getNodeConfigValue(
                            selectedNode,
                            'field'
                        )
                    )
                "
                :key="operator.value"
            >

                <option
                    :value="operator.value"
                    x-text="operator.label"
                ></option>

            </template>

        </select>

    </div>

</div>



{{-- ============================================================
     VALUE
============================================================= --}}

<div
    x-show="
        getNodeConfigValue(
            selectedNode,
            'field'
        ) &&

        getNodeConfigValue(
            selectedNode,
            'operator'
        ) &&

        conditionOperatorRequiresValue(
            getNodeConfigValue(
                selectedNode,
                'operator'
            )
        )
    "
    x-transition
>

    <label class="mb-2 block text-sm font-semibold text-gray-700">
        Value
    </label>


    {{-- ========================================================
         BOOLEAN VALUE
    ========================================================= --}}

    <template
        x-if="
            isBooleanConditionField(
                getNodeConfigValue(
                    selectedNode,
                    'field'
                )
            )
        "
    >

        <select
            :value="
                getNodeConfigValue(
                    selectedNode,
                    'value'
                )
            "
            @change="
                changeConditionValue(
                    $event.target.value
                )
            "
            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
        >

            <option value="">
                Select true or false
            </option>

            <option value="true">
                True
            </option>

            <option value="false">
                False
            </option>

        </select>

    </template>


    {{-- ========================================================
         NUMBER VALUE
    ========================================================= --}}

    <template
        x-if="
            isNumericConditionField(
                getNodeConfigValue(
                    selectedNode,
                    'field'
                )
            )
        "
    >

        <input
            type="number"
            :value="
                getNodeConfigValue(
                    selectedNode,
                    'value'
                )
            "
            @input="
                changeConditionValue(
                    $event.target.value
                )
            "
            :placeholder="
                getConditionValuePlaceholder()
            "
            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
        >

    </template>


    {{-- ========================================================
         DATE VALUE
    ========================================================= --}}

    <template
        x-if="
            isDateConditionField(
                getNodeConfigValue(
                    selectedNode,
                    'field'
                )
            )
        "
    >

        <input
            type="datetime-local"
            :value="
                getNodeConfigValue(
                    selectedNode,
                    'value'
                )
            "
            @input="
                changeConditionValue(
                    $event.target.value
                )
            "
            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
        >

    </template>


    {{-- ========================================================
         STRING VALUE
    ========================================================= --}}

    <template
        x-if="
            isStringConditionField(
                getNodeConfigValue(
                    selectedNode,
                    'field'
                )
            )
        "
    >

        <input
            type="text"
            :value="
                getNodeConfigValue(
                    selectedNode,
                    'value'
                )
            "
            @input="
                changeConditionValue(
                    $event.target.value
                )
            "
            :placeholder="
                getConditionValuePlaceholder()
            "
            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
        >

    </template>


    {{-- ========================================================
         VALUE HELP
    ========================================================= --}}

    <p
        x-show="
            getConditionValueHelp()
        "
        x-text="
            getConditionValueHelp()
        "
        class="mt-2 text-xs text-gray-500"
    ></p>


    {{-- ========================================================
         CASE SENSITIVE
    ========================================================= --}}

    <div
        x-show="
            isStringConditionField(
                getNodeConfigValue(
                    selectedNode,
                    'field'
                )
            ) &&

            conditionOperatorRequiresValue(
                getNodeConfigValue(
                    selectedNode,
                    'operator'
                )
            )
        "
        class="mt-3 flex items-center gap-2"
    >

        <input
            type="checkbox"
            :checked="
                getNodeConfigValue(
                    selectedNode,
                    'case_sensitive'
                )
            "
            @change="
                updateNodeConfig(
                    selectedNode,
                    'case_sensitive',
                    $event.target.checked
                )
            "
            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        >

        <label class="text-xs text-gray-600">
            Case sensitive
        </label>

    </div>

</div>



{{-- ============================================================
     NO VALUE MESSAGE
============================================================= --}}

<div
    x-show="
        getNodeConfigValue(
            selectedNode,
            'field'
        ) &&

        getNodeConfigValue(
            selectedNode,
            'operator'
        ) &&

        !conditionOperatorRequiresValue(
            getNodeConfigValue(
                selectedNode,
                'operator'
            )
        )
    "
    x-transition
    class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3"
>

    <div class="flex items-start gap-3">

        <div
            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600"
        >

            <svg
                class="h-3.5 w-3.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z"
                />

            </svg>

        </div>

        <div>

            <p class="text-sm font-medium text-indigo-800">
                No value required
            </p>

            <p
                class="mt-0.5 text-xs text-indigo-600"
                x-text="
                    getConditionValueHelp()
                "
            ></p>

        </div>

    </div>

</div>

</div>