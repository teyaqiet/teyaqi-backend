@extends('admin.layouts.main')

@section('title', 'System Information')

@section('content')

<div
    class="space-y-6"
    x-data="operationsSystem()"
    x-init="init()"
>
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                System Information
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Server, runtime, Laravel and resource information.
            </p>
        </div>

```
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
                    Unable to load system information
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
            Loading system information...
        </p>
    </div>
</template>

<template x-if="loaded">

    <div class="space-y-6">

        {{-- Server --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="mb-5 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                    <i class="ik ik-server text-lg"></i>
                </div>

                <div>
                    <h2 class="font-semibold text-gray-800">
                        Server
                    </h2>

                    <p class="text-xs text-gray-500">
                        Host and operating system information
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Operating System
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="display(server.os_family)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        OS Release
                    </p>

                    <p
                        class="mt-1 break-words text-sm font-semibold text-gray-800"
                        x-text="display(server.os_release)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Hostname
                    </p>

                    <p
                        class="mt-1 break-words text-sm font-semibold text-gray-800"
                        x-text="display(server.hostname)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Architecture
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="display(server.architecture)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 md:col-span-2">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Server Software
                    </p>

                    <p
                        class="mt-1 break-words text-sm font-semibold text-gray-800"
                        x-text="display(server.server_software)"
                    ></p>
                </div>

            </div>
        </div>


        {{-- PHP --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="mb-5 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                    <i class="ik ik-code text-lg"></i>
                </div>

                <div>
                    <h2 class="font-semibold text-gray-800">
                        PHP Runtime
                    </h2>

                    <p class="text-xs text-gray-500">
                        Current PHP runtime configuration
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        PHP Version
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="display(php.version)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        SAPI
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="display(php.sapi)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Memory Limit
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="display(php.memory_limit)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Max Execution Time
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="php.max_execution_time !== undefined ? php.max_execution_time + ' seconds' : '—'"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Upload Max Filesize
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="display(php.upload_max_filesize)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        POST Max Size
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="display(php.post_max_size)"
                    ></p>
                </div>

            </div>
        </div>


        {{-- Laravel --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="mb-5 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <i class="ik ik-layers text-lg"></i>
                </div>

                <div>
                    <h2 class="font-semibold text-gray-800">
                        Laravel
                    </h2>

                    <p class="text-xs text-gray-500">
                        Application framework and environment
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Laravel Version
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="display(laravel.version)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Environment
                    </p>

                    <div class="mt-2">
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="environmentClass(laravel.environment)"
                            x-text="display(laravel.environment)"
                        ></span>
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Debug Mode
                    </p>

                    <div class="mt-2">
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="booleanClass(laravel.debug)"
                            x-text="booleanLabel(laravel.debug)"
                        ></span>
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Maintenance Mode
                    </p>

                    <div class="mt-2">
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="laravel.maintenance ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
                            x-text="laravel.maintenance ? 'Enabled' : 'Disabled'"
                        ></span>
                    </div>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Timezone
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="display(laravel.timezone)"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Locale
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-800"
                        x-text="display(laravel.locale)"
                    ></p>
                </div>

            </div>
        </div>


        {{-- Resources --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="mb-5 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i class="ik ik-bar-chart-2 text-lg"></i>
                </div>

                <div>
                    <h2 class="font-semibold text-gray-800">
                        System Resources
                    </h2>

                    <p class="text-xs text-gray-500">
                        Current CPU, memory and disk utilization
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

                {{-- CPU --}}
                <div class="rounded-xl border border-gray-100 p-5">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="ik ik-cpu text-gray-400"></i>

                            <span class="text-sm font-medium text-gray-700">
                                CPU
                            </span>
                        </div>

                        <span
                            class="text-sm font-semibold text-gray-800"
                            x-text="formatPercent(resources.cpu?.usage_percent)"
                        ></span>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full bg-primary-600 transition-all duration-500"
                            :style="`width: ${safePercent(resources.cpu?.usage_percent)}%`"
                        ></div>
                    </div>

                    <div class="mt-3 flex justify-between text-xs text-gray-500">
                        <span>
                            Cores:
                            <span
                                class="font-medium text-gray-700"
                                x-text="display(resources.cpu?.cores)"
                            ></span>
                        </span>

                        <span>
                            Load:
                            <span
                                class="font-medium text-gray-700"
                                x-text="display(resources.cpu?.load_1m)"
                            ></span>
                        </span>
                    </div>

                </div>


                {{-- Memory --}}
                <div class="rounded-xl border border-gray-100 p-5">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="ik ik-database text-gray-400"></i>

                            <span class="text-sm font-medium text-gray-700">
                                Memory
                            </span>
                        </div>

                        <span
                            class="text-sm font-semibold text-gray-800"
                            x-text="formatPercent(resources.memory?.usage_percent)"
                        ></span>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full bg-primary-600 transition-all duration-500"
                            :style="`width: ${safePercent(resources.memory?.usage_percent)}%`"
                        ></div>
                    </div>

                    <div class="mt-3 flex justify-between text-xs text-gray-500">
                        <span>
                            Used:
                            <span
                                class="font-medium text-gray-700"
                                x-text="formatBytes(resources.memory?.used)"
                            ></span>
                        </span>

                        <span>
                            Total:
                            <span
                                class="font-medium text-gray-700"
                                x-text="formatBytes(resources.memory?.total)"
                            ></span>
                        </span>
                    </div>

                </div>


                {{-- Disk --}}
                <div class="rounded-xl border border-gray-100 p-5">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="ik ik-hard-drive text-gray-400"></i>

                            <span class="text-sm font-medium text-gray-700">
                                Disk
                            </span>
                        </div>

                        <span
                            class="text-sm font-semibold text-gray-800"
                            x-text="formatPercent(resources.disk?.usage_percent)"
                        ></span>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full bg-primary-600 transition-all duration-500"
                            :style="`width: ${safePercent(resources.disk?.usage_percent)}%`"
                        ></div>
                    </div>

                    <div class="mt-3 flex justify-between text-xs text-gray-500">
                        <span>
                            Free:
                            <span
                                class="font-medium text-gray-700"
                                x-text="formatBytes(resources.disk?.free)"
                            ></span>
                        </span>

                        <span>
                            Total:
                            <span
                                class="font-medium text-gray-700"
                                x-text="formatBytes(resources.disk?.total)"
                            ></span>
                        </span>
                    </div>

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
```

