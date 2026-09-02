@extends('admin.layouts.main')

@section('title', 'Operations Center')

@section('content')

<div
    class="space-y-6"
    x-data="operationsDashboard()"
    x-init="init()"
>

```
{{-- HEADER --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                <i class="ik ik-cpu text-xl"></i>
            </div>

            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    Operations Center
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Monitor and manage the Teyaqi platform.
                </p>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">

        {{-- ENVIRONMENT --}}
        <span
            class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700"
        >
            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
            {{ strtoupper(config('operations.environment', config('app.env'))) }}
        </span>

        {{-- REFRESH --}}
        <button
            type="button"
            @click="refresh()"
            :disabled="loading"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
        >
            <i
                class="ik ik-refresh-cw"
                :class="{ 'animate-spin': loading }"
            ></i>

            <span x-text="loading ? 'Refreshing...' : 'Refresh'"></span>
        </button>

    </div>
</div>


{{-- GLOBAL STATUS --}}
<div
    class="rounded-xl border p-5"
    :class="health.status === 'healthy'
        ? 'border-green-200 bg-green-50'
        : 'border-red-200 bg-red-50'"
>
    <div class="flex items-start gap-4">

        <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
            :class="health.status === 'healthy'
                ? 'bg-green-100 text-green-600'
                : 'bg-red-100 text-red-600'"
        >
            <i
                class="ik text-xl"
                :class="health.status === 'healthy'
                    ? 'ik-check-circle'
                    : 'ik-alert-circle'"
            ></i>
        </div>

        <div class="flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h2
                    class="font-semibold"
                    :class="health.status === 'healthy'
                        ? 'text-green-800'
                        : 'text-red-800'"
                    x-text="health.status === 'healthy'
                        ? 'All systems operational'
                        : 'System health requires attention'"
                ></h2>

                <span
                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase"
                    :class="health.status === 'healthy'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-700'"
                    x-text="health.status || 'unknown'"
                ></span>
            </div>

            <p
                class="mt-1 text-sm"
                :class="health.status === 'healthy'
                    ? 'text-green-700'
                    : 'text-red-700'"
            >
                <span x-show="health.status === 'healthy'">
                    Database, cache, storage, and queue health checks are passing.
                </span>

                <span x-show="health.status !== 'healthy'">
                    One or more platform components require investigation.
                </span>
            </p>
        </div>

        <div class="hidden text-right sm:block">
            <p class="text-xs text-gray-400">
                Last checked
            </p>

            <p
                class="mt-1 text-sm font-medium text-gray-700"
                x-text="lastUpdated || '—'"
            ></p>
        </div>

    </div>
</div>


{{-- SYSTEM HEALTH --}}
<div>
    <div class="mb-4">
        <h2 class="font-semibold text-gray-800">
            System Health
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Current status of critical platform services.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- DATABASE --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i class="ik ik-database text-lg"></i>
                </div>

                <span
                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                    :class="statusClass(health.database?.status)"
                    x-text="formatStatus(health.database?.status)"
                ></span>
            </div>

            <div class="mt-4">
                <h3 class="font-semibold text-gray-800">
                    Database
                </h3>

                <p class="mt-1 text-xs text-gray-500">
                    MySQL connection and query health.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                <span class="text-xs text-gray-400">
                    Latency
                </span>

                <span class="text-sm font-semibold text-gray-700">
                    <span x-text="health.database?.latency_ms ?? '—'"></span>
                    <span x-show="health.database?.latency_ms !== undefined">ms</span>
                </span>
            </div>
        </div>


        {{-- CACHE --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                    <i class="ik ik-zap text-lg"></i>
                </div>

                <span
                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                    :class="statusClass(health.cache?.status)"
                    x-text="formatStatus(health.cache?.status)"
                ></span>
            </div>

            <div class="mt-4">
                <h3 class="font-semibold text-gray-800">
                    Cache
                </h3>

                <p class="mt-1 text-xs text-gray-500">
                    Cache storage read/write health.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                <span class="text-xs text-gray-400">
                    Driver
                </span>

                <span
                    class="text-sm font-semibold text-gray-700"
                    x-text="cacheDriver || '—'"
                ></span>
            </div>
        </div>


        {{-- STORAGE --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i class="ik ik-hard-drive text-lg"></i>
                </div>

                <span
                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                    :class="statusClass(health.storage?.status)"
                    x-text="formatStatus(health.storage?.status)"
                ></span>
            </div>

            <div class="mt-4">
                <h3 class="font-semibold text-gray-800">
                    Storage
                </h3>

                <p class="mt-1 text-xs text-gray-500">
                    Application storage health.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                <span class="text-xs text-gray-400">
                    Writable
                </span>

                <span
                    class="text-sm font-semibold"
                    :class="health.storage?.status === 'healthy'
                        ? 'text-green-600'
                        : 'text-red-600'"
                    x-text="health.storage?.status === 'healthy' ? 'Yes' : 'No'"
                ></span>
            </div>
        </div>


        {{-- QUEUE --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-start justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <i class="ik ik-layers text-lg"></i>
                </div>

                <span
                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                    :class="statusClass(health.queue?.status)"
                    x-text="formatStatus(health.queue?.status)"
                ></span>
            </div>

            <div class="mt-4">
                <h3 class="font-semibold text-gray-800">
                    Queue
                </h3>

                <p class="mt-1 text-xs text-gray-500">
                    Background job configuration.
                </p>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                <span class="text-xs text-gray-400">
                    Driver
                </span>

                <span
                    class="text-sm font-semibold text-gray-700"
                    x-text="queueDriver || '—'"
                ></span>
            </div>
        </div>

    </div>
</div>


{{-- MAIN GRID --}}
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- RESOURCES --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100 xl:col-span-2">

        <div class="mb-6 flex items-start justify-between">
            <div>
                <h2 class="font-semibold text-gray-800">
                    System Resources
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Current server resource utilization.
                </p>
            </div>

            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-50 text-gray-500">
                <i class="ik ik-server text-lg"></i>
            </div>
        </div>

        <div class="space-y-6">

            {{-- CPU --}}
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ik ik-cpu text-gray-400"></i>
                        <span class="text-sm font-medium text-gray-700">
                            CPU
                        </span>
                    </div>

                    <span class="text-sm font-semibold text-gray-800">
                        <span x-text="formatPercent(resources.cpu?.usage_percent)"></span>
                    </span>
                </div>

                <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                    <div
                        class="h-full rounded-full bg-primary-500 transition-all duration-500"
                        :style="`width: ${safePercent(resources.cpu?.usage_percent)}%`"
                    ></div>
                </div>

                <p class="mt-2 text-xs text-gray-400">
                    <span x-text="resources.cpu?.cores ?? '—'"></span>
                    CPU cores
                </p>
            </div>


            {{-- MEMORY --}}
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ik ik-bar-chart-2 text-gray-400"></i>
                        <span class="text-sm font-medium text-gray-700">
                            Memory
                        </span>
                    </div>

                    <span class="text-sm font-semibold text-gray-800">
                        <span x-text="formatPercent(resources.memory?.usage_percent)"></span>
                    </span>
                </div>

                <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                    <div
                        class="h-full rounded-full bg-primary-500 transition-all duration-500"
                        :style="`width: ${safePercent(resources.memory?.usage_percent)}%`"
                    ></div>
                </div>

                <div class="mt-2 flex justify-between text-xs text-gray-400">
                    <span x-text="formatBytes(resources.memory?.used_bytes)"></span>
                    <span x-text="formatBytes(resources.memory?.total_bytes)"></span>
                </div>
            </div>


            {{-- DISK --}}
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ik ik-hard-drive text-gray-400"></i>
                        <span class="text-sm font-medium text-gray-700">
                            Disk
                        </span>
                    </div>

                    <span class="text-sm font-semibold text-gray-800">
                        <span x-text="formatPercent(resources.disk?.usage_percent)"></span>
                    </span>
                </div>

                <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                    <div
                        class="h-full rounded-full bg-primary-500 transition-all duration-500"
                        :style="`width: ${safePercent(resources.disk?.usage_percent)}%`"
                    ></div>
                </div>

                <div class="mt-2 flex justify-between text-xs text-gray-400">
                    <span x-text="formatBytes(resources.disk?.free_bytes) + ' free'"></span>
                    <span x-text="formatBytes(resources.disk?.total_bytes)"></span>
                </div>
            </div>

        </div>
    </div>


    {{-- APPLICATION --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

        <div class="mb-6 flex items-start justify-between">
            <div>
                <h2 class="font-semibold text-gray-800">
                    Application
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Runtime and environment information.
                </p>
            </div>

            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-50 text-gray-500">
                <i class="ik ik-code text-lg"></i>
            </div>
        </div>

        <div class="space-y-4 text-sm">

            <div class="flex items-center justify-between">
                <span class="text-gray-500">
                    Application
                </span>

                <span
                    class="font-medium text-gray-800"
                    x-text="application.name || '—'"
                ></span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-500">
                    Environment
                </span>

                <span
                    class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                    x-text="application.environment || '—'"
                ></span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-500">
                    Laravel
                </span>

                <span
                    class="font-medium text-gray-800"
                    x-text="application.laravel || '—'"
                ></span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-500">
                    PHP
                </span>

                <span
                    class="font-medium text-gray-800"
                    x-text="application.php || '—'"
                ></span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-500">
                    Debug Mode
                </span>

                <span
                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="application.debug
                        ? 'bg-red-100 text-red-700'
                        : 'bg-green-100 text-green-700'"
                    x-text="application.debug ? 'Enabled' : 'Disabled'"
                ></span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-500">
                    Maintenance
                </span>

                <span
                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="application.maintenance
                        ? 'bg-red-100 text-red-700'
                        : 'bg-green-100 text-green-700'"
                    x-text="application.maintenance ? 'Active' : 'Off'"
                ></span>
            </div>

        </div>
    </div>

</div>


{{-- QUICK ACTIONS --}}
<div>
    <div class="mb-4">
        <h2 class="font-semibold text-gray-800">
            Operations
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Access operational tools and monitoring areas.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

        {{-- SYSTEM --}}
        <a
            href="{{ route('admin.operations.system') }}"
            class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-start justify-between">

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                    <i class="ik ik-server text-lg"></i>
                </div>

                <i class="ik ik-arrow-right text-gray-300 transition group-hover:translate-x-1 group-hover:text-primary-500"></i>

            </div>

            <h3 class="mt-4 font-semibold text-gray-800">
                System Information
            </h3>

            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                View detailed server, PHP, Laravel, CPU, memory, and disk information.
            </p>
        </a>


        {{-- AUDIT LOGS --}}
        <a
            href="{{ route('admin.operations.audit-logs') }}"
            class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-start justify-between">

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                    <i class="ik ik-activity text-lg"></i>
                </div>

                <i class="ik ik-arrow-right text-gray-300 transition group-hover:translate-x-1 group-hover:text-purple-500"></i>

            </div>

            <h3 class="mt-4 font-semibold text-gray-800">
                Audit Logs
            </h3>

            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                Review operational actions, system events, status, and administrative activity.
            </p>
        </a>

        {{-- QUEUE MONITOR --}}
        <a
            href="{{ route('admin.operations.queue') }}"
            class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-start justify-between">

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-50 text-gray-400">
                    <i class="ik ik-list text-lg"></i>
                </div>

                <i class="ik ik-arrow-right text-gray-300 transition group-hover:translate-x-1 group-hover:text-purple-500"></i>

            </div>

            <h3 class="mt-4 font-semibold text-gray-800">
                Queue Monitor
            </h3>

            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                Monitor and manage background job processing.
            </p>
        </a>



        {{-- CACHE MONITOR --}}
        <a
            href="{{ route('admin.operations.cache') }}"
            class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-start justify-between">

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-50 text-gray-400">
                    <i class="ik ik-database text-lg"></i>
                </div>

                <i class="ik ik-arrow-right text-gray-300 transition group-hover:translate-x-1 group-hover:text-purple-500"></i>

            </div>

            <h3 class="mt-4 font-semibold text-gray-800">
                Cache Monitor
            </h3>

            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                Monitor and manage cache performance and usage.
            </p>
        </a>


        {{-- DATABASE MONITOR --}}
        <a
            href="{{ route('admin.operations.database') }}"
            class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-start justify-between">

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-gray-400">
                    <i class="ik ik-search text-lg"></i>
                </div>

                <i class="ik ik-arrow-right text-gray-300 transition group-hover:translate-x-1 group-hover:text-purple-500"></i>

            </div>

            <h3 class="mt-4 font-semibold text-gray-800">
                Database Monitor
            </h3>

            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                Monitor and manage database performance and usage.
            </p>
        </a>

        {{-- BACKUPS --}}
        <a
            href="{{ route('admin.operations.backups') }}"
            class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-start justify-between">

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-gray-400">
                    <i class="ik ik-archive text-lg"></i>
                </div>

                <i class="ik ik-arrow-right text-gray-300 transition group-hover:translate-x-1 group-hover:text-purple-500"></i>

            </div>

            <h3 class="mt-4 font-semibold text-gray-800">
                Backups
            </h3>

            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                Create and manage database backups.
            </p>
        </a>


        {{-- DEPLOYMENTS --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex items-start justify-between">

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-50 text-gray-400">
                    <i class="ik ik-rocket text-lg"></i>
                </div>

                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                    Coming Soon
                </span>

            </div>

            <h3 class="mt-4 font-semibold text-gray-800">
                Deployments
            </h3>

            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                Deploy releases, monitor deployment status, and manage rollbacks.
            </p>

        </div>

    </div>
</div>


{{-- ERROR --}}
<div
    x-show="error"
    x-cloak
    class="rounded-xl border border-red-200 bg-red-50 p-4"
>
    <div class="flex items-start gap-3">

        <i class="ik ik-alert-triangle mt-0.5 text-red-500"></i>

        <div>
            <h3 class="text-sm font-semibold text-red-800">
                Unable to load Operations data
            </h3>

            <p
                class="mt-1 text-sm text-red-700"
                x-text="error"
            ></p>
        </div>

    </div>
</div>
```

