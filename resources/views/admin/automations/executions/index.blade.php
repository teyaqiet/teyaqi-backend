@extends('admin.layouts.main')

@section('content')

<div class="p-6">

    <div class="flex items-center justify-between mb-6">

        <div>
            <a
                href="{{ route('admin.automations.builder', $automation) }}"
                class="text-xs text-gray-400 hover:text-gray-700"
            >
                ← Back to Builder
            </a>

            <h1 class="mt-2 text-xl font-semibold text-gray-900">
                Executions
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                {{ $automation->name }}
            </p>
        </div>

    </div>

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

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead class="border-b border-gray-200 bg-gray-50">

                    <tr>
                        <th class="px-5 py-3 text-left font-medium text-gray-500">
                            Execution
                        </th>

                        <th class="px-5 py-3 text-left font-medium text-gray-500">
                            Trigger
                        </th>

                        <th class="px-5 py-3 text-left font-medium text-gray-500">
                            Status
                        </th>

                        <th class="px-5 py-3 text-left font-medium text-gray-500">
                            Started
                        </th>

                        <th class="px-5 py-3 text-left font-medium text-gray-500">
                            Duration
                        </th>

                        <th class="px-5 py-3"></th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-gray-100">

                    @forelse($executions as $execution)

                        <tr class="hover:bg-gray-50">

                            <td class="px-5 py-4">

                                <div class="font-mono text-xs text-gray-700">
                                    {{ Str::limit($execution->execution_id, 18) }}
                                </div>

                                <div class="text-[11px] text-gray-400 mt-1">
                                    #{{ $execution->id }}
                                </div>

                            </td>

                            <td class="px-5 py-4">

                                <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                                    {{ $execution->trigger_type }}
                                </span>

                            </td>

                            <td class="px-5 py-4">

                                @php
                                    $statusClasses = [
                                        'completed' => 'bg-green-50 text-green-700',
                                        'failed' => 'bg-red-50 text-red-700',
                                        'running' => 'bg-blue-50 text-blue-700',
                                        'pending' => 'bg-yellow-50 text-yellow-700',
                                    ];
                                @endphp

                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClasses[$execution->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($execution->status) }}
                                </span>

                            </td>

                            <td class="px-5 py-4 text-gray-500">
                                {{ $execution->started_at?->format('M d, Y H:i:s') ?? '—' }}
                            </td>

                            <td class="px-5 py-4 text-gray-500">
                                {{ $execution->duration_ms !== null
                                    ? $execution->duration_ms . ' ms'
                                    : '—'
                                }}
                            </td>

                            <td class="px-5 py-4 text-right">

                                <a
                                    href="{{ route('admin.automations.executions.show', [$automation, $execution]) }}"
                                    class="text-xs font-medium text-gray-900 hover:underline"
                                >
                                    View
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="6"
                                class="px-5 py-12 text-center text-sm text-gray-400"
                            >
                                No executions yet.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <div class="mt-5">
        {{ $executions->links() }}
    </div>

</div>

@endsection