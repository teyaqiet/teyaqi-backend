@extends('admin.layouts.main')

@section('content')

<div
    x-data="operationsProcesses()"
    x-init="init()"
    class="space-y-6"
>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100">
                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 17.25v1.5a2.25 2.25 0 002.25 2.25h1.5A2.25 2.25 0 0015 18.75v-1.5M9 6.75v-1.5A2.25 2.25 0 0111.25 3h1.5A2.25 2.25 0 0115 5.25v1.5M17.25 9h1.5A2.25 2.25 0 0121 11.25v1.5A2.25 2.25 0 0118.75 15h-1.5M6.75 9h-1.5A2.25 2.25 0 003 11.25v1.5A2.25 2.25 0 005.25 15h1.5M8.25 12h7.5"
                        />
                    </svg>
                </div>

                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Processes
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Monitor running processes on the server.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">

            <span
                x-show="!loading"
                class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                Live
            </span>

            <button
                type="button"
                @click="refresh()"
                :disabled="loading"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg
                    class="h-4 w-4"
                    :class="{ 'animate-spin': loading }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h5M20 20v-5h-5M5.07 9A7 7 0 0118.93 15M18.93 15A7 7 0 015.07 9"
                    />
                </svg>

                Refresh
            </button>

        </div>
    </div>


    {{-- Error --}}
    <div
        x-show="error"
        x-cloak
        class="rounded-xl border border-red-200 bg-red-50 p-4"
    >
        <div class="flex gap-3">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008M10.29 3.86l-7.1 12.25A1.5 1.5 0 004.49 18.4h15.02a1.5 1.5 0 001.3-2.25l-7.1-12.25a1.5 1.5 0 00-2.6 0z"/>
            </svg>

            <div>
                <p class="text-sm font-medium text-red-800">
                    Failed to load processes
                </p>

                <p
                    class="mt-1 text-sm text-red-700"
                    x-text="error"
                ></p>
            </div>
        </div>
    </div>


    {{-- Loading --}}
    <div
        x-show="loading && !loaded"
        x-cloak
        class="rounded-xl border border-gray-200 bg-white p-12 text-center"
    >
        <svg class="mx-auto h-7 w-7 animate-spin text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.07 9A7 7 0 0118.93 15M18.93 15A7 7 0 015.07 9"/>
        </svg>

        <p class="mt-3 text-sm text-gray-500">
            Loading processes...
        </p>
    </div>


    {{-- Summary --}}
    <div
        x-show="loaded"
        x-cloak
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >

        {{-- Total --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Total Processes
                    </p>

                    <p
                        class="mt-2 text-2xl font-semibold text-gray-900"
                        x-text="processes.length"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-100 p-2.5">
                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17.25v1.5a2.25 2.25 0 002.25 2.25h1.5A2.25 2.25 0 0015 18.75v-1.5M9 6.75v-1.5A2.25 2.25 0 0111.25 3h1.5A2.25 2.25 0 0115 5.25v1.5M17.25 9h1.5A2.25 2.25 0 0121 11.25v1.5A2.25 2.25 0 0118.75 15h-1.5M6.75 9h-1.5A2.25 2.25 0 003 11.25v1.5A2.25 2.25 0 005.25 15h1.5M8.25 12h7.5"/></svg>
                </div>
            </div>
        </div>


        {{-- Top CPU --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">
                        Top CPU
                    </p>

                    <p
                        class="mt-2 truncate text-lg font-semibold text-gray-900"
                        x-text="topCpu?.name ?? '—'"
                    ></p>

                    <p
                        class="mt-1 text-xs text-gray-500"
                        x-text="topCpu?.cpu_percent != null ? formatPercent(topCpu.cpu_percent) : 'CPU data unavailable'"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-100 p-2.5">
                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>


        {{-- Top Memory --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">
                        Top Memory
                    </p>

                    <p
                        class="mt-2 truncate text-lg font-semibold text-gray-900"
                        x-text="topMemory?.name ?? '—'"
                    ></p>

                    <p
                        class="mt-1 text-xs text-gray-500"
                        x-text="topMemory?.memory_percent != null ? formatPercent(topMemory.memory_percent) : formatBytes(topMemory?.memory_bytes)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-100 p-2.5">
                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 7h10v10H7V7z"/>
                    </svg>
                </div>
            </div>
        </div>


        {{-- Platform --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Platform
                    </p>

                    <p
                        class="mt-2 text-lg font-semibold text-gray-900"
                        x-text="platform"
                    ></p>

                    <p
                        class="mt-1 text-xs text-gray-500"
                        x-text="provider"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-100 p-2.5">
                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5zM8 21h8M12 17v4"/>
                    </svg>
                </div>
            </div>
        </div>

    </div>


    {{-- Process Table --}}
    <div
        x-show="loaded"
        x-cloak
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
    >

        {{-- Table Header --}}
        <div class="border-b border-gray-200 p-4">

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

                <div>
                    <h2 class="text-base font-semibold text-gray-900">
                        Running Processes
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        <span x-text="filteredProcesses.length"></span>
                        processes shown
                    </p>
                </div>


                <div class="flex flex-col gap-2 sm:flex-row">

                    {{-- Search --}}
                    <div class="relative">
                        <svg
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"
                            />
                        </svg>

                        <input
                            type="text"
                            x-model="search"
                            placeholder="Search processes..."
                            class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-gray-400 focus:ring-2 focus:ring-gray-100 sm:w-64"
                        >
                    </div>


                    {{-- Sort --}}
                    <select
                        x-model="sort"
                        @change="refresh()"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 outline-none focus:border-gray-400"
                    >
                        <option value="cpu">Sort by CPU</option>
                        <option value="memory">Sort by Memory</option>
                        <option value="name">Sort by Name</option>
                        <option value="pid">Sort by PID</option>
                    </select>

                </div>

            </div>

        </div>


        {{-- Table --}}
        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-gray-200">

                <thead class="bg-gray-50">
                    <tr>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Process
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            PID
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            CPU
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Memory
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            User
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>

                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Action
                        </th>

                    </tr>
                </thead>


                <tbody class="divide-y divide-gray-100 bg-white">

                    <template x-for="process in filteredProcesses" :key="process.pid">

                        <tr class="transition hover:bg-gray-50">

                            {{-- Process --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100">
                                        <span
                                            class="text-xs font-semibold text-gray-600"
                                            x-text="process.name?.charAt(0)?.toUpperCase() ?? '?'"
                                        ></span>
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="max-w-xs truncate text-sm font-medium text-gray-900"
                                            x-text="process.name ?? 'Unknown'"
                                        ></p>

                                        <p
                                            x-show="process.command"
                                            class="max-w-md truncate text-xs text-gray-400"
                                            x-text="process.command"
                                        ></p>
                                    </div>

                                </div>

                            </td>


                            {{-- PID --}}
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="font-mono text-xs text-gray-600"
                                    x-text="process.pid"
                                ></span>
                            </td>


                            {{-- CPU --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                <template x-if="process.cpu_percent != null">
                                    <div class="w-24">
                                        <div class="flex items-center justify-between text-xs">
                                            <span
                                                class="font-medium text-gray-700"
                                                x-text="formatPercent(process.cpu_percent)"
                                            ></span>
                                        </div>

                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-100">
                                            <div
                                                class="h-full rounded-full bg-gray-400"
                                                :style="`width: ${Math.min(process.cpu_percent, 100)}%`"
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
                            <td class="whitespace-nowrap px-5 py-4">

                                <template x-if="process.memory_percent != null">
                                    <div>
                                        <span
                                            class="text-sm text-gray-700"
                                            x-text="formatPercent(process.memory_percent)"
                                        ></span>
                                    </div>
                                </template>

                                <template x-if="process.memory_percent == null">
                                    <span
                                        class="text-sm text-gray-700"
                                        x-text="formatBytes(process.memory_bytes)"
                                    ></span>
                                </template>

                            </td>


                            {{-- User --}}
                            <td class="whitespace-nowrap px-5 py-4">
                                <span
                                    class="text-sm text-gray-600"
                                    x-text="process.user ?? '—'"
                                ></span>
                            </td>


                            {{-- Status --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                    <span x-text="process.status ?? 'running'"></span>
                                </span>

                            </td>


                            {{-- Action --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right">

                                <button
                                    type="button"
                                    @click="showProcess(process)"
                                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                                >
                                    Details
                                </button>

                            </td>

                        </tr>

                    </template>


                    {{-- Empty --}}
                    <tr x-show="filteredProcesses.length === 0">

                        <td colspan="7" class="px-5 py-12 text-center">

                            <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17.25v1.5a2.25 2.25 0 002.25 2.25h1.5A2.25 2.25 0 0015 18.75v-1.5M9 6.75v-1.5A2.25 2.25 0 0111.25 3h1.5A2.25 2.25 0 0115 5.25v1.5"/>
                            </svg>

                            <p class="mt-3 text-sm font-medium text-gray-700">
                                No processes found
                            </p>

                            <p class="mt-1 text-xs text-gray-400">
                                Try changing your search.
                            </p>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>


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
                class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl"
            >

                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">

                    <div>
                        <h3 class="text-base font-semibold text-gray-900">
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
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/>
                        </svg>
                    </button>

                </div>


                {{-- Modal Body --}}
                <div class="space-y-4 p-6" x-show="selectedProcess">

                    <div class="rounded-xl bg-gray-50 p-4">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Process
                        </p>

                        <p
                            class="mt-1 text-lg font-semibold text-gray-900"
                            x-text="selectedProcess?.name ?? 'Unknown'"
                        ></p>

                    </div>


                    <div class="grid grid-cols-2 gap-3">

                        <div class="rounded-xl border border-gray-200 p-4">
                            <p class="text-xs text-gray-400">PID</p>
                            <p
                                class="mt-1 font-mono text-sm font-medium text-gray-900"
                                x-text="selectedProcess?.pid ?? '—'"
                            ></p>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4">
                            <p class="text-xs text-gray-400">Status</p>
                            <p
                                class="mt-1 text-sm font-medium text-gray-900"
                                x-text="selectedProcess?.status ?? '—'"
                            ></p>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4">
                            <p class="text-xs text-gray-400">CPU</p>
                            <p
                                class="mt-1 text-sm font-medium text-gray-900"
                                x-text="selectedProcess?.cpu_percent != null ? formatPercent(selectedProcess.cpu_percent) : 'Unavailable'"
                            ></p>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4">
                            <p class="text-xs text-gray-400">Memory</p>
                            <p
                                class="mt-1 text-sm font-medium text-gray-900"
                                x-text="selectedProcess?.memory_percent != null ? formatPercent(selectedProcess.memory_percent) : formatBytes(selectedProcess?.memory_bytes)"
                            ></p>
                        </div>

                    </div>


                    <div class="space-y-3">

                        <div>
                            <p class="text-xs text-gray-400">
                                User
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-700"
                                x-text="selectedProcess?.user ?? 'Unavailable'"
                            ></p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400">
                                Started
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-700"
                                x-text="selectedProcess?.started_at ?? 'Unavailable'"
                            ></p>
                        </div>

                        <div x-show="selectedProcess?.command">
                            <p class="text-xs text-gray-400">
                                Command
                            </p>

                            <pre
                                class="mt-1 max-h-32 overflow-auto rounded-lg bg-gray-50 p-3 font-mono text-xs text-gray-600"
                                x-text="selectedProcess?.command"
                            ></pre>
                        </div>

                    </div>

                </div>


                {{-- Modal Footer --}}
                <div class="flex justify-end border-t border-gray-200 px-6 py-4">

                    <button
                        type="button"
                        @click="closeModal()"
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Close
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>


<script>
function operationsProcesses() {
    return {
        loading: false,
        loaded: false,
        error: null,

        platform: '—',
        provider: '—',

        processes: [],

        search: '',
        sort: 'cpu',

        showModal: false,
        selectedProcess: null,

        async init() {
            await this.load();
        },

        async load() {
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
                        headers: {
                            'Accept': 'application/json',
                        },
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Failed to load processes.'
                    );
                }

                this.platform = result.data?.platform ?? '—';

                this.provider =
                    result.data?.provider?.provider
                    ? result.data.provider.provider.split('\\').pop()
                    : '—';

                this.processes =
                    Array.isArray(result.data?.processes)
                        ? result.data.processes
                        : [];

                this.loaded = true;

            } catch (error) {
                console.error(error);

                this.error =
                    error?.message ||
                    'Unable to load processes.';
            } finally {
                this.loading = false;
            }
        },

        async refresh() {
            await this.load();
        },

        showProcess(process) {
            this.selectedProcess = process;
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.selectedProcess = null;
        },

        formatPercent(value) {
            if (value === null || value === undefined) {
                return '—';
            }

            const number = Number(value);

            if (!Number.isFinite(number)) {
                return '—';
            }

            return `${number.toFixed(1)}%`;
        },

        formatBytes(bytes) {
            if (bytes === null || bytes === undefined) {
                return '—';
            }

            const number = Number(bytes);

            if (!Number.isFinite(number) || number <= 0) {
                return '—';
            }

            const units = [
                'B',
                'KB',
                'MB',
                'GB',
                'TB'
            ];

            let value = number;
            let index = 0;

            while (value >= 1024 && index < units.length - 1) {
                value /= 1024;
                index++;
            }

            return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
        },

        get filteredProcesses() {
            const query = this.search.trim().toLowerCase();

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
                .filter(p => p.cpu_percent != null)
                .sort(
                    (a, b) =>
                        Number(b.cpu_percent) -
                        Number(a.cpu_percent)
                )[0] ?? null;
        },

        get topMemory() {
            return [...this.processes]
                .sort((a, b) => {
                    if (
                        a.memory_percent != null &&
                        b.memory_percent != null
                    ) {
                        return (
                            Number(b.memory_percent) -
                            Number(a.memory_percent)
                        );
                    }

                    return (
                        Number(b.memory_bytes ?? 0) -
                        Number(a.memory_bytes ?? 0)
                    );
                })[0] ?? null;
        },
    };
}
</script>

@endsection