@extends('admin.layouts.main')

@section('title', 'Queue Monitor')

@section('content')

<div
    x-data="operationsQueue()"
    x-init="init()"
    class="space-y-6"
>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold text-gray-800">
                    Queue Monitor
                </h1>

                <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="statusClass()"
                    x-text="queue.status || 'Checking...'"
                ></span>
            </div>

            <p class="mt-1 text-sm text-gray-500">
                Monitor queued and failed background jobs.
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


    {{-- Error --}}
    <template x-if="error">
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <div class="flex items-start gap-3">
                <i class="ik ik-alert-circle mt-0.5"></i>
                <div x-text="error"></div>
            </div>
        </div>
    </template>


    {{-- Queue Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Queue Driver
            </p>

            <p
                class="mt-2 text-xl font-semibold text-gray-800"
                x-text="queue.driver || '—'"
            ></p>
        </div>


        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Pending Jobs
            </p>

            <p
                class="mt-2 text-2xl font-semibold text-gray-800"
                x-text="queue.pending_jobs ?? '—'"
            ></p>
        </div>


        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Failed Jobs
            </p>

            <p
                class="mt-2 text-2xl font-semibold text-red-600"
                x-text="queue.failed_jobs ?? '—'"
            ></p>
        </div>


        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Queue Size
            </p>

            <p
                class="mt-2 text-2xl font-semibold text-gray-800"
                x-text="queue.queue_size ?? '—'"
            ></p>
        </div>

    </div>


    {{-- Failed Jobs --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="border-b border-gray-100 p-6">

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                <div>
                    <h2 class="font-semibold text-gray-800">
                        Failed Jobs
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        Jobs that could not be processed successfully.
                    </p>
                </div>

                <div class="relative w-full lg:w-80">

                    <i class="ik ik-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>

                    <input
                        type="text"
                        x-model="search"
                        @input.debounce.400ms="loadFailed()"
                        placeholder="Search failed jobs..."
                        class="w-full rounded-lg border border-gray-200 py-2 pl-10 pr-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    >

                </div>

            </div>

        </div>


        {{-- Loading --}}
        <template x-if="loadingFailed">
            <div class="p-10 text-center text-sm text-gray-500">
                Loading failed jobs...
            </div>
        </template>


        {{-- Empty --}}
        <template x-if="!loadingFailed && failedJobs.length === 0">

            <div class="p-12 text-center">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-50">
                    <i class="ik ik-check-circle text-xl text-green-600"></i>
                </div>

                <h3 class="mt-4 font-medium text-gray-800">
                    No failed jobs
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Your queue is clean.
                </p>

            </div>

        </template>


        {{-- Table --}}
        <template x-if="!loadingFailed && failedJobs.length > 0">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-100">

                    <thead class="bg-gray-50">

                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Queue
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                UUID
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Failed At
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">

                        <template x-for="job in failedJobs" :key="job.id">

                            <tr class="hover:bg-gray-50">

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    <span x-text="job.queue || 'default'"></span>
                                </td>

                                <td class="px-6 py-4">
                                    <code
                                        class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700"
                                        x-text="job.uuid"
                                    ></code>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    <span x-text="formatDate(job.failed_at)"></span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <div class="flex justify-end gap-2">

                                        <button
                                            type="button"
                                            @click="viewJob(job.id)"
                                            class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                        >
                                            View
                                        </button>

                                        <button
                                            type="button"
                                            @click="retryJob(job.id)"
                                            class="rounded-lg bg-primary-600 px-3 py-2 text-xs font-medium text-white hover:bg-primary-700"
                                        >
                                            Retry
                                        </button>

                                        <button
                                            type="button"
                                            @click="deleteJob(job.id)"
                                            class="rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50"
                                        >
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


        {{-- Pagination --}}
        <div
            x-show="pagination.last_page > 1"
            class="flex items-center justify-between border-t border-gray-100 px-6 py-4"
        >

            <button
                type="button"
                @click="previousPage()"
                :disabled="pagination.current_page <= 1"
                class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 disabled:opacity-40"
            >
                Previous
            </button>

            <span
                class="text-xs text-gray-500"
                x-text="`Page ${pagination.current_page} of ${pagination.last_page}`"
            ></span>

            <button
                type="button"
                @click="nextPage()"
                :disabled="pagination.current_page >= pagination.last_page"
                class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 disabled:opacity-40"
            >
                Next
            </button>

        </div>

    </div>


    {{-- Job Modal --}}
    <template x-if="selectedJob">

        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="selectedJob = null"
        >

            <div class="max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-xl">

                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">

                    <div>
                        <h3 class="font-semibold text-gray-800">
                            Failed Job Details
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Job ID:
                            <span x-text="selectedJob.id"></span>
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="selectedJob = null"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    >
                        <i class="ik ik-x"></i>
                    </button>

                </div>


                <div class="max-h-[75vh] overflow-y-auto p-6 space-y-6">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        <div>
                            <p class="text-xs text-gray-500">UUID</p>
                            <code
                                class="mt-1 block break-all text-sm text-gray-800"
                                x-text="selectedJob.uuid"
                            ></code>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">Queue</p>
                            <p
                                class="mt-1 text-sm text-gray-800"
                                x-text="selectedJob.queue"
                            ></p>
                        </div>

                    </div>


                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">
                            Exception
                        </p>

                        <pre
                            class="max-h-80 overflow-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"
                            x-text="selectedJob.exception"
                        ></pre>
                    </div>


                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">
                            Payload
                        </p>

                        <pre
                            class="max-h-80 overflow-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"
                            x-text="formatPayload(selectedJob.payload)"
                        ></pre>
                    </div>

                </div>

            </div>

        </div>

    </template>

</div>


<script>
function operationsQueue() {
    return {
        loading: false,
        loadingFailed: false,

        error: null,

        search: '',

        queue: {
            status: null,
            driver: null,
            pending_jobs: null,
            failed_jobs: null,
            queue_size: null,
        },

        failedJobs: [],

        selectedJob: null,

        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
        },

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(
                    '/api/admin/operations/queue',
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
                        result.message || 'Unable to load queue information.'
                    );
                }

                this.queue = result.data;

                await this.loadFailed();

            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async loadFailed(page = 1) {
            this.loadingFailed = true;

            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 25,
                });

                if (this.search) {
                    params.append('search', this.search);
                }

                const response = await fetch(
                    `/api/admin/operations/queue/failed?${params.toString()}`,
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
                        result.message || 'Unable to load failed jobs.'
                    );
                }

                const data = result.data;

                this.failedJobs = data.data || [];

                this.pagination = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    total: data.total || 0,
                };

            } catch (error) {
                this.error = error.message;
            } finally {
                this.loadingFailed = false;
            }
        },

        async viewJob(id) {
            try {
                const response = await fetch(
                    `/api/admin/operations/queue/failed/${id}`,
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
                        result.message || 'Unable to load failed job.'
                    );
                }

                this.selectedJob = result.data;

            } catch (error) {
                this.error = error.message;
            }
        },

        async retryJob(id) {
            if (!confirm('Retry this failed job?')) {
                return;
            }

            try {
                const response = await fetch(
                    `/api/admin/operations/queue/failed/${id}/retry`,
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
                        result.message || 'Unable to retry job.'
                    );
                }

                await this.load();

            } catch (error) {
                this.error = error.message;
            }
        },

        async deleteJob(id) {
            if (!confirm('Delete this failed job permanently?')) {
                return;
            }

            try {
                const response = await fetch(
                    `/api/admin/operations/queue/failed/${id}`,
                    {
                        method: 'DELETE',
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
                        result.message || 'Unable to delete job.'
                    );
                }

                await this.load();

            } catch (error) {
                this.error = error.message;
            }
        },

        previousPage() {
            if (this.pagination.current_page > 1) {
                this.loadFailed(
                    this.pagination.current_page - 1
                );
            }
        },

        nextPage() {
            if (
                this.pagination.current_page <
                this.pagination.last_page
            ) {
                this.loadFailed(
                    this.pagination.current_page + 1
                );
            }
        },

        statusClass() {
            if (this.queue.status === 'healthy') {
                return 'bg-green-50 text-green-700';
            }

            return 'bg-gray-100 text-gray-600';
        },

        formatDate(value) {
            if (!value) {
                return '—';
            }

            return new Date(value).toLocaleString();
        },

        formatPayload(payload) {
            try {
                return JSON.stringify(
                    JSON.parse(payload),
                    null,
                    2
                );
            } catch {
                return payload;
            }
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