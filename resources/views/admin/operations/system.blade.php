@extends('admin.layouts.main')

@section('title', 'Processes')

@section('content')

<div
    class="space-y-6"
    x-data="operationsProcesses()"
    x-init="init()"
>
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

```
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            Processes
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Monitor currently running processes on the server.
        </p>
    </div>

    <button
        type="button"
        @click="refresh()"
        :disabled="loading"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
    >
        <i
            class="ik ik-refresh-cw"
            :class="{ 'animate-spin': loading }"
        ></i>

        <span x-text="loading ? 'Refreshing...' : 'Refresh'"></span>
    </button>

</div>


{{-- Error --}}
<template x-if="error">

    <div class="rounded-xl border border-red-200 bg-red-50 p-4">

        <div class="flex items-start gap-3">

            <div class="mt-0.5 text-red-600">
                <i class="ik ik-alert-circle"></i>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-red-800">
                    Unable to load processes
                </h3>

                <p
                    class="mt-1 text-sm text-red-700"
                    x-text="error"
                ></p>
            </div>

        </div>

    </div>

</template>


{{-- Loading --}}
<template x-if="loading && !loaded">

    <div class="rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">

        <i class="ik ik-loader animate-spin text-2xl text-primary-600"></i>

        <p class="mt-3 text-sm text-gray-500">
            Loading processes...
        </p>

    </div>

</template>


<template x-if="loaded">

    <div class="space-y-6">

        {{-- Process Overview --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

            <div class="mb-5 flex items-center gap-3">

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                    <i class="ik ik-cpu text-lg"></i>
                </div>

                <div>
                    <h2 class="font-semibold text-gray-800">
                        Process Overview
                    </h2>

                    <p class="text-xs text-gray-500">
                        Current process activity on the server
                    </p>
                </div>

            </div>


            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                {{-- Total --}}
                <div class="rounded-lg bg-gray-50 p-4">

                    <div class="flex items-center justify-between">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Total Processes
                        </p>

                        <i class="ik ik-list text-gray-400"></i>

                    </div>

                    <p
                        class="mt-2 text-2xl font-semibold text-gray-800"
                        x-text="processes.length"
                    ></p>

                    <p class="mt-1 text-xs text-gray-500">
                        Processes currently detected
                    </p>

                </div>


                {{-- Top CPU --}}
                <div class="rounded-lg bg-gray-50 p-4">

                    <div class="flex items-center justify-between">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Top CPU
                        </p>

                        <i class="ik ik-activity text-gray-400"></i>

                    </div>

                    <p
                        class="mt-2 truncate text-lg font-semibold text-gray-800"
                        x-text="topCpu?.name ?? '—'"
                    ></p>

                    <p
                        class="mt-1 text-xs text-gray-500"
                        x-text="
                            topCpu?.cpu_percent != null
                                ? formatPercent(topCpu.cpu_percent)
                                : 'CPU data unavailable'
                        "
                    ></p>

                </div>


                {{-- Top Memory --}}
                <div class="rounded-lg bg-gray-50 p-4">

                    <div class="flex items-center justify-between">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Top Memory
                        </p>

                        <i class="ik ik-database text-gray-400"></i>

                    </div>

                    <p
                        class="mt-2 truncate text-lg font-semibold text-gray-800"
                        x-text="topMemory?.name ?? '—'"
                    ></p>

                    <p
                        class="mt-1 text-xs text-gray-500"
                        x-text="topMemory ? formatBytes(topMemory.memory_bytes) : '—'"
                    ></p>

                </div>

            </div>

        </div>


        {{-- Running Processes --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

            {{-- Section Header --}}
            <div class="border-b border-gray-100 p-6">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div class="flex items-center gap-3">

                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <i class="ik ik-list text-lg"></i>
                        </div>

                        <div>

                            <h2 class="font-semibold text-gray-800">
                                Running Processes
                            </h2>

                            <p class="text-xs text-gray-500">
                                Processes detected on the current server
                            </p>

                        </div>

                    </div>


                    <div class="flex flex-col gap-2 sm:flex-row">

                        {{-- Search --}}
                        <div class="relative">

                            <i class="ik ik-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>

                            <input
                                type="text"
                                x-model="search"
                                placeholder="Search processes..."
                                class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 sm:w-64"
                            >

                        </div>


                        {{-- Sort --}}
                        <select
                            x-model="sort"
                            @change="refresh()"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                        >
                            <option value="cpu">
                                Sort by CPU
                            </option>

                            <option value="memory">
                                Sort by Memory
                            </option>

                            <option value="name">
                                Sort by Name
                            </option>

                            <option value="pid">
                                Sort by PID
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            {{-- Table --}}
            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-400">
                                Process
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-400">
                                PID
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-400">
                                CPU
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-400">
                                Memory
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-400">
                                User
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-400">
                                Status
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-400">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        <template
                            x-for="process in filteredProcesses"
                            :key="process.pid"
                        >

                            <tr class="transition hover:bg-gray-50">

                                {{-- Process --}}
                                <td class="px-6 py-4">

                                    <div class="flex min-w-0 items-center gap-3">

                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100">
                                            <span
                                                class="text-xs font-semibold text-gray-600"
                                                x-text="process.name ? process.name.charAt(0).toUpperCase() : '?'"
                                            ></span>
                                        </div>

                                        <div class="min-w-0">

                                            <p
                                                class="max-w-xs truncate text-sm font-medium text-gray-800"
                                                x-text="process.name || 'Unknown'"
                                            ></p>

                                            <p
                                                x-show="process.command"
                                                class="mt-0.5 max-w-md truncate font-mono text-xs text-gray-400"
                                                x-text="process.command"
                                            ></p>

                                        </div>

                                    </div>

                                </td>


                                {{-- PID --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    <span
                                        class="font-mono text-xs text-gray-600"
                                        x-text="process.pid ?? '—'"
                                    ></span>

                                </td>


                                {{-- CPU --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    <template x-if="process.cpu_percent != null">

                                        <div class="w-24">

                                            <div class="flex items-center justify-between">

                                                <span
                                                    class="text-xs font-medium text-gray-700"
                                                    x-text="formatPercent(process.cpu_percent)"
                                                ></span>

                                            </div>

                                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100">

                                                <div
                                                    class="h-full rounded-full bg-primary-600 transition-all duration-500"
                                                    :style="`width: ${safePercent(process.cpu_percent)}%`"
                                                ></div>

                                            </div>

                                        </div>

                                    </template>


                                    <template x-if="process.cpu_percent == null">

                                        <span class="text-xs text-gray-400">
                                            —
                                        </span>

                                    </template>

                                </td>


                                {{-- Memory --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    <div>

                                        <span
                                            class="text-sm text-gray-700"
                                            x-text="formatBytes(process.memory_bytes)"
                                        ></span>

                                        <template x-if="process.memory_percent != null">

                                            <span
                                                class="ml-1 text-xs text-gray-400"
                                                x-text="`(${formatPercent(process.memory_percent)})`"
                                            ></span>

                                        </template>

                                    </div>

                                </td>


                                {{-- User --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    <span
                                        class="max-w-[180px] truncate text-sm text-gray-600"
                                        x-text="process.user || '—'"
                                    ></span>

                                </td>


                                {{-- Status --}}
                                <td class="whitespace-nowrap px-6 py-4">

                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700"
                                    >

                                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>

                                        <span
                                            x-text="process.status || 'Running'"
                                        ></span>

                                    </span>

                                </td>


                                {{-- Action --}}
                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <button
                                        type="button"
                                        @click="showProcess(process)"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                                    >

                                        <i class="ik ik-eye"></i>

                                        Details

                                    </button>

                                </td>

                            </tr>

                        </template>


                        {{-- Empty --}}
                        <template x-if="filteredProcesses.length === 0">

                            <tr>

                                <td
                                    colspan="7"
                                    class="px-6 py-12 text-center"
                                >

                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-lg bg-gray-50">

                                        <i class="ik ik-search text-gray-300 text-lg"></i>

                                    </div>

                                    <p class="mt-3 text-sm font-medium text-gray-700">
                                        No processes found
                                    </p>

                                    <p class="mt-1 text-xs text-gray-400">
                                        Try changing your search or refreshing the process list.
                                    </p>

                                </td>

                            </tr>

                        </template>

                    </tbody>

                </table>

            </div>


            {{-- Table Footer --}}
            <div class="border-t border-gray-100 px-6 py-4">

                <div class="flex flex-col gap-2 text-xs text-gray-400 sm:flex-row sm:items-center sm:justify-between">

                    <span>
                        Showing
                        <span
                            class="font-medium text-gray-500"
                            x-text="filteredProcesses.length"
                        ></span>
                        processes
                    </span>

                    <span>
                        Platform:
                        <span
                            class="font-medium text-gray-500"
                            x-text="platform"
                        ></span>

                        <span class="mx-1">•</span>

                        Provider:
                        <span
                            class="font-medium text-gray-500"
                            x-text="provider"
                        ></span>
                    </span>

                </div>

            </div>

        </div>


        {{-- Last Updated --}}
        <div class="flex items-center justify-between text-xs text-gray-400">

            <span>
                Operations Center
            </span>

            <span>
                Last updated:
                <span
                    class="font-medium text-gray-500"
                    x-text="lastUpdated || '—'"
                ></span>
            </span>

        </div>

    </div>

</template>


{{-- Process Details Modal --}}
<div
    x-show="showModal"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    @keydown.escape.window="closeModal()"
>

    <div
        class="fixed inset-0 bg-black/40"
        @click="closeModal()"
    ></div>


    <div class="relative flex min-h-full items-center justify-center p-4">

        <div
            x-show="showModal"
            x-transition
            @click.stop
            class="relative w-full max-w-2xl rounded-2xl bg-white shadow-xl"
        >

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">

                <div>

                    <h3 class="text-lg font-semibold text-gray-800">
                        Process Details
                    </h3>

                    <p
                        class="mt-1 font-mono text-xs text-gray-400"
                        x-text="selectedProcess ? `PID ${selectedProcess.pid}` : ''"
                    ></p>

                </div>


                <button
                    type="button"
                    @click="closeModal()"
                    class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                >
                    <i class="ik ik-x text-lg"></i>
                </button>

            </div>


            {{-- Body --}}
            <div
                x-show="selectedProcess"
                class="max-h-[70vh] space-y-5 overflow-y-auto p-6"
            >

                {{-- Main --}}
                <div class="rounded-xl bg-gray-50 p-5">

                    <div class="flex items-center gap-3">

                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white shadow-sm">

                            <span
                                class="text-sm font-semibold text-gray-600"
                                x-text="
                                    selectedProcess?.name
                                        ? selectedProcess.name.charAt(0).toUpperCase()
                                        : '?'
                                "
                            ></span>

                        </div>

                        <div class="min-w-0">

                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                Process
                            </p>

                            <p
                                class="mt-1 truncate text-lg font-semibold text-gray-800"
                                x-text="selectedProcess?.name || 'Unknown'"
                            ></p>

                        </div>

                    </div>

                </div>


                {{-- Metrics --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                    <div class="rounded-lg bg-gray-50 p-4">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Process ID
                        </p>

                        <p
                            class="mt-1 font-mono text-sm font-semibold text-gray-800"
                            x-text="selectedProcess?.pid ?? '—'"
                        ></p>

                    </div>


                    <div class="rounded-lg bg-gray-50 p-4">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Status
                        </p>

                        <div class="mt-2">

                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">

                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>

                                <span
                                    x-text="selectedProcess?.status || 'Running'"
                                ></span>

                            </span>

                        </div>

                    </div>


                    <div class="rounded-lg bg-gray-50 p-4">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            CPU Usage
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold text-gray-800"
                            x-text="
                                selectedProcess?.cpu_percent != null
                                    ? formatPercent(selectedProcess.cpu_percent)
                                    : 'Unavailable'
                            "
                        ></p>

                    </div>


                    <div class="rounded-lg bg-gray-50 p-4">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Memory Usage
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold text-gray-800"
                            x-text="formatBytes(selectedProcess?.memory_bytes)"
                        ></p>

                        <p
                            x-show="selectedProcess?.memory_percent != null"
                            class="mt-1 text-xs text-gray-400"
                            x-text="formatPercent(selectedProcess?.memory_percent)"
                        ></p>

                    </div>

                </div>


                {{-- Information --}}
                <div class="space-y-4">

                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            User
                        </p>

                        <p
                            class="mt-1 break-words text-sm text-gray-700"
                            x-text="selectedProcess?.user || 'Unavailable'"
                        ></p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Started
                        </p>

                        <p
                            class="mt-1 text-sm text-gray-700"
                            x-text="selectedProcess?.started_at || 'Unavailable'"
                        ></p>

                    </div>


                    <div x-show="selectedProcess?.command">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Command
                        </p>

                        <pre
                            class="mt-1 max-h-40 overflow-auto rounded-lg bg-gray-50 p-4 font-mono text-xs leading-relaxed text-gray-600"
                            x-text="selectedProcess?.command"
                        ></pre>

                    </div>

                </div>

            </div>


            {{-- Footer --}}
            <div class="flex justify-end border-t border-gray-100 px-6 py-4">

                <button
                    type="button"
                    @click="closeModal()"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    Close
                </button>

            </div>

        </div>

    </div>

</div>
```

