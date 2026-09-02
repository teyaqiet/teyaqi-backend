@props([
    'title',
    'value',
    'change'=>null,
    'icon'=>'ik ik-bar-chart',
    'color'=>'primary',
    'description'=>null
])


<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">


    <div class="flex items-start justify-between">


        <div>


            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                {{ $title }}
            </p>


            <h3 class="mt-2 text-2xl font-bold text-gray-800">
                {{ $value }}
            </h3>


        </div>



        <div
            class="
            flex h-10 w-10 items-center justify-center rounded-lg
            bg-primary-50 text-primary-600
            "
        >

            <i class="{{ $icon }} text-xl"></i>

        </div>


    </div>





    @if($change)

    <div class="mt-4 flex items-center gap-2">


        <span
            class="
            inline-flex items-center rounded-full
            bg-emerald-50 px-2 py-1
            text-xs font-semibold text-emerald-700
            "
        >

            <i class="ik ik-trending-up mr-1"></i>

            {{ $change }}

        </span>



        @if($description)

        <span class="text-xs text-gray-400">
            {{ $description }}
        </span>

        @endif


    </div>


    @endif



</div>