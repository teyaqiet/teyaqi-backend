<div
    draggable="true"
    @dragstart="startDrag('{{ $type }}')"
    @dragend="endDrag()"
    class="group p-3 bg-white border border-gray-200 rounded-xl cursor-grab hover:border-gray-400 hover:shadow-sm active:cursor-grabbing transition"
>

    <div class="flex items-center gap-3">

        <div
            class="w-9 h-9 rounded-lg {{ $color }} flex items-center justify-center text-lg"
        >
            {{ $icon }}
        </div>

        <div class="min-w-0">

            <div class="text-sm font-medium text-gray-800">
                {{ $name }}
            </div>

            <div class="text-xs text-gray-400">
                {{ $description }}
            </div>

        </div>

    </div>

</div>