</div>

<script>
function operationsProcesses() {
    return {
        loading: false,
        loaded: false,
        error: null,
        lastUpdated: null,

        platform: '—',
        provider: '—',

        processes: [],

        search: '',
        sort: 'cpu',

        showModal: false,
        selectedProcess: null,

        async init() {
            await this.refresh();
        },

        async refresh() {
            this.loading = true;
            this.error = null;

            try {
                const params = new URLSearchParams({
                    limit: '500',
                    sort: this.sort,
                    direction: 'desc',
                });

                const response = await fetch(
                    `/api/admin/operations/processes?${params.toString()}`,
                    {
                        method: 'GET',

                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },

                        credentials: 'same-origin',
                    }
                );

                if (!response.ok) {
                    throw new Error(
                        `Process request failed (${response.status})`
                    );
                }

                const json = await response.json();

                if (!json.success) {
                    throw new Error(
                        json.message ||
                        'Unable to retrieve processes.'
                    );
                }

                const data = json.data || {};

                this.platform =
                    data.platform || '—';

                this.provider =
                    data.provider?.provider
                        ? data.provider.provider
                            .split('\\')
                            .pop()
                        : '—';

                this.processes =
                    Array.isArray(data.processes)
                        ? data.processes
                        : [];

                this.lastUpdated =
                    new Date().toLocaleTimeString();

                this.loaded = true;

            } catch (error) {

                console.error(
                    'Operations Processes error:',
                    error
                );

                this.error =
                    error?.message ||
                    'An unexpected error occurred while loading processes.';

            } finally {

                this.loading = false;

            }
        },

        showProcess(process) {
            this.selectedProcess = process;
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.selectedProcess = null;
        },

        safePercent(value) {
            const number = Number(value);

            if (!Number.isFinite(number)) {
                return 0;
            }

            return Math.min(
                100,
                Math.max(0, number)
            );
        },

        formatPercent(value) {
            const number = Number(value);

            if (!Number.isFinite(number)) {
                return '—';
            }

            return `${number.toFixed(1)}%`;
        },

        formatBytes(value) {
            const number = Number(value);

            if (
                !Number.isFinite(number) ||
                number <= 0
            ) {
                return '—';
            }

            const units = [
                'B',
                'KB',
                'MB',
                'GB',
                'TB'
            ];

            let size = number;
            let unit = 0;

            while (
                size >= 1024 &&
                unit < units.length - 1
            ) {
                size /= 1024;
                unit++;
            }

            return `${size.toFixed(
                size >= 10 || unit === 0
                    ? 0
                    : 1
            )} ${units[unit]}`;
        },

        get filteredProcesses() {

            const query =
                this.search
                    .trim()
                    .toLowerCase();

            if (!query) {
                return this.processes;
            }

            return this.processes.filter(process => {

                return (
                    String(process.name ?? '')
                        .toLowerCase()
                        .includes(query)

                    ||

                    String(process.pid ?? '')
                        .includes(query)

                    ||

                    String(process.user ?? '')
                        .toLowerCase()
                        .includes(query)
                );

            });
        },

        get topCpu() {

            return [...this.processes]
                .filter(
                    process =>
                        process.cpu_percent != null
                )
                .sort(
                    (a, b) =>
                        Number(b.cpu_percent) -
                        Number(a.cpu_percent)
                )[0] || null;

        },

        get topMemory() {

            return [...this.processes]
                .sort(
                    (a, b) =>
                        Number(b.memory_bytes ?? 0) -
                        Number(a.memory_bytes ?? 0)
                )[0] || null;

        },
    };
}
</script>

@endsection
