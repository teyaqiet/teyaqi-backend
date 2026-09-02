@extends('admin.layouts.main')

@section('title', $automation->name . ' — Automation Builder')

@section('content')

@php

    /*
    |--------------------------------------------------------------------------
    | Builder Nodes
    |--------------------------------------------------------------------------
    */

    $builderNodes =
        $automation->nodes
            ->map(function ($node) {

                $component =
                    $node->component;

                $defaultName =
                    match ($component) {

                        'trigger' =>
                            'Trigger',

                        'condition' =>
                            'Condition',

                        'telegram_message' =>
                            'Telegram Message',

                        'delay' =>
                            'Delay',

                        'end' =>
                            'End',

                        default =>
                            'Node',

                    };


                $config =
                    $node->config;


                if (
                    is_string($config)
                ) {

                    $config =
                        json_decode(
                            $config,
                            true
                        );

                }


                if (
                    !is_array($config)
                ) {

                    $config = [];

                }


                return [

                    'id' =>
                        $node->id,

                    'type' =>
                        $component,

                    'name' =>
                        $node->name
                        ?: $defaultName,

                    'description' =>
                        $node->description
                        ?? '',

                    'enabled' =>
                        (bool) $node->enabled,

                    'position' => [

                        'x' =>
                            (float)
                            ($node->position_x ?? 300),

                        'y' =>
                            (float)
                            ($node->position_y ?? 100),

                    ],

                    'config' =>
                        $config,

                ];

            })
            ->values();


    /*
    |--------------------------------------------------------------------------
    | Builder Connections
    |--------------------------------------------------------------------------
    */

    $builderConnections =
        $automation->connections
            ->map(function ($connection) {

                return [

                    'id' =>
                        $connection->id,

                    'source_node_id' =>
                        $connection->source_node_id,

                    'target_node_id' =>
                        $connection->target_node_id,

                    'source_handle' =>
                        $connection->source_handle
                        ?? 'output',

                    'target_handle' =>
                        $connection->target_handle
                        ?? 'input',

                ];

            })
            ->values();

@endphp


{{-- ============================================================
     BUILDER
============================================================= --}}

<div
    x-data="automationBuilder()"
    class="flex h-[calc(100vh-64px)] flex-col overflow-hidden bg-gray-50"
>

    {{-- Header --}}

    @include(
        'admin.automations.builder.header'
    )


    <div class="flex min-h-0 flex-1 overflow-hidden">

        {{-- Node Library --}}

        @include(
            'admin.automations.builder.library'
        )


        {{-- Canvas --}}

        @include(
            'admin.automations.builder.canvas'
        )


        {{-- Configuration Panel --}}

        @include(
            'admin.automations.builder.config-panel'
        )

    </div>

</div>


{{-- ============================================================
     AUTOMATION BUILDER MODULES
============================================================= --}}

{{-- Node creation, deletion, selection and configuration --}}
@include(
    'admin.automations.builder.scripts.nodes'
)

{{-- Canvas and node movement --}}
@include(
    'admin.automations.builder.scripts.movement'
)

{{-- Connection creation and deletion --}}
@include(
    'admin.automations.builder.scripts.connections'
)

{{-- Workflow persistence --}}
@include(
    'admin.automations.builder.scripts.save'
)

{{-- Condition configuration --}}
@include(
    'admin.automations.builder.scripts.conditions'
)

{{-- Telegram media and buttons --}}
@include(
    'admin.automations.builder.scripts.media'
)

{{-- Core builder state and shared helpers --}}
@include(
    'admin.automations.builder.scripts.builder'
)

@endsection