</div>

<script>
function operationsSystem() {
    return {
        loading: false,
        loaded: false,
        error: null,
        lastUpdated: null,

        server: {},
        php: {},
        laravel: {},
        resources: {},

        async init() {
            await this.refresh();
        },

        async refresh() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('/api/admin/operations/system', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(
                        `System information request failed (${response.status})`
                    );
                }

                const json = await response.json();

                if (!json.success) {
                    throw new Error(
                        json.message || 'Unable to retrieve system information.'
                    );
                }

                const data = json.data || {};

                this.server = data.server || {};
                this.php = data.php || {};
                this.laravel = data.laravel || {};
                this.resources = data.resources || {};

                this.lastUpdated = new Date().toLocaleTimeString();
                this.loaded = true;

            } catch (error) {
                console.error('Operations System error:', error);

                this.error = error?.message ||
                    'An unexpected error occurred while loading system information.';
            } finally {
                this.loading = false;
            }
        },

        display(value) {
            if (
                value === null ||
                value === undefined ||
                value === ''
            ) {
                return '—';
            }

            return value;
        },

        safePercent(value) {
            const number = Number(value);

            if (!Number.isFinite(number)) {
                return 0;
            }

            return Math.min(100, Math.max(0, number));
        },

        formatPercent(value) {
            const number = Number(value);

            if (!Number.isFinite(number)) {
                return '—';
            }

            return `${Math.round(number)}%`;
        },

        formatBytes(value) {
            const number = Number(value);

            if (
                !Number.isFinite(number) ||
                number <= 0
            ) {
                return '—';
            }

            const units = ['B', 'KB', 'MB', 'GB', 'TB'];

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
                size >= 10 || unit === 0 ? 0 : 1
            )} ${units[unit]}`;
        },

        booleanLabel(value) {
            if (value === true) {
                return 'Enabled';
            }

            if (value === false) {
                return 'Disabled';
            }

            return 'Unknown';
        },

        booleanClass(value) {
            if (value === true) {
                return 'bg-yellow-100 text-yellow-700';
            }

            if (value === false) {
                return 'bg-green-100 text-green-700';
            }

            return 'bg-gray-100 text-gray-500';
        },

        environmentClass(environment) {
            switch (String(environment || '').toLowerCase()) {
                case 'production':
                    return 'bg-red-100 text-red-700';

                case 'staging':
                    return 'bg-yellow-100 text-yellow-700';

                case 'local':
                case 'development':
                    return 'bg-blue-100 text-blue-700';

                default:
                    return 'bg-gray-100 text-gray-500';
            }
        },
    };
}
</script>

@endsection
