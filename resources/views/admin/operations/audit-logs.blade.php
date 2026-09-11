@extends('admin.layouts.main')

@section('title', 'Audit Logs')

@section('content')

<div
    class="space-y-6"
    x-data="operationsAuditLogs()"
    x-init="init()"
>
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                Audit Logs
            </h1>


        <p class="mt-1 text-sm text-gray-500">
            Track activity and changes made through the Operations Center.
        </p>
    </div>

    <button
        type="button"
        @click="loadLogs()"
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


{{-- Filters --}}
<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

    <div class="mb-5 flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
            <i class="ik ik-filter text-lg"></i>
        </div>

        <div>
            <h2 class="font-semibold text-gray-800">
                Filters
            </h2>

            <p class="text-xs text-gray-500">
                Find specific operations quickly.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">

        {{-- Search --}}
        <div class="lg:col-span-2">
            <label class="mb-1.5 block text-xs font-medium text-gray-600">
                Search
            </label>

            <div class="relative">
                <i class="ik ik-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>

                <input
                    type="text"
                    x-model="filters.search"
                    @keydown.enter="applyFilters()"
                    placeholder="Search action or description..."
                    class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
                >
            </div>
        </div>


        {{-- Module --}}
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">
                Module
            </label>

            <select
                x-model="filters.module"
                @change="applyFilters()"
                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
            >
                <option value="">All modules</option>
                <option value="system">System</option>
                <option value="database">Database</option>
                <option value="cache">Cache</option>
                <option value="queue">Queue</option>
                <option value="backup">Backup</option>
                <option value="deployment">Deployment</option>
            </select>
        </div>


        {{-- Status --}}
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">
                Status
            </label>

            <select
                x-model="filters.status"
                @change="applyFilters()"
                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
            >
                <option value="">All statuses</option>
                <option value="success">Success</option>
                <option value="failed">Failed</option>
                <option value="warning">Warning</option>
            </select>
        </div>


        {{-- Environment --}}
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">
                Environment
            </label>

            <select
                x-model="filters.environment"
                @change="applyFilters()"
                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100"
            >
                <option value="">All environments</option>
                <option value="local">Local</option>
                <option value="staging">Staging</option>
                <option value="production">Production</option>
            </select>
        </div>

    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">

        <button
            type="button"
            @click="clearFilters()"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
        >
            <i class="ik ik-x"></i>
            Clear filters
        </button>

        <div class="text-xs text-gray-400">
            <span x-text="pagination.total || 0"></span>
            log(s)
        </div>

    </div>
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
                    Unable to load audit logs
                </h3>

                <p
                    class="mt-1 text-sm text-red-700"
                    x-text="error"
                ></p>
            </div>

        </div>
    </div>
</template>


