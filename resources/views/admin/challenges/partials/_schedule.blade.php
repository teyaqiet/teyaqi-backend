{{-- SCHEDULE & AVAILABILITY --}}

<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">


    <div class="mb-5">

        <h2 class="font-semibold text-gray-800">
            Schedule & Availability
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Control when this challenge becomes available and when it expires.
        </p>

    </div>





    <div
        x-data="{
            startEnabled: {{ 
                old('start_at', $challenge->start_at ?? null) 
                    ? 'true' 
                    : 'false' 
            }},
            endEnabled: {{ 
                old('end_at', $challenge->end_at ?? null) 
                    ? 'true' 
                    : 'false' 
            }}
        }"
        class="space-y-6"
    >





        {{-- START DATE --}}
        <div class="rounded-xl border border-gray-200 p-4">


            <label class="flex items-center gap-3 cursor-pointer">


                <input
                    type="checkbox"
                    x-model="startEnabled"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                >


                <span class="text-sm font-medium text-gray-700">
                    Schedule Start Date
                </span>


            </label>





            <div
                x-show="startEnabled"
                x-cloak
                class="mt-4"
            >


                <label class="text-xs font-medium text-gray-500">
                    Activate Challenge At
                </label>



                <input
                    type="datetime-local"
                    name="start_at"
                    value="{{ old(
                        'start_at',
                        isset($challenge->start_at)
                            ? \Carbon\Carbon::parse($challenge->start_at)->format('Y-m-d\TH:i')
                            : ''
                    ) }}"
                    class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:ring-0"
                >



                <p class="mt-1 text-xs text-gray-400">
                    Challenge remains unavailable before this time.
                </p>


            </div>



        </div>









        {{-- END DATE --}}
        <div class="rounded-xl border border-gray-200 p-4">


            <label class="flex items-center gap-3 cursor-pointer">


                <input
                    type="checkbox"
                    x-model="endEnabled"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                >


                <span class="text-sm font-medium text-gray-700">
                    Schedule End Date
                </span>


            </label>







            <div
                x-show="endEnabled"
                x-cloak
                class="mt-4"
            >


                <label class="text-xs font-medium text-gray-500">
                    Deactivate Challenge At
                </label>




                <input
                    type="datetime-local"
                    name="end_at"
                    value="{{ old(
                        'end_at',
                        isset($challenge->end_at)
                            ? \Carbon\Carbon::parse($challenge->end_at)->format('Y-m-d\TH:i')
                            : ''
                    ) }}"
                    class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:ring-0"
                >



                <p class="mt-1 text-xs text-gray-400">
                    Challenge automatically becomes unavailable after this time.
                </p>


            </div>



        </div>









        {{-- INFO CARD --}}
        <div class="rounded-xl bg-blue-50 p-4">


            <div class="flex gap-3">


                <div class="text-blue-500">

                    <i class="ik ik-info"></i>

                </div>




                <div>


                    <h4 class="text-sm font-semibold text-blue-800">
                        Scheduling Behavior
                    </h4>



                    <ul class="mt-2 space-y-1 text-xs text-blue-700">


                        <li>
                            • No start date = available immediately
                        </li>


                        <li>
                            • No end date = stays active permanently
                        </li>


                        <li>
                            • Scheduled challenges can be prepared before publishing
                        </li>


                    </ul>



                </div>



            </div>



        </div>




    </div>



</div>