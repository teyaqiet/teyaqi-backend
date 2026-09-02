@props([
    'title',
    'subtitle'=>null,
    'height'=>'300'
])


<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">


    <div class="mb-5">

        <h3 class="font-semibold text-gray-800">
            {{ $title }}
        </h3>


        @if($subtitle)

            <p class="mt-1 text-xs text-gray-500">
                {{ $subtitle }}
            </p>

        @endif


    </div>



    <div
        style="height: {{ $height }}px"
    >

        {{ $slot }}

    </div>


</div>