@extends('admin.layouts.main')

@section('content')

<div class="p-6">

    {{-- Header --}}
    <div class="mb-6 flex items-start justify-between gap-4">

        <div>
            <a
                href="{{ route('admin.automations.executions.index', $automation) }}"
                class="text-xs text-gray-400 hover:text-gray-700"
            >
                ← Back to Executions
            </a>

            <div class="mt-3">
                <h1 class="text-xl font-semibold text-gray-900">
                    Execution #{{ $execution->id }}
                </h1>

                <p class="mt-1 font-mono text-xs text-gray-400">
                    {{ $execution->execution_id }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">

            @if($execution->status === 'failed')
                <form
                    method="POST"
                    action="{{ route('admin.automations.executions.retry', [$automation, $execution]) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                    >
                        Retry Execution
                    </button>
                </form>
            @endif

        </div>

    </div>


    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif


    <div class="grid grid-cols-1 gap-5 xl:grid-cols-4">

        {{-- =========================================================
             LEFT SIDEBAR
        ========================================================== --}}

        <div class="xl:col-span-1 space-y-5">

            {{-- Summary --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between">

                    <h2 class="text-sm font-semibold text-gray-900">
                        Execution Summary
                    </h2>

                    @php
                        $statusClasses = [
                            'completed' => 'bg-green-50 text-green-700 border-green-200',
                            'failed' => 'bg-red-50 text-red-700 border-red-200',
                            'running' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'pending' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                        ];
                    @endphp

                    <span class="rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $statusClasses[$execution->status] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">
                        {{ ucfirst($execution->status) }}
                    </span>

                </div>


                <dl class="mt-5 space-y-4">

                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gray-400">
                            Trigger
                        </dt>

                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $execution->trigger_type }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gray-400">
                            Started
                        </dt>

                        <dd class="mt-1 text-sm text-gray-700">
                            {{ $execution->started_at?->format('M d, Y H:i:s') ?? '—' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gray-400">
                            Completed
                        </dt>

                        <dd class="mt-1 text-sm text-gray-700">
                            {{ $execution->completed_at?->format('M d, Y H:i:s') ?? '—' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gray-400">
                            Duration
                        </dt>

                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $execution->duration_ms !== null
                                ? $execution->duration_ms . ' ms'
                                : '—'
                            }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-gray-400">
                            Nodes Executed
                        </dt>

                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $execution->nodeExecutions->count() }}
                        </dd>
                    </div>

                </dl>


                @if($execution->error_message)

                    <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4">

                        <div class="text-xs font-semibold text-red-700">
                            Execution Error
                        </div>

                        <div class="mt-1 text-xs leading-5 text-red-600">
                            {{ $execution->error_message }}
                        </div>

                    </div>

                @endif

            </div>


            {{-- Trigger Data --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <h2 class="text-sm font-semibold text-gray-900">
                    Trigger Data
                </h2>

                <pre class="mt-4 max-h-72 overflow-auto rounded-lg bg-gray-950 p-4 text-[11px] leading-5 text-gray-200">{{ json_encode($execution->trigger_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

            </div>


            {{-- Context --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <h2 class="text-sm font-semibold text-gray-900">
                    Execution Context
                </h2>

                <pre class="mt-4 max-h-[450px] overflow-auto rounded-lg bg-gray-950 p-4 text-[11px] leading-5 text-gray-200">{{ json_encode($execution->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

            </div>

        </div>


        {{-- =========================================================
             EXECUTION TIMELINE
        ========================================================== --}}

        <div class="xl:col-span-3">

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <div class="flex items-center justify-between">

                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">
                            Execution Timeline
                        </h2>

                        <p class="mt-1 text-xs text-gray-400">
                            Follow the path taken through the automation.
                        </p>
                    </div>

                    <div class="text-xs text-gray-400">
                        {{ $execution->nodeExecutions->count() }} executed nodes
                    </div>

                </div>


                <div class="relative mt-8">

                    {{-- Timeline line --}}
                    <div class="absolute left-5 top-3 bottom-3 w-px bg-gray-200"></div>


                    <div class="space-y-5">

                        @forelse($execution->nodeExecutions as $log)

                            @php
                                $nodeStatus = match ($log->status) {
                                    'completed' => [
                                        'dot' => 'bg-emerald-500 ring-emerald-100',
                                        'border' => 'border-emerald-200',
                                        'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    ],
                                    'failed' => [
                                        'dot' => 'bg-red-500 ring-red-100',
                                        'border' => 'border-red-200',
                                        'badge' => 'bg-red-50 text-red-700 border-red-200',
                                    ],
                                    'running' => [
                                        'dot' => 'bg-blue-500 ring-blue-100',
                                        'border' => 'border-blue-200',
                                        'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    ],
                                    default => [
                                        'dot' => 'bg-gray-400 ring-gray-100',
                                        'border' => 'border-gray-200',
                                        'badge' => 'bg-gray-50 text-gray-600 border-gray-200',
                                    ],
                                };
                            @endphp


                            <div class="relative pl-12">

                                {{-- Step marker --}}
                                <div class="absolute left-0 top-2 flex h-10 w-10 items-center justify-center rounded-full bg-white">

                                    <div class="flex h-7 w-7 items-center justify-center rounded-full ring-4 {{ $nodeStatus['dot'] }} text-[10px] font-bold text-white">

                                        {{ $loop->iteration }}

                                    </div>

                                </div>


                                {{-- Node card --}}
                                <div class="rounded-xl border {{ $nodeStatus['border'] }} bg-white p-5">

                                    <div class="flex items-start justify-between gap-4">

                                        <div class="min-w-0 flex-1">

                                            <div class="flex items-center gap-2">

                                                <h3 class="truncate text-sm font-semibold text-gray-900">
                                                    {{ $log->node?->name ?? 'Node #' . $log->node_id }}
                                                </h3>

                                                <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500">
                                                    {{ $log->node?->component ?? 'unknown' }}
                                                </span>

                                            </div>

                                            <div class="mt-1 text-xs text-gray-400">
                                                Node ID: {{ $log->node_id }}
                                            </div>

                                        </div>


                                        <span class="shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $nodeStatus['badge'] }}">
                                            {{ ucfirst($log->status) }}
                                        </span>

                                    </div>


                                    {{-- Execution metadata --}}
                                    <div class="mt-4 grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-3 md:grid-cols-4">

                                        <div>
                                            <div class="text-[10px] uppercase tracking-wide text-gray-400">
                                                Started
                                            </div>

                                            <div class="mt-1 text-xs font-medium text-gray-700">
                                                {{ $log->started_at?->format('H:i:s.v') ?? '—' }}
                                            </div>
                                        </div>


                                        <div>
                                            <div class="text-[10px] uppercase tracking-wide text-gray-400">
                                                Completed
                                            </div>

                                            <div class="mt-1 text-xs font-medium text-gray-700">
                                                {{ $log->completed_at?->format('H:i:s.v') ?? '—' }}
                                            </div>
                                        </div>


                                        <div>
                                            <div class="text-[10px] uppercase tracking-wide text-gray-400">
                                                Duration
                                            </div>

                                            <div class="mt-1 text-xs font-medium text-gray-700">
                                                {{ $log->duration_ms !== null
                                                    ? $log->duration_ms . ' ms'
                                                    : '—'
                                                }}
                                            </div>
                                        </div>


                                        <div>
                                            <div class="text-[10px] uppercase tracking-wide text-gray-400">
                                                Status
                                            </div>

                                            <div class="mt-1 text-xs font-medium text-gray-700">
                                                {{ ucfirst($log->status) }}
                                            </div>
                                        </div>

                                    </div>


                                    {{-- Input --}}
                                    @if($log->input)

                                        <details class="mt-4 group">

                                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50">

                                                <span>
                                                    Input
                                                </span>

                                                <span class="text-gray-400 group-open:rotate-180 transition">
                                                    ↓
                                                </span>

                                            </summary>

                                            <pre class="mt-2 max-h-80 overflow-auto rounded-lg bg-gray-950 p-4 text-[11px] leading-5 text-gray-200">{{ json_encode($log->input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                                        </details>

                                    @endif


                                    {{-- Output --}}
                                    @if($log->output)

                                        <details class="mt-3 group">

                                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50">

                                                <span>
                                                    Output
                                                </span>

                                                <span class="text-gray-400 group-open:rotate-180 transition">
                                                    ↓
                                                </span>

                                            </summary>

                                            <pre class="mt-2 max-h-80 overflow-auto rounded-lg bg-gray-950 p-4 text-[11px] leading-5 text-gray-200">{{ json_encode($log->output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                                        </details>

                                    @endif


                                    {{-- Error --}}
                                    @if($log->error_message)

                                        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4">

                                            <div class="text-xs font-semibold text-red-700">
                                                Node Error
                                            </div>

                                            <div class="mt-1 text-xs leading-5 text-red-600">
                                                {{ $log->error_message }}
                                            </div>

                                        </div>

                                    @endif

                                </div>

                            </div>

                        @empty

                            <div class="rounded-xl border border-dashed border-gray-200 p-12 text-center">

                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-400">
                                    —
                                </div>

                                <div class="mt-3 text-sm font-medium text-gray-700">
                                    No node execution records
                                </div>

                                <div class="mt-1 text-xs text-gray-400">
                                    This execution did not produce node logs.
                                </div>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection