{{-- RULES & CONSTRAINTS --}}

<div
    x-data="{
        rules: {{ json_encode(old('rules', $challenge->rules ?? [
            [
                'category_id' => '',
                'topic_id' => '',
                'selection_type' => 'random',
                'difficulty' => '',
                'question_count' => 5,
                'tags' => ''
            ]
        ])) }},

        addRule(){
            this.rules.push({
                category_id:'',
                topic_id:'',
                selection_type:'random',
                difficulty:'',
                question_count:5,
                tags:''
            })
        },

        removeRule(index){
            this.rules.splice(index,1)
        }
    }"
    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100"
>

    <div class="mb-5">
        <h2 class="font-semibold text-gray-800">
            Rules & Constraints
        </h2>
        <p class="mt-1 text-xs text-gray-500">
            Configure challenge difficulty, timing and automatic question generation.
        </p>
    </div>

    <div class="space-y-6">

        {{-- BASIC RULES --}}
        <div class="grid gap-5 md:grid-cols-3">

            <div>
                <label class="text-xs font-medium text-gray-500">
                    Difficulty
                </label>
                <select
                    name="difficulty"
                    class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm"
                >
                    @foreach(['easy','medium','hard'] as $level)
                        <option
                            value="{{ $level }}"
                            @selected(old('difficulty', $challenge->difficulty ?? 'medium') == $level)
                        >
                            {{ ucfirst($level) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">
                    Question Count
                </label>
                <input
                    type="number"
                    name="question_count"
                    value="{{ old('question_count', $challenge->question_count ?? 10) }}"
                    class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm"
                >
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">
                    Passing Score (%)
                </label>
                <input
                    type="number"
                    name="passing_score"
                    value="{{ old('passing_score', $challenge->passing_score ?? 70) }}"
                    class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm"
                >
            </div>

        </div>

        {{-- TIMER --}}
        <div class="grid gap-5 md:grid-cols-3">

            <div>
                <label class="text-xs font-medium text-gray-500">
                    Timer Mode
                </label>
                <select
                    name="time_mode"
                    class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm"
                >
                    <option value="per_session" @selected(old('time_mode', $challenge->time_mode ?? 'per_session') == 'per_session')>
                        Whole Session
                    </option>
                    <option value="per_question" @selected(old('time_mode', $challenge->time_mode ?? '') == 'per_question')>
                        Per Question
                    </option>
                </select>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">
                    Time Limit (Seconds)
                </label>
                <input
                    type="number"
                    name="time_limit"
                    value="{{ old('time_limit', $challenge->time_limit ?? 60) }}"
                    class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm"
                >
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">
                    Minimum Level
                </label>
                <input
                    type="number"
                    name="level_min"
                    value="{{ old('level_min', $challenge->level_min ?? 1) }}"
                    class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm"
                >
            </div>

        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">
                Maximum Level
            </label>
            <input
                type="number"
                name="level_max"
                value="{{ old('level_max', $challenge->level_max ?? '') }}"
                placeholder="Optional"
                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm md:w-1/3"
            >
        </div>

        {{-- DYNAMIC RULES --}}
        <div class="border-t border-gray-100 pt-6">

            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-800">
                        Dynamic Question Pool
                    </h3>
                    <p class="text-xs text-gray-500">
                        Automatically generate questions based on filters.
                    </p>
                </div>

                <button
                    type="button"
                    @click="addRule()"
                    class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800"
                >
                    + Add Rule
                </button>
            </div>

            <div class="space-y-4">
                <template
                    x-for="(rule, index) in rules"
                    :key="index"
                >
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">

                        <div class="grid gap-4 md:grid-cols-3">

                            <div>
                                <label class="text-xs text-gray-500">
                                    Category
                                </label>
                                <select
                                    :name="`rules[${index}][category_id]`"
                                    x-model="rule.category_id"
                                    class="mt-2 h-10 w-full rounded-lg border-gray-200 text-sm"
                                >
                                    <option value="">
                                        Any Category
                                    </option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">
                                            {{ $category->name['en'] ?? $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs text-gray-500">
                                    Topic
                                </label>
                                <select
                                    :name="`rules[${index}][topic_id]`"
                                    x-model="rule.topic_id"
                                    class="mt-2 h-10 w-full rounded-lg border-gray-200 text-sm"
                                >
                                    <option value="">
                                        Any Topic
                                    </option>
                                    @foreach($topics as $topic)
                                        <option value="{{ $topic->id }}">
                                            {{ $topic->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs text-gray-500">
                                    Selection Type
                                </label>
                                <select
                                    :name="`rules[${index}][selection_type]`"
                                    x-model="rule.selection_type"
                                    class="mt-2 h-10 w-full rounded-lg border-gray-200 text-sm"
                                >
                                    <option value="random">
                                        Random
                                    </option>
                                    <option value="weighted">
                                        Weighted
                                    </option>
                                </select>
                            </div>

                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-3">

                            <div>
                                <label class="text-xs text-gray-500">
                                    Difficulty
                                </label>
                                <select
                                    :name="`rules[${index}][difficulty]`"
                                    x-model="rule.difficulty"
                                    class="mt-2 h-10 w-full rounded-lg border-gray-200 text-sm"
                                >
                                    <option value="">
                                        Any
                                    </option>
                                    <option value="easy">
                                        Easy
                                    </option>
                                    <option value="medium">
                                        Medium
                                    </option>
                                    <option value="hard">
                                        Hard
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs text-gray-500">
                                    Question Count
                                </label>
                                <input
                                    type="number"
                                    :name="`rules[${index}][question_count]`"
                                    x-model="rule.question_count"
                                    class="mt-2 h-10 w-full rounded-lg border-gray-200 text-sm"
                                >
                            </div>

                            <div>
                                <label class="text-xs text-gray-500">
                                    Tags
                                </label>
                                <input
                                    type="text"
                                    placeholder="history, science"
                                    :name="`rules[${index}][tags]`"
                                    x-model="rule.tags"
                                    class="mt-2 h-10 w-full rounded-lg border-gray-200 text-sm"
                                >
                            </div>

                        </div>

                        <div class="mt-4 text-right">
                            <button
                                type="button"
                                @click="removeRule(index)"
                                class="text-xs font-semibold text-red-600 hover:text-red-800"
                            >
                                Remove Rule
                            </button>
                        </div>

                    </div>
                </template>
            </div>

        </div>

    </div>
</div>