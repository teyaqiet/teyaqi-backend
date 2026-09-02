{{-- MANUAL QUESTIONS --}}

<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">


    <div class="mb-5">

        <h2 class="font-semibold text-gray-800">
            Manual Question Assignment
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Select specific questions that should appear in this challenge.
        </p>

    </div>




    <div
        x-data="{
            search:'',
            
            questions: {{ 
                $questions->map(function($question){
                    return [
                        'id'=>$question->id,
                        'text'=>$question->question_text['en'] ?? '',
                        'category'=>$question->category->name['en'] ?? ''
                    ];
                })->toJson()
            }},

            selected: {{ json_encode(old('manual_questions',[])) }},

            get filteredQuestions(){

                return this.questions.filter(q => 
                    q.text.toLowerCase()
                    .includes(this.search.toLowerCase())
                )

            }
        }"
        class="space-y-4"
    >





        {{-- SEARCH --}}
        <div>


            <input
                type="text"
                x-model="search"
                placeholder="Search questions..."
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
            >


        </div>







        {{-- SELECTED COUNT --}}
        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">


            <span class="text-xs text-gray-500">
                Selected Questions
            </span>


            <span
                class="text-sm font-semibold text-primary-600"
                x-text="selected.length"
            >
            </span>


        </div>







        {{-- QUESTIONS LIST --}}
        <div
            class="max-h-96 space-y-2 overflow-y-auto rounded-xl border border-gray-200 p-3"
        >


            <template
                x-for="question in filteredQuestions"
                :key="question.id"
            >


                <label
                    class="flex cursor-pointer items-start gap-3 rounded-lg p-3 hover:bg-gray-50"
                >


                    <input
                        type="checkbox"
                        name="manual_questions[]"
                        :value="question.id"
                        x-model="selected"
                        class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    >



                    <div>


                        <p
                            class="text-sm font-medium text-gray-700"
                            x-text="'#'+question.id+' - '+question.text"
                        >
                        </p>


                        <p
                            class="mt-1 text-xs text-gray-400"
                            x-text="question.category"
                        >
                        </p>


                    </div>


                </label>


            </template>


        </div>




    </div>


</div>