{{-- Logs --}}
<div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

    {{-- Loading --}}
    <template x-if="loading && logs.length === 0">
        <div class="p-10 text-center">
            <i class="ik ik-loader animate-spin text-2xl text-primary-600"></i>

            <p class="mt-3 text-sm text-gray-500">
                Loading audit logs...
            </p>
        </div>
    </template>


    {{-- Empty --}}
    <template x-if="!loading && logs.length === 0 && !error">
        <div class="p-12 text-center">

            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                <i class="ik ik-file-text text-xl"></i>
            </div>

            <h3 class="mt-4 text-sm font-semibold text-gray-800">
                No audit logs found
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                Try changing your filters or perform an operation first.
            </p>

        </div>
    </template>


    {{-- Table --}}
    <template x-if="logs.length > 0">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-gray-100">

                <thead class="bg-gray-50">
                    <tr>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Operation
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Module
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            User
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Environment
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Status
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Time
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Action
                        </th>

                    </tr>
                </thead>


                <tbody class="divide-y divide-gray-100 bg-white">

                    <template x-for="log in logs" :key="log.id">

                        <tr class="transition hover:bg-gray-50">

                            {{-- Operation --}}
                            <td class="px-6 py-4">

                                <div>
                                    <p
                                        class="text-sm font-semibold text-gray-800"
                                        x-text="log.action || '—'"
                                    ></p>

                                    <p
                                        class="mt-0.5 max-w-xs truncate text-xs text-gray-500"
                                        x-text="log.description || 'No description'"
                                    ></p>
                                </div>

                            </td>


                            {{-- Module --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <span
                                    class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600"
                                    x-text="log.module || '—'"
                                ></span>

                            </td>


                            {{-- User --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <template x-if="log.user">

                                    <div>
                                        <p
                                            class="text-sm font-medium text-gray-700"
                                            x-text="log.user.name || '—'"
                                        ></p>

                                        <p
                                            class="text-xs text-gray-400"
                                            x-text="log.user.email || ''"
                                        ></p>
                                    </div>

                                </template>

                                <template x-if="!log.user">
                                    <span class="text-sm text-gray-400">
                                        System
                                    </span>
                                </template>

                            </td>


                            {{-- Environment --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="environmentClass(log.environment)"
                                    x-text="log.environment || '—'"
                                ></span>

                            </td>


                            {{-- Status --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="statusClass(log.status)"
                                >

                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-current"
                                    ></span>

                                    <span x-text="formatStatus(log.status)"></span>

                                </span>

                            </td>


                            {{-- Time --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <p
                                    class="text-sm text-gray-700"
                                    x-text="formatDate(log.created_at)"
                                ></p>

                                <p
                                    class="mt-0.5 text-xs text-gray-400"
                                    x-text="formatRelative(log.created_at)"
                                ></p>

                            </td>


                            {{-- Action --}}
                            <td class="whitespace-nowrap px-6 py-4 text-right">

                                <button
                                    type="button"
                                    @click="showLog(log.id)"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-800"
                                >
                                    <i class="ik ik-eye"></i>
                                    View
                                </button>

                            </td>

                        </tr>

                    </template>

                </tbody>

            </table>

        </div>

    </template>


    {{-- Pagination --}}
    <template x-if="pagination.last_page > 1">

        <div class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

            <p class="text-xs text-gray-500">
                Showing
                <span
                    class="font-medium text-gray-700"
                    x-text="pagination.from || 0"
                ></span>
                to
                <span
                    class="font-medium text-gray-700"
                    x-text="pagination.to || 0"
                ></span>
                of
                <span
                    class="font-medium text-gray-700"
                    x-text="pagination.total || 0"
                ></span>
            </p>

            <div class="flex items-center gap-1">

                <button
                    type="button"
                    @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1 || loading"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <i class="ik ik-chevron-left"></i>
                </button>

                <template x-for="page in visiblePages()" :key="page">

                    <button
                        type="button"
                        @click="changePage(page)"
                        :class="page === pagination.current_page
                            ? 'bg-primary-600 text-white'
                            : 'border border-gray-200 text-gray-600 hover:bg-gray-50'"
                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-xs font-medium transition"
                        x-text="page"
                    ></button>

                </template>

                <button
                    type="button"
                    @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page || loading"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <i class="ik ik-chevron-right"></i>
                </button>

            </div>

        </div>

    </template>

</div>


{{-- Detail Modal --}}
<template x-if="selectedLog">

    <div
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @keydown.escape.window="closeLog()"
    >

        <div
            class="absolute inset-0 bg-black/40"
            @click="closeLog()"
        ></div>


        <div class="relative max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl">

            {{-- Modal Header --}}
            <div class="flex items-start justify-between border-b border-gray-100 px-6 py-5">

                <div>
                    <div class="flex items-center gap-2">

                        <h2 class="text-lg font-semibold text-gray-800">
                            Operation Details
                        </h2>

                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="statusClass(selectedLog.status)"
                            x-text="formatStatus(selectedLog.status)"
                        ></span>

                    </div>

                    <p
                        class="mt-1 text-xs text-gray-500"
                        x-text="selectedLog.action || '—'"
                    ></p>
                </div>

                <button
                    type="button"
                    @click="closeLog()"
                    class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                >
                    <i class="ik ik-x"></i>
                </button>

            </div>


            {{-- Modal Body --}}
            <div class="max-h-[70vh] overflow-y-auto p-6">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Module
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold text-gray-800"
                            x-text="selectedLog.module || '—'"
                        ></p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Environment
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold text-gray-800"
                            x-text="selectedLog.environment || '—'"
                        ></p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            User
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold text-gray-800"
                            x-text="selectedLog.user?.name || 'System'"
                        ></p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            IP Address
                        </p>

                        <p
                            class="mt-1 break-all text-sm font-semibold text-gray-800"
                            x-text="selectedLog.ip_address || '—'"
                        ></p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 sm:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Date
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold text-gray-800"
                            x-text="formatDate(selectedLog.created_at)"
                        ></p>
                    </div>

                </div>


                {{-- Description --}}
                <div class="mt-5">

                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">
                        Description
                    </p>

                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p
                            class="whitespace-pre-wrap text-sm text-gray-700"
                            x-text="selectedLog.description || 'No description provided.'"
                        ></p>
                    </div>

                </div>


                {{-- Metadata --}}
                <div class="mt-5">

                    <div class="mb-2 flex items-center justify-between">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Metadata
                        </p>

                        <span class="text-xs text-gray-400">
                            JSON
                        </span>

                    </div>

                    <pre
                        class="max-h-72 overflow-auto rounded-lg bg-gray-900 p-4 text-xs leading-5 text-gray-100"
                        x-text="formatMetadata(selectedLog.metadata)"
                    ></pre>

                </div>


                {{-- User Agent --}}
                <template x-if="selectedLog.user_agent">

                    <div class="mt-5">

                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">
                            User Agent
                        </p>

                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">

                            <p
                                class="break-all text-xs leading-5 text-gray-600"
                                x-text="selectedLog.user_agent"
                            ></p>

                        </div>

                    </div>

                </template>

            </div>


            {{-- Modal Footer --}}
            <div class="flex justify-end border-t border-gray-100 px-6 py-4">

                <button
                    type="button"
                    @click="closeLog()"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                >
                    Close
                </button>

            </div>

        </div>

    </div>

</template>

</div>

<script>
function operationsAuditLogs() {
    return {
        loading: false,
        error: null,

        logs: [],
        selectedLog: null,

        filters: {
            search: '',
            module: '',
            action: '',
            status: '',
            environment: '',
        },

        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 25,
            total: 0,
            from: 0,
            to: 0,
        },

        async init() {
            await this.loadLogs();
        },

        async loadLogs(page = 1) {
            this.loading = true;
            this.error = null;

            try {
                const params = new URLSearchParams();

                params.set('page', page);
                params.set('per_page', this.pagination.per_page);

                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        params.set(key, value);
                    }
                });

                const response = await fetch(
                    `/api/admin/operations/audit-logs?${params.toString()}`,
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
                        `Audit logs request failed (${response.status})`
                    );
                }

                const json = await response.json();

                if (!json.success) {
                    throw new Error(
                        json.message || 'Unable to load audit logs.'
                    );
                }

                const data = json.data || {};

                this.logs = data.data || [];

                this.pagination = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    per_page: data.per_page || 25,
                    total: data.total || 0,
                    from: data.from || 0,
                    to: data.to || 0,
                };

            } catch (error) {
                console.error('Operations Audit Logs error:', error);

                this.error = error?.message ||
                    'An unexpected error occurred while loading audit logs.';
            } finally {
                this.loading = false;
            }
        },

        async applyFilters() {
            await this.loadLogs(1);
        },

        async clearFilters() {
            this.filters = {
                search: '',
                module: '',
                action: '',
                status: '',
                environment: '',
            };

            await this.loadLogs(1);
        },

        async changePage(page) {
            if (
                page < 1 ||
                page > this.pagination.last_page ||
                page === this.pagination.current_page
            ) {
                return;
            }

            await this.loadLogs(page);
        },

        async showLog(id) {
            this.error = null;

            try {
                const response = await fetch(
                    `/api/admin/operations/audit-logs/${id}`,
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
                        `Audit log request failed (${response.status})`
                    );
                }

                const json = await response.json();

                if (!json.success) {
                    throw new Error(
                        json.message || 'Unable to load audit log.'
                    );
                }

                this.selectedLog = json.data || null;

            } catch (error) {
                console.error('Audit log detail error:', error);

                this.error = error?.message ||
                    'Unable to load the selected audit log.';
            }
        },

        closeLog() {
            this.selectedLog = null;
        },

        formatStatus(status) {
            if (!status) {
                return 'Unknown';
            }

            return String(status).charAt(0).toUpperCase() +
                String(status).slice(1);
        },

        statusClass(status) {
            switch (String(status || '').toLowerCase()) {
                case 'success':
                    return 'bg-green-100 text-green-700';

                case 'failed':
                    return 'bg-red-100 text-red-700';

                case 'warning':
                    return 'bg-yellow-100 text-yellow-700';

                default:
                    return 'bg-gray-100 text-gray-500';
            }
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

        formatDate(value) {
            if (!value) {
                return '—';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleString();
        },

        formatRelative(value) {
            if (!value) {
                return '';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return '';
            }

            const seconds = Math.floor(
                (Date.now() - date.getTime()) / 1000
            );

            if (seconds < 60) {
                return 'Just now';
            }

            const minutes = Math.floor(seconds / 60);

            if (minutes < 60) {
                return `${minutes} min ago`;
            }

            const hours = Math.floor(minutes / 60);

            if (hours < 24) {
                return `${hours} hr ago`;
            }

            const days = Math.floor(hours / 24);

            if (days < 30) {
                return `${days} day${days === 1 ? '' : 's'} ago`;
            }

            return date.toLocaleDateString();
        },

        formatMetadata(metadata) {
            if (
                metadata === null ||
                metadata === undefined ||
                metadata === ''
            ) {
                return '{}';
            }

            try {
                return JSON.stringify(metadata, null, 2);
            } catch (error) {
                return String(metadata);
            }
        },

        visiblePages() {
            const current = Number(this.pagination.current_page || 1);
            const last = Number(this.pagination.last_page || 1);

            if (last <= 5) {
                return Array.from(
                    { length: last },
                    (_, index) => index + 1
                );
            }

            let start = Math.max(1, current - 2);
            let end = Math.min(last, start + 4);

            if (end - start < 4) {
                start = Math.max(1, end - 4);
            }

            return Array.from(
                { length: end - start + 1 },
                (_, index) => start + index
            );
        },
    };
}
</script>

@endsection
