@extends('admin.layouts.main')

@section('title', 'Backups')

@section('content')

<div
    x-data="operationsBackups()"
    x-init="init()"
    class="space-y-6"
>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <div class="flex items-center gap-3">

                <h1 class="text-xl font-semibold text-gray-800">
                    Backups
                </h1>

                <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="enabled
                        ? 'bg-green-50 text-green-700'
                        : 'bg-gray-100 text-gray-600'"
                    x-text="enabled ? 'Enabled' : 'Disabled'"
                ></span>

            </div>

            <p class="mt-1 text-sm text-gray-500">
                Create and manage database backups.
            </p>

        </div>

        <div class="flex gap-2">

            {{-- Refresh --}}
            <button
                type="button"
                @click="load()"
                :disabled="loading"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
            >
                <i
                    class="ik ik-refresh-cw"
                    :class="loading ? 'animate-spin' : ''"
                ></i>

                Refresh
            </button>


            {{-- Create Backup --}}
            <button
                type="button"
                @click="createBackup()"
                :disabled="creating || !enabled"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
            >

                <i
                    class="ik"
                    :class="creating
                        ? 'ik-loader animate-spin'
                        : 'ik-plus'"
                ></i>

                <span
                    x-text="creating
                        ? 'Queuing...'
                        : 'Create Backup'"
                ></span>

            </button>

        </div>

    </div>


    {{-- Processing Notice --}}
    <template x-if="hasActiveBackup()">

        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">

            <div class="flex items-start gap-3">

                <i class="ik ik-loader mt-0.5 animate-spin text-blue-600"></i>

                <div>

                    <p class="text-sm font-medium text-blue-800">
                        Backup in progress
                    </p>

                    <p class="mt-1 text-xs text-blue-700">
                        The backup is being processed by the queue worker.
                        You can safely leave this page.
                    </p>

                </div>

            </div>

        </div>

    </template>


    {{-- General Warning --}}
    <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">

        <div class="flex items-start gap-3">

            <i class="ik ik-alert-triangle mt-0.5 text-yellow-600"></i>

            <div>

                <p class="text-sm font-medium text-yellow-800">
                    Backup operation
                </p>

                <p class="mt-1 text-xs text-yellow-700">
                    Database backups are processed in the background.
                    Large databases may take several minutes to complete.
                </p>

            </div>

        </div>

    </div>


    {{-- Error --}}
    <template x-if="error">

        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">

            <div class="flex items-start gap-3">

                <i class="ik ik-alert-circle mt-0.5"></i>

                <span x-text="error"></span>

            </div>

        </div>

    </template>


    {{-- Success --}}
    <template x-if="message">

        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">

            <div class="flex items-start gap-3">

                <i class="ik ik-check-circle mt-0.5"></i>

                <span x-text="message"></span>

            </div>

        </div>

    </template>


    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- Total Backups --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Total Backups
            </p>

            <p
                class="mt-2 text-2xl font-semibold text-gray-800"
                x-text="overview.total_backups ?? '—'"
            ></p>

        </div>


        {{-- Total Storage --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Total Storage
            </p>

            <p
                class="mt-2 text-xl font-semibold text-gray-800"
                x-text="overview.total_size_human || '—'"
            ></p>

        </div>


        {{-- Storage Disk --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Storage Disk
            </p>

            <p
                class="mt-2 text-xl font-semibold text-gray-800"
                x-text="overview.disk || '—'"
            ></p>

        </div>


        {{-- Latest Backup --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Latest Backup
            </p>

            <p
                class="mt-2 text-sm font-semibold text-gray-800"
                x-text="overview.latest_backup
                    ? formatDate(overview.latest_backup.completed_at)
                    : 'Never'"
            ></p>

        </div>

    </div>


    {{-- Backup History --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="flex flex-col gap-3 border-b border-gray-100 p-6 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="font-semibold text-gray-800">
                    Backup History
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Previously created database backups.
                </p>

            </div>

            {{-- Auto-refresh indicator --}}
            <template x-if="polling">

                <div class="inline-flex items-center gap-2 text-xs text-gray-500">

                    <span class="relative flex h-2 w-2">

                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span>

                        <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-500"></span>

                    </span>

                    Checking backup status...

                </div>

            </template>

        </div>


        {{-- Loading --}}
        <template x-if="loadingBackups">

            <div class="p-10 text-center text-sm text-gray-500">

                <div class="flex items-center justify-center gap-2">

                    <i class="ik ik-loader animate-spin"></i>

                    Loading backups...

                </div>

            </div>

        </template>


        {{-- Empty --}}
        <template x-if="!loadingBackups && backups.length === 0">

            <div class="p-12 text-center">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-50">

                    <i class="ik ik-archive text-xl text-gray-400"></i>

                </div>

                <h3 class="mt-4 font-medium text-gray-800">
                    No backups yet
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Create your first database backup.
                </p>

            </div>

        </template>


        {{-- Table --}}
        <template x-if="!loadingBackups && backups.length > 0">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-100">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                File
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                Status
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                Size
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                Created
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        <template
                            x-for="backup in backups"
                            :key="backup.id"
                        >

                            <tr class="hover:bg-gray-50">

                                {{-- File --}}
                                <td class="px-6 py-4">

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                            :class="backup.status === 'completed'
                                                ? 'bg-green-50'
                                                : backup.status === 'failed'
                                                    ? 'bg-red-50'
                                                    : 'bg-blue-50'"
                                        >

                                            <i
                                                class="ik"
                                                :class="backup.status === 'completed'
                                                    ? 'ik-check'
                                                    : backup.status === 'failed'
                                                        ? 'ik-alert-circle'
                                                        : 'ik-loader animate-spin'"
                                            ></i>

                                        </div>

                                        <div class="min-w-0">

                                            <code
                                                class="block truncate text-sm text-gray-800"
                                                x-text="backup.filename"
                                            ></code>

                                            <template x-if="backup.checksum">

                                                <p class="mt-1 max-w-xs truncate font-mono text-[10px] text-gray-400">
                                                    SHA256:
                                                    <span x-text="backup.checksum"></span>
                                                </p>

                                            </template>

                                        </div>

                                    </div>

                                </td>


                                {{-- Status --}}
                                <td class="px-6 py-4">

                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusClass(backup.status)"
                                    >

                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="statusDotClass(backup.status)"
                                        ></span>

                                        <span
                                            x-text="formatStatus(backup.status)"
                                        ></span>

                                    </span>

                                    {{-- Failure message --}}
                                    <template x-if="backup.status === 'failed' && backup.error">

                                        <p
                                            class="mt-2 max-w-xs text-xs text-red-500"
                                            x-text="backup.error"
                                        ></p>

                                    </template>

                                </td>


                                {{-- Size --}}
                                <td class="px-6 py-4 text-sm text-gray-500">

                                    <span
                                        x-text="backup.status === 'completed'
                                            ? formatBytes(backup.size)
                                            : '—'"
                                    ></span>

                                </td>


                                {{-- Created --}}
                                <td class="px-6 py-4 text-sm text-gray-500">

                                    <span
                                        x-text="formatDate(backup.created_at)"
                                    ></span>

                                </td>


                                {{-- Actions --}}
                                <td class="px-6 py-4">

                                    <div class="flex justify-end gap-2">

                                        {{-- Download --}}
                                        <template x-if="backup.status === 'completed'">

                                            <a
                                                :href="`/api/admin/operations/backups/${backup.id}/download`"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                            >

                                                <i class="ik ik-download"></i>

                                                Download

                                            </a>

                                        </template>


                                        {{-- Processing indicator --}}
                                        <template x-if="
                                            backup.status === 'pending' ||
                                            backup.status === 'running'
                                        ">

                                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-600">

                                                <i class="ik ik-loader animate-spin"></i>

                                                <span
                                                    x-text="backup.status === 'pending'
                                                        ? 'Queued'
                                                        : 'Processing'"
                                                ></span>

                                            </span>

                                        </template>


                                        {{-- Delete --}}
                                        <button
                                            type="button"
                                            @click="deleteBackup(backup)"
                                            :disabled="
                                                backup.status === 'pending' ||
                                                backup.status === 'running'
                                            "
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        >

                                            <i class="ik ik-trash-2"></i>

                                            Delete

                                        </button>

                                    </div>

                                </td>

                            </tr>

                        </template>

                    </tbody>

                </table>

            </div>

        </template>

    </div>

</div>


<script>
function operationsBackups() {

    return {

        /*
         * ---------------------------------------------------------
         * State
         * ---------------------------------------------------------
         */

        loading: false,

        loadingBackups: false,

        creating: false,

        polling: false,

        pollTimer: null,

        enabled: false,

        error: null,

        message: null,

        overview: {
            total_backups: 0,
            total_size_human: null,
            disk: null,
            latest_backup: null,
        },

        backups: [],


        /*
         * ---------------------------------------------------------
         * Initialization
         * ---------------------------------------------------------
         */

        async init() {

            await this.load();

            this.startPolling();

        },


        /*
         * ---------------------------------------------------------
         * Main loader
         * ---------------------------------------------------------
         */

        async load() {

            this.loading = true;

            this.error = null;

            try {

                await Promise.all([
                    this.loadOverview(),
                    this.loadBackups(),
                ]);

            } catch (error) {

                this.error =
                    error.message ||
                    'Unable to load backup information.';

            } finally {

                this.loading = false;

            }

        },


        /*
         * ---------------------------------------------------------
         * Overview
         * ---------------------------------------------------------
         */

        async loadOverview() {

            const response = await fetch(
                '/api/admin/operations/backups',
                {
                    credentials: 'same-origin',

                    headers: {
                        'Accept': 'application/json',
                    },
                }
            );

            const result =
                await response.json();

            if (
                !response.ok ||
                !result.success
            ) {
                throw new Error(
                    result.message ||
                    'Unable to load backup information.'
                );
            }

            this.overview =
                result.data;

            this.enabled =
                result.data.enabled;

        },


        /*
         * ---------------------------------------------------------
         * Backup list
         * ---------------------------------------------------------
         */

        async loadBackups() {

            this.loadingBackups = true;

            try {

                const response = await fetch(
                    '/api/admin/operations/backups/list',
                    {
                        credentials: 'same-origin',

                        headers: {
                            'Accept': 'application/json',
                        },
                    }
                );

                const result =
                    await response.json();

                if (
                    !response.ok ||
                    !result.success
                ) {
                    throw new Error(
                        result.message ||
                        'Unable to load backups.'
                    );
                }

                this.backups =
                    result.data.data || [];

            } finally {

                this.loadingBackups = false;

            }

        },


        /*
         * ---------------------------------------------------------
         * Create backup
         * ---------------------------------------------------------
         */

        async createBackup() {

            if (!confirm(
                'Create a new database backup now?'
            )) {
                return;
            }

            this.creating = true;

            this.error = null;

            this.message = null;

            try {

                const response = await fetch(
                    '/api/admin/operations/backups/create',
                    {
                        method: 'POST',

                        credentials: 'same-origin',

                        headers: {
                            'Accept': 'application/json',

                            'X-CSRF-TOKEN':
                                this.csrfToken(),
                        },
                    }
                );

                const result =
                    await response.json();

                if (
                    !response.ok ||
                    !result.success
                ) {
                    throw new Error(
                        result.message ||
                        'Unable to queue backup.'
                    );
                }

                this.message =
                    result.message ||
                    'Backup has been queued.';

                /*
                 * Immediately refresh so the pending backup
                 * appears in the table.
                 */
                await this.load();

                /*
                 * Make sure polling is active.
                 */
                this.startPolling();

            } catch (error) {

                this.error =
                    error.message ||
                    'Unable to create backup.';

            } finally {

                this.creating = false;

            }

        },


        /*
         * ---------------------------------------------------------
         * Delete backup
         * ---------------------------------------------------------
         */

        async deleteBackup(backup) {

            if (
                backup.status === 'pending' ||
                backup.status === 'running'
            ) {
                return;
            }

            if (!confirm(
                `Delete backup "${backup.filename}" permanently?`
            )) {
                return;
            }

            this.error = null;

            this.message = null;

            try {

                const response = await fetch(
                    `/api/admin/operations/backups/${backup.id}`,
                    {
                        method: 'DELETE',

                        credentials: 'same-origin',

                        headers: {
                            'Accept': 'application/json',

                            'X-CSRF-TOKEN':
                                this.csrfToken(),
                        },
                    }
                );

                const result =
                    await response.json();

                if (
                    !response.ok ||
                    !result.success
                ) {
                    throw new Error(
                        result.message ||
                        'Unable to delete backup.'
                    );
                }

                this.message =
                    result.message ||
                    'Backup deleted successfully.';

                await this.load();

            } catch (error) {

                this.error =
                    error.message ||
                    'Unable to delete backup.';

            }

        },


        /*
         * ---------------------------------------------------------
         * Detect active backups
         * ---------------------------------------------------------
         */

        hasActiveBackup() {

            return this.backups.some(
                backup =>
                    backup.status === 'pending' ||
                    backup.status === 'running'
            );

        },


        /*
         * ---------------------------------------------------------
         * Polling
         * ---------------------------------------------------------
         *
         * Check every 3 seconds while a backup is active.
         * Once all backups are completed/failed, polling stops.
         */

        startPolling() {

            this.stopPolling();

            if (!this.hasActiveBackup()) {
                return;
            }

            this.polling = true;

            this.pollTimer =
                setInterval(
                    async () => {

                        try {

                            await this.loadBackups();

                            /*
                             * Refresh overview as well because
                             * total storage and latest backup may
                             * have changed.
                             */
                            await this.loadOverview();

                            if (
                                !this.hasActiveBackup()
                            ) {

                                this.stopPolling();

                                this.message =
                                    'Database backup completed successfully.';

                            }

                        } catch (error) {

                            /*
                             * Do not stop polling because of a
                             * temporary network error.
                             */
                            console.error(
                                'Backup polling error:',
                                error
                            );

                        }

                    },
                    3000
                );

        },


        stopPolling() {

            if (this.pollTimer) {

                clearInterval(
                    this.pollTimer
                );

                this.pollTimer = null;

            }

            this.polling = false;

        },


        /*
         * ---------------------------------------------------------
         * Status styling
         * ---------------------------------------------------------
         */

        statusClass(status) {

            switch (status) {

                case 'completed':
                    return 'bg-green-50 text-green-700';

                case 'failed':
                    return 'bg-red-50 text-red-700';

                case 'running':
                    return 'bg-blue-50 text-blue-700';

                case 'pending':
                    return 'bg-yellow-50 text-yellow-700';

                default:
                    return 'bg-gray-100 text-gray-600';

            }

        },


        statusDotClass(status) {

            switch (status) {

                case 'completed':
                    return 'bg-green-500';

                case 'failed':
                    return 'bg-red-500';

                case 'running':
                    return 'bg-blue-500';

                case 'pending':
                    return 'bg-yellow-500';

                default:
                    return 'bg-gray-400';

            }

        },


        formatStatus(status) {

            switch (status) {

                case 'completed':
                    return 'Completed';

                case 'failed':
                    return 'Failed';

                case 'running':
                    return 'Processing';

                case 'pending':
                    return 'Queued';

                default:
                    return status || 'Unknown';

            }

        },


        /*
         * ---------------------------------------------------------
         * Date formatting
         * ---------------------------------------------------------
         */

        formatDate(value) {

            if (!value) {
                return '—';
            }

            return new Date(
                value
            ).toLocaleString();

        },


        /*
         * ---------------------------------------------------------
         * Byte formatting
         * ---------------------------------------------------------
         */

        formatBytes(bytes) {

            bytes = Number(
                bytes || 0
            );

            if (bytes <= 0) {
                return '0 B';
            }

            const units = [
                'B',
                'KB',
                'MB',
                'GB',
                'TB'
            ];

            const power = Math.min(
                Math.floor(
                    Math.log(bytes) /
                    Math.log(1024)
                ),
                units.length - 1
            );

            return (
                Math.round(
                    (
                        bytes /
                        Math.pow(
                            1024,
                            power
                        )
                    ) * 100
                ) / 100
            ) + ' ' + units[power];

        },


        /*
         * ---------------------------------------------------------
         * CSRF
         * ---------------------------------------------------------
         */

        csrfToken() {

            return document
                .querySelector(
                    'meta[name="csrf-token"]'
                )
                ?.getAttribute(
                    'content'
                ) || '';

        },

    };
}
</script>

@endsection