</div>

<script>
function operationsDashboard() {
    return {
        loading: false,
        error: null,
        lastUpdated: null,

        health: {
            status: 'unknown',
            database: {},
            cache: {},
            storage: {},
            queue: {},
        },

        resources: {
            cpu: {},
            memory: {},
            disk: {},
        },

        application: {},

        cacheDriver: null,
        queueDriver: null,

        async init() {
            await this.refresh();
        },

        async refresh() {
            this.loading = true;
            this.error = null;

            try {
                const [healthResponse, systemResponse] = await Promise.all([
                    fetch('/api/admin/operations/health', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }),

                    fetch('/api/admin/operations/system', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }),
                ]);

                if (!healthResponse.ok) {
                    throw new Error(
                        `Health request failed (${healthResponse.status})`
                    );
                }

                if (!systemResponse.ok) {
                    throw new Error(
                        `System request failed (${systemResponse.status})`
                    );
                }

                const healthJson = await healthResponse.json();
                const systemJson = await systemResponse.json();

                if (!healthJson.success) {
                    throw new Error(
                        healthJson.message || 'Health check failed.'
                    );
                }

                if (!systemJson.success) {
                    throw new Error(
                        systemJson.message || 'System information request failed.'
                    );
                }

                this.health = healthJson.data || {};
                this.resources = healthJson.data?.resources || systemJson.data?.resources || {};

                /*
                 * HealthService currently returns application information
                 * while SystemService provides detailed system information.
                 */
                this.application = healthJson.data?.application || {};

                this.cacheDriver =
                    healthJson.data?.cache?.driver ||
                    systemJson.data?.cache?.driver ||
                    null;

                this.queueDriver =
                    healthJson.data?.queue?.driver ||
                    systemJson.data?.queue?.driver ||
                    null;

                /*
                 * If resources are returned from SystemService, prefer them.
                 */
                if (systemJson.data?.resources) {
                    this.resources = systemJson.data.resources;
                }

                this.lastUpdated = new Date().toLocaleTimeString();

            } catch (error) {
                console.error('Operations Center error:', error);

                this.error =
                    error?.message ||
                    'An unexpected error occurred while loading Operations Center.';

            } finally {
                this.loading = false;
            }
        },

        formatStatus(status) {
            if (!status) {
                return 'Unknown';
            }

            return status.charAt(0).toUpperCase() + status.slice(1);
        },

        statusClass(status) {
            switch (status) {
                case 'healthy':
                    return 'bg-green-100 text-green-700';

                case 'unhealthy':
                case 'failed':
                    return 'bg-red-100 text-red-700';

                case 'warning':
                    return 'bg-yellow-100 text-yellow-700';

                default:
                    return 'bg-gray-100 text-gray-500';
            }
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

            if (!Number.isFinite(number) || number <= 0) {
                return '—';
            }

            const units = ['B', 'KB', 'MB', 'GB', 'TB'];

            let size = number;
            let unit = 0;

            while (size >= 1024 && unit < units.length - 1) {
                size /= 1024;
                unit++;
            }

            return `${size.toFixed(size >= 10 || unit === 0 ? 0 : 1)} ${units[unit]}`;
        },
    };
}
</script>

@endsection
