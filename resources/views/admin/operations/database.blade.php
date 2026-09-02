@extends('admin.layouts.main')

@section('title', 'Database')

@section('content')

<div
    x-data="operationsDatabase()"
    x-init="init()"
    class="space-y-6"
>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <div class="flex items-center gap-3">

                <h1 class="text-xl font-semibold text-gray-800">
                    Database
                </h1>

                <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="statusClass()"
                    x-text="database.status || 'Checking...'"
                ></span>

            </div>

            <p class="mt-1 text-sm text-gray-500">
                Monitor database health, size, tables, and migrations.
            </p>

        </div>

        <button
            type="button"
            @click="load()"
            :disabled="loading"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
        >
            <i class="ik ik-refresh-cw"></i>
            Refresh
        </button>

    </div>


    {{-- Warning --}}
    <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">

        <div class="flex items-start gap-3">

            <i class="ik ik-alert-triangle mt-0.5 text-yellow-600"></i>

            <div>

                <p class="text-sm font-medium text-yellow-800">
                    Read-only database monitoring
                </p>

                <p class="mt-1 text-xs text-yellow-700">
                    Database backup, restore, migration, and destructive
                    operations will be added separately with additional
                    security controls.
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


    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Driver
            </p>

            <p
                class="mt-2 text-xl font-semibold text-gray-800"
                x-text="database.driver || '—'"
            ></p>

        </div>


        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Database
            </p>

            <p
                class="mt-2 truncate text-xl font-semibold text-gray-800"
                x-text="database.database || '—'"
            ></p>

        </div>


        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Tables
            </p>

            <p
                class="mt-2 text-2xl font-semibold text-gray-800"
                x-text="database.table_count ?? '—'"
            ></p>

        </div>


        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Database Size
            </p>

            <p
                class="mt-2 text-xl font-semibold text-gray-800"
                x-text="database.database_size?.human || '—'"
            ></p>

        </div>

    </div>


    {{-- Connection Information --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="border-b border-gray-100 p-6">

            <h2 class="font-semibold text-gray-800">
                Connection Information
            </h2>

        </div>

        <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">

            <div>
                <p class="text-xs text-gray-500">
                    Host
                </p>

                <p
                    class="mt-1 text-sm font-medium text-gray-800"
                    x-text="database.host || '—'"
                ></p>
            </div>

            <div>
                <p class="text-xs text-gray-500">
                    Port
                </p>

                <p
                    class="mt-1 text-sm font-medium text-gray-800"
                    x-text="database.port || '—'"
                ></p>
            </div>

            <div>
                <p class="text-xs text-gray-500">
                    Server Version
                </p>

                <p
                    class="mt-1 break-all text-sm font-medium text-gray-800"
                    x-text="database.server_version || '—'"
                ></p>
            </div>

            <div>
                <p class="text-xs text-gray-500">
                    Query Latency
                </p>

                <p class="mt-1 text-sm font-medium text-gray-800">

                    <span x-text="database.latency_ms ?? '—'"></span>

                    <span
                        x-show="database.latency_ms !== null"
                        class="font-normal text-gray-500"
                    >
                        ms
                    </span>

                </p>
            </div>

        </div>

    </div>


    {{-- Tables --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="border-b border-gray-100 p-6">

            <h2 class="font-semibold text-gray-800">
                Database Tables
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Overview of tables and their storage usage.
            </p>

        </div>


        <template x-if="loadingTables">

            <div class="p-10 text-center text-sm text-gray-500">
                Loading tables...
            </div>

        </template>


        <template x-if="!loadingTables && tables.length === 0">

            <div class="p-10 text-center text-sm text-gray-500">
                No table information available.
            </div>

        </template>


        <template x-if="!loadingTables && tables.length > 0">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-100">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                Table
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                Engine
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                                Rows
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                                Data
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                                Index
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                                Total
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        <template
                            x-for="table in tables"
                            :key="table.name"
                        >

                            <tr class="hover:bg-gray-50">

                                <td class="px-6 py-4">

                                    <code
                                        class="text-sm font-medium text-gray-800"
                                        x-text="table.name"
                                    ></code>

                                </td>

                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <span x-text="table.engine || '—'"></span>
                                </td>

                                <td class="px-6 py-4 text-right text-sm text-gray-700">
                                    <span x-text="formatNumber(table.rows)"></span>
                                </td>

                                <td class="px-6 py-4 text-right text-sm text-gray-500">
                                    <span x-text="formatBytes(table.data_size)"></span>
                                </td>

                                <td class="px-6 py-4 text-right text-sm text-gray-500">
                                    <span x-text="formatBytes(table.index_size)"></span>
                                </td>

                                <td class="px-6 py-4 text-right text-sm font-medium text-gray-800">
                                    <span x-text="table.total_size_human"></span>
                                </td>

                            </tr>

                        </template>

                    </tbody>

                </table>

            </div>

        </template>

    </div>


    {{-- Recent Migrations --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="border-b border-gray-100 p-6">

            <h2 class="font-semibold text-gray-800">
                Recent Migrations
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                The most recently executed migrations.
            </p>

        </div>


        <template x-if="loadingMigrations">

            <div class="p-10 text-center text-sm text-gray-500">
                Loading migrations...
            </div>

        </template>


        <template x-if="!loadingMigrations && migrations.length === 0">

            <div class="p-10 text-center text-sm text-gray-500">
                No migration records found.
            </div>

        </template>


        <template x-if="!loadingMigrations && migrations.length > 0">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-100">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                Migration
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                                Batch
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        <template
                            x-for="migration in migrations"
                            :key="migration.id"
                        >

                            <tr class="hover:bg-gray-50">

                                <td class="px-6 py-4">

                                    <code
                                        class="break-all text-sm text-gray-700"
                                        x-text="migration.migration"
                                    ></code>

                                </td>

                                <td class="px-6 py-4 text-right text-sm text-gray-500">
                                    <span x-text="migration.batch"></span>
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
function operationsDatabase() {
    return {
        loading: false,
        loadingTables: false,
        loadingMigrations: false,

        error: null,

        database: {
            status: null,
            driver: null,
            database: null,
            host: null,
            port: null,
            latency_ms: null,
            server_version: null,
            table_count: null,
            database_size: {
                human: null,
            },
        },

        tables: [],
        migrations: [],

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            this.error = null;

            try {

                await Promise.all([
                    this.loadOverview(),
                    this.loadTables(),
                    this.loadMigrations(),
                ]);

            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async loadOverview() {

            const response = await fetch(
                '/api/admin/operations/database',
                {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                    },
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message ||
                    'Unable to load database information.'
                );
            }

            this.database = result.data;
        },

        async loadTables() {

            this.loadingTables = true;

            try {

                const response = await fetch(
                    '/api/admin/operations/database/tables',
                    {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                        },
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message ||
                        'Unable to load database tables.'
                    );
                }

                this.tables = result.data || [];

            } finally {

                this.loadingTables = false;

            }
        },

        async loadMigrations() {

            this.loadingMigrations = true;

            try {

                const response = await fetch(
                    '/api/admin/operations/database/migrations',
                    {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                        },
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message ||
                        'Unable to load migrations.'
                    );
                }

                this.migrations = result.data || [];

            } finally {

                this.loadingMigrations = false;

            }
        },

        statusClass() {

            if (this.database.status === 'healthy') {
                return 'bg-green-50 text-green-700';
            }

            if (this.database.status === 'unhealthy') {
                return 'bg-red-50 text-red-700';
            }

            return 'bg-gray-100 text-gray-600';

        },

        formatNumber(value) {

            return new Intl.NumberFormat().format(
                Number(value || 0)
            );

        },

        formatBytes(bytes) {

            bytes = Number(bytes || 0);

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
                    (bytes / Math.pow(1024, power)) * 100
                ) / 100
            ) + ' ' + units[power];

        },
    };
}
</script>

@endsection