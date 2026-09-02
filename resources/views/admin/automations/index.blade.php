@extends('admin.layouts.main')

@section('content')

<div class="p-6">

    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                Automations
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Build and manage automated player workflows.
            </p>
        </div>

        <a
            href="{{ route('admin.automations.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800"
        >
            <span class="text-base leading-none">+</span>
            New Automation
        </a>

    </div>


    {{-- =========================================================
         STATS
    ========================================================== --}}

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Total --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Total
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ $stats['total'] }}
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-600">
                    ⚡
                </div>

            </div>

        </div>


        {{-- Active --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Active
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ $stats['active'] }}
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    ✓
                </div>

            </div>

        </div>


        {{-- Draft --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Draft
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ $stats['draft'] }}
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-50 text-yellow-600">
                    ◌
                </div>

            </div>

        </div>


        {{-- Paused --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Paused
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ $stats['paused'] }}
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-500">
                    ⏸
                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         FLASH MESSAGES
    ========================================================== --}}

    @if(session('success'))

        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>

    @endif


    @if(session('error'))

        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>

    @endif


    {{-- =========================================================
         AUTOMATIONS TABLE
    ========================================================== --}}

    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- Table Header --}}
        <div class="border-b border-gray-200 px-5 py-4">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h2 class="text-sm font-semibold text-gray-900">
                        All Automations
                    </h2>

                    <p class="mt-1 text-xs text-gray-400">
                        Manage your automation workflows.
                    </p>
                </div>

            </div>

        </div>


        {{-- Table --}}
        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="border-b border-gray-200 bg-gray-50">

                    <tr>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            Automation
                        </th>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            Status
                        </th>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            Nodes
                        </th>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            Runs
                        </th>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            Success
                        </th>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            Failed
                        </th>

                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            Last Run
                        </th>

                        <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">

                    @forelse($automations as $automation)

                        @php

                            $statusStyles = match ($automation->status) {

                                'active' => [
                                    'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'dot' => 'bg-emerald-500',
                                ],

                                'paused' => [
                                    'badge' => 'bg-gray-100 text-gray-600 border-gray-200',
                                    'dot' => 'bg-gray-400',
                                ],

                                default => [
                                    'badge' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                    'dot' => 'bg-yellow-500',
                                ],

                            };

                            $latestExecution = $automation->executions->first();

                            $successRate = $automation->total_runs > 0
                                ? round(
                                    (
                                        $automation->successful_runs /
                                        $automation->total_runs
                                    ) * 100,
                                    1
                                )
                                : null;

                        @endphp


                        <tr class="transition hover:bg-gray-50">

                            {{-- Automation --}}
                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-700">
                                        ⚡
                                    </div>

                                    <div class="min-w-0">

                                        <div class="truncate text-sm font-semibold text-gray-900">
                                            {{ $automation->name }}
                                        </div>

                                        <div class="mt-1 max-w-xs truncate text-xs text-gray-400">
                                            {{ $automation->description ?: 'No description' }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- Status --}}
                            <td class="px-5 py-4">

                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $statusStyles['badge'] }}">

                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusStyles['dot'] }}"></span>

                                    {{ ucfirst($automation->status) }}

                                </span>

                            </td>


                            {{-- Nodes --}}
                            <td class="px-5 py-4">

                                <span class="text-sm font-medium text-gray-700">
                                    {{ $automation->nodes_count }}
                                </span>

                            </td>


                            {{-- Runs --}}
                            <td class="px-5 py-4">

                                <div class="text-sm font-medium text-gray-700">
                                    {{ number_format($automation->total_runs) }}
                                </div>

                                @if($successRate !== null)

                                    <div class="mt-1 text-[11px] text-gray-400">
                                        {{ $successRate }}% success
                                    </div>

                                @endif

                            </td>


                            {{-- Successful --}}
                            <td class="px-5 py-4">

                                <span class="text-sm font-medium text-emerald-600">
                                    {{ number_format($automation->successful_runs) }}
                                </span>

                            </td>


                            {{-- Failed --}}
                            <td class="px-5 py-4">

                                <span class="text-sm font-medium {{ $automation->failed_runs > 0 ? 'text-red-600' : 'text-gray-500' }}">
                                    {{ number_format($automation->failed_runs) }}
                                </span>

                            </td>


                            {{-- Last Run --}}
                            <td class="px-5 py-4">

                                @if($latestExecution)

                                    <div class="text-xs text-gray-700">
                                        {{ $latestExecution->created_at?->diffForHumans() }}
                                    </div>

                                    <div class="mt-1 text-[11px] text-gray-400">
                                        {{ ucfirst($latestExecution->status) }}
                                    </div>

                                @elseif($automation->last_run_at)

                                    <div class="text-xs text-gray-700">
                                        {{ $automation->last_run_at->diffForHumans() }}
                                    </div>

                                @else

                                    <span class="text-xs text-gray-400">
                                        Never
                                    </span>

                                @endif

                            </td>


                            {{-- Actions --}}
                            <td class="px-5 py-4">

                                <div class="flex items-center justify-end gap-2">

                                    <a
                                        href="{{ route('admin.automations.builder', $automation) }}"
                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Builder
                                    </a>

                                    <a
                                        href="{{ route('admin.automations.executions.index', $automation) }}"
                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Executions
                                    </a>

                                    <a
                                        href="{{ route('admin.automations.edit', $automation) }}"
                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Edit
                                    </a>

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="px-5 py-16 text-center"
                            >

                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-200 bg-gray-50 text-xl text-gray-400">
                                    ⚡
                                </div>

                                <h3 class="mt-4 text-sm font-semibold text-gray-800">
                                    No automations yet
                                </h3>

                                <p class="mt-1 text-xs text-gray-400">
                                    Create your first automation workflow.
                                </p>

                                <a
                                    href="{{ route('admin.automations.create') }}"
                                    class="mt-4 inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800"
                                >
                                    Create Automation
                                </a>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if($automations->hasPages())

            <div class="border-t border-gray-200 px-5 py-4">
                {{ $automations->links() }}
            </div>

        @endif

    </div>

</div>

@endsection