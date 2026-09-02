<aside
    class="w-64 bg-white border-r border-gray-200 shrink-0 overflow-y-auto z-20"
>

    <div class="p-5 border-b border-gray-200">

        <h2 class="text-sm font-semibold text-gray-900">
            Node Library
        </h2>

        <p class="text-xs text-gray-400 mt-1">
            Drag a component onto the canvas.
        </p>

    </div>


    <div class="p-4 space-y-6">

        {{-- Triggers --}}

        <div>

            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">
                Triggers
            </div>

            @include(
                'admin.automations.builder.library-node',
                [
                    'type' => 'trigger',
                    'name' => 'Trigger',
                    'description' => 'Start workflow',
                    'icon' => '⚡',
                    'color' => 'bg-yellow-50',
                ]
            )

        </div>


        {{-- Logic --}}

        <div>

            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">
                Logic
            </div>

            @include(
                'admin.automations.builder.library-node',
                [
                    'type' => 'condition',
                    'name' => 'Condition',
                    'description' => 'Check a value',
                    'icon' => '◇',
                    'color' => 'bg-purple-50',
                ]
            )

        </div>


        {{-- Actions --}}

        <div>

            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">
                Actions
            </div>

            @include(
                'admin.automations.builder.library-node',
                [
                    'type' => 'telegram_message',
                    'name' => 'Telegram Message',
                    'description' => 'Send message',
                    'icon' => '✈',
                    'color' => 'bg-blue-50',
                ]
            )

        </div>


        {{-- Flow --}}

        <div>

            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">
                Flow Control
            </div>

            <div class="space-y-2">

                @include(
                    'admin.automations.builder.library-node',
                    [
                        'type' => 'delay',
                        'name' => 'Delay',
                        'description' => 'Wait before continuing',
                        'icon' => '⏱',
                        'color' => 'bg-orange-50',
                    ]
                )

                @include(
                    'admin.automations.builder.library-node',
                    [
                        'type' => 'end',
                        'name' => 'End',
                        'description' => 'Finish workflow',
                        'icon' => '■',
                        'color' => 'bg-gray-100',
                    ]
                )

            </div>

        </div>

    </div>

</aside>