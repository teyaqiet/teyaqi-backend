{{-- CHALLENGE SETTINGS --}}

<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">


    <div class="mb-5">

        <h2 class="font-semibold text-gray-800">
            Challenge Settings
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Control visibility, classification and challenge behavior.
        </p>

    </div>




    <div class="space-y-5">



        {{-- STATUS --}}
        <div>

            <label class="text-xs font-medium text-gray-500">
                Status
            </label>


            <select
                name="status"
                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:ring-0"
            >

                @foreach([
                    'draft'=>'Draft',
                    'active'=>'Active',
                    'inactive'=>'Inactive'
                ] as $key=>$label)

                    <option
                        value="{{ $key }}"
                        @selected(
                            old(
                                'status',
                                $challenge->status ?? 'draft'
                            ) === $key
                        )
                    >
                        {{ $label }}
                    </option>

                @endforeach


            </select>


        </div>







        {{-- TYPE --}}
        <div>

            <label class="text-xs font-medium text-gray-500">
                Challenge Type
            </label>


            <select
                name="type"
                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:ring-0"
            >


                @foreach([
                    'daily'=>'Daily',
                    'topic'=>'Topic',
                    'timed'=>'Timed',
                    'ranked'=>'Ranked'
                ] as $key=>$label)

                    <option
                        value="{{ $key }}"
                        @selected(
                            old(
                                'type',
                                $challenge->type ?? ''
                            ) === $key
                        )
                    >
                        {{ $label }}
                    </option>

                @endforeach


            </select>


        </div>







        {{-- VISIBILITY --}}
        <div>


            <label class="text-xs font-medium text-gray-500">
                Visibility
            </label>


            <select
                name="visibility"
                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:ring-0"
            >

                @foreach([
                    'public'=>'Public',
                    'private'=>'Private',
                    'hidden'=>'Hidden'
                ] as $key=>$label)


                    <option
                        value="{{ $key }}"
                        @selected(
                            old(
                                'visibility',
                                $challenge->visibility ?? 'public'
                            ) === $key
                        )
                    >
                        {{ $label }}
                    </option>


                @endforeach


            </select>


        </div>









        {{-- CATEGORY --}}
        <div>


            <label class="text-xs font-medium text-gray-500">
                Category
            </label>


            <select
                name="category_id"
                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:ring-0"
            >


                <option value="">
                    Select Category
                </option>


                @foreach($categories as $category)

                    <option
                        value="{{ $category->id }}"
                        @selected(
                            old(
                                'category_id',
                                $challenge->category_id ?? null
                            ) == $category->id
                        )
                    >

                        {{ $category->name['en'] ?? $category->name }}

                    </option>


                @endforeach


            </select>


        </div>









        {{-- TOPIC --}}
        <div>


            <label class="text-xs font-medium text-gray-500">
                Topic
            </label>


            <select
                name="topic_id"
                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:ring-0"
            >


                <option value="">
                    Select Topic
                </option>



                @foreach($topics as $topic)

                    <option
                        value="{{ $topic->id }}"
                        @selected(
                            old(
                                'topic_id',
                                $challenge->topic_id ?? null
                            ) == $topic->id
                        )
                    >

                        {{ $topic->name }}

                    </option>


                @endforeach


            </select>


        </div>










        {{-- FLAGS --}}
        <div class="border-t border-gray-100 pt-5 space-y-4">



            <label class="flex items-center gap-3 cursor-pointer">

                <input
                    type="checkbox"
                    name="is_daily"
                    value="1"

                    @checked(
                        old(
                            'is_daily',
                            $challenge->is_daily ?? false
                        )
                    )

                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                >


                <span class="text-sm text-gray-700">
                    Daily Challenge
                </span>


            </label>







            <label class="flex items-center gap-3 cursor-pointer">


                <input
                    type="checkbox"
                    name="is_featured"
                    value="1"

                    @checked(
                        old(
                            'is_featured',
                            $challenge->is_featured ?? false
                        )
                    )

                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                >


                <span class="text-sm text-gray-700">
                    Featured Challenge
                </span>


            </label>








            <label class="flex items-center gap-3 cursor-pointer">


                <input
                    type="checkbox"
                    name="is_ranked"
                    value="1"

                    @checked(
                        old(
                            'is_ranked',
                            $challenge->is_ranked ?? false
                        )
                    )

                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                >


                <span class="text-sm text-gray-700">
                    Ranked Challenge
                </span>


            </label>








            <label class="flex items-center gap-3 cursor-pointer">


                <input
                    type="checkbox"
                    name="allow_retry"
                    value="1"

                    @checked(
                        old(
                            'allow_retry',
                            $challenge->allow_retry ?? true
                        )
                    )

                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                >


                <span class="text-sm text-gray-700">
                    Allow Retry
                </span>


            </label>


        </div>



    </div>


</div>