@extends('admin.layouts.main')

@section('title', 'Cache Management')

@section('content')

<div
    x-data="operationsCache()"
    x-init="init()"
    class="space-y-6"
>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <div class="flex items-center gap-3">

                <h1 class="text-xl font-semibold text-gray-800">
                    Cache Management
                </h1>

                <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="statusClass()"
                    x-text="cache.status || 'Checking...'"
                ></span>

            </div>

            <p class="mt-1 text-sm text-gray-500">
                Monitor and manage application caches.
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
                    Cache management affects the running application
                </p>

                <p class="mt-1 text-xs text-yellow-700">
                    Clearing configuration, routes, views, or application
                    cache can temporarily affect application performance.
                    Use these actions carefully in production.
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


    {{-- Cache Overview --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Cache Driver
            </p>

            <p
                class="mt-2 text-xl font-semibold text-gray-800"
                x-text="cache.driver || '—'"
            ></p>

        </div>


        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Status
            </p>

            <p
                class="mt-2 text-xl font-semibold"
                :class="cache.status === 'healthy'
                    ? 'text-green-600'
                    : 'text-red-600'"
                x-text="cache.status || '—'"
            ></p>

        </div>


        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Latency
            </p>

            <p class="mt-2 text-xl font-semibold text-gray-800">

                <span x-text="cache.latency_ms ?? '—'"></span>

                <span
                    x-show="cache.latency_ms !== null"
                    class="text-sm font-normal text-gray-500"
                >
                    ms
                </span>

            </p>

        </div>


        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Prefix
            </p>

            <p
                class="mt-2 break-all text-sm font-semibold text-gray-800"
                x-text="cache.prefix || '—'"
            ></p>

        </div>

    </div>


    {{-- Cache Actions --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="border-b border-gray-100 p-6">

            <h2 class="font-semibold text-gray-800">
                Cache Actions
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Clear specific Laravel caches when needed.
            </p>

        </div>


        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">


            {{-- Application Cache --}}
            <div class="rounded-xl border border-gray-100 p-5">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <h3 class="font-medium text-gray-800">
                            Application Cache
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Clears items stored in the application's cache store.
                        </p>

                    </div>

                    <button
                        type="button"
                        @click="clearCache('clear', 'Clear application cache?')"
                        :disabled="actionLoading"
                        class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    >
                        Clear
                    </button>

                </div>

            </div>


            {{-- Config Cache --}}
            <div class="rounded-xl border border-gray-100 p-5">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <h3 class="font-medium text-gray-800">
                            Configuration Cache
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Removes the cached configuration file.
                        </p>

                    </div>

                    <button
                        type="button"
                        @click="clearCache('clear-config', 'Clear configuration cache?')"
                        :disabled="actionLoading"
                        class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    >
                        Clear
                    </button>

                </div>

            </div>


            {{-- Route Cache --}}
            <div class="rounded-xl border border-gray-100 p-5">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <h3 class="font-medium text-gray-800">
                            Route Cache
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Removes Laravel's cached route definitions.
                        </p>

                    </div>

                    <button
                        type="button"
                        @click="clearCache('clear-routes', 'Clear route cache?')"
                        :disabled="actionLoading"
                        class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    >
                        Clear
                    </button>

                </div>

            </div>


            {{-- View Cache --}}
            <div class="rounded-xl border border-gray-100 p-5">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <h3 class="font-medium text-gray-800">
                            View Cache
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Clears compiled Blade view files.
                        </p>

                    </div>

                    <button
                        type="button"
                        @click="clearCache('clear-views', 'Clear view cache?')"
                        :disabled="actionLoading"
                        class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    >
                        Clear
                    </button>

                </div>

            </div>

        </div>


        {{-- Clear All --}}
        <div class="border-t border-gray-100 bg-gray-50 p-6">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h3 class="font-medium text-gray-800">
                        Clear All Caches
                    </h3>

                    <p class="mt-1 text-xs text-gray-500">
                        Clears application, configuration, route, and view caches.
                    </p>

                </div>

                <button
                    type="button"
                    @click="clearCache('clear-all', 'Clear ALL application caches?')"
                    :disabled="actionLoading"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                >
                    <i class="ik ik-trash-2"></i>
                    Clear All Caches
                </button>

            </div>

        </div>

    </div>


    {{-- Result --}}
    <template x-if="message">

        <div class="rounded-xl border border-green-200 bg-green-50 p-4">

            <div class="flex items-start gap-3">

                <i class="ik ik-check-circle mt-0.5 text-green-600"></i>

                <span
                    class="text-sm text-green-700"
                    x-text="message"
                ></span>

            </div>

        </div>

    </template>

</div>


<script>
function operationsCache() {
    return {
        loading: false,
        actionLoading: false,

        error: null,
        message: null,

        cache: {
            driver: null,
            status: null,
            latency_ms: null,
            prefix: null,
        },

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(
                    '/api/admin/operations/cache',
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
                        'Unable to load cache information.'
                    );
                }

                this.cache = result.data;

            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async clearCache(endpoint, confirmation) {

            if (!confirm(confirmation)) {
                return;
            }

            this.actionLoading = true;
            this.error = null;
            this.message = null;

            try {
                const response = await fetch(
                    `/api/admin/operations/cache/${endpoint}`,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken(),
                        },
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message ||
                        'Cache operation failed.'
                    );
                }

                this.message = result.message;

                await this.load();

            } catch (error) {
                this.error = error.message;
            } finally {
                this.actionLoading = false;
            }
        },

        statusClass() {
            if (this.cache.status === 'healthy') {
                return 'bg-green-50 text-green-700';
            }

            if (this.cache.status === 'unhealthy') {
                return 'bg-red-50 text-red-700';
            }

            return 'bg-gray-100 text-gray-600';
        },

        csrfToken() {
            return document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';
        },
    };
}
</script>

@endsection