@extends('admin.layouts.main')

@section('title', 'Queue Monitor')

@section('content')

<div
    x-data="operationsQueue()"
    x-init="init()"
    class="space-y-6"
>

    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <div class="flex items-center gap-3">

                <h1 class="text-xl font-semibold text-gray-800">
                    Queue Monitor
                </h1>

                <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="statusClass()"
                    x-text="queue.status_label || 'Checking...'"
                ></span>

            </div>

            <p class="mt-1 text-sm text-gray-500">
                Monitor background jobs, queue health, backlog and failures.
            </p>

        </div>

        <div class="flex items-center gap-2">

            <span
                class="text-xs text-gray-400"
                x-show="lastChecked"
                x-text="'Updated ' + formatDate(lastChecked)"
            ></span>

            <button
                type="button"
                @click="load()"
                :disabled="loading"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
            >
                <i
                    class="ik ik-refresh-cw"
                    :class="{ 'animate-spin': loading }"
                ></i>

                Refresh
            </button>

        </div>

    </div>


    {{-- =========================================================
         ERROR
    ========================================================== --}}

    <template x-if="error">

        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">

            <div class="flex items-start gap-3">

                <i class="ik ik-alert-circle mt-0.5"></i>

                <div x-text="error"></div>

            </div>

        </div>

    </template>


    {{-- =========================================================
         QUEUE STATS
    ========================================================== --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Health --}}

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Queue Health
                    </p>

                    <p
                        class="mt-2 text-2xl font-semibold text-gray-800"
                        x-text="queue.status_label || '—'"
                    ></p>

                </div>

                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full"
                    :class="healthIconClass()"
                >
                    <i
                        class="text-lg"
                        :class="healthIcon()"
                    ></i>
                </div>

            </div>

            <p
                class="mt-3 text-xs text-gray-500"
                x-text="healthDescription()"
            ></p>

        </div>


        {{-- Pending --}}

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Pending Jobs
            </p>

            <p
                class="mt-2 text-3xl font-semibold text-gray-800"
                x-text="queue.pending_jobs ?? '—'"
            ></p>

            <p
                class="mt-2 text-xs text-gray-500"
                x-text="queue.pending_jobs > 0
                    ? 'Jobs waiting to be processed'
                    : 'Queue is empty'"
            ></p>

        </div>


        {{-- Failed --}}

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Failed Jobs
            </p>

            <p
                class="mt-2 text-3xl font-semibold"
                :class="queue.failed_jobs > 0
                    ? 'text-red-600'
                    : 'text-gray-800'"
                x-text="queue.failed_jobs ?? '—'"
            ></p>

            <p class="mt-2 text-xs text-gray-500">
                Jobs requiring attention
            </p>

        </div>


        {{-- Oldest --}}

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Oldest Pending
            </p>

            <p
                class="mt-2 text-3xl font-semibold text-gray-800"
                x-text="queue.oldest_job_age_human || '—'"
            ></p>

            <p
                class="mt-2 truncate text-xs text-gray-500"
                x-text="queue.oldest_job?.job_short || 'No pending jobs'"
            ></p>

        </div>

    </div>


    {{-- =========================================================
         QUEUE HEALTH DETAIL
    ========================================================== --}}

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Oldest job --}}

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

            <div class="border-b border-gray-100 px-6 py-5">

                <h2 class="font-semibold text-gray-800">
                    Queue Activity
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Current queue state and recent activity.
                </p>

            </div>

            <div class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0">

                <div class="p-6">

                    <p class="text-xs uppercase tracking-wide text-gray-500">
                        Queue Driver
                    </p>

                    <p
                        class="mt-2 font-medium text-gray-800"
                        x-text="queue.driver || '—'"
                    ></p>

                </div>

                <div class="p-6">

                    <p class="text-xs uppercase tracking-wide text-gray-500">
                        Queue Connection
                    </p>

                    <p
                        class="mt-2 font-medium text-gray-800"
                        x-text="queue.connection || '—'"
                    ></p>

                </div>

                <div class="border-t border-gray-100 p-6 sm:border-t-0">

                    <p class="text-xs uppercase tracking-wide text-gray-500">
                        Oldest Job
                    </p>

                    <p
                        class="mt-2 font-medium text-gray-800"
                        x-text="queue.oldest_job?.job_short || '—'"
                    ></p>

                    <p
                        class="mt-1 text-xs text-gray-500"
                        x-text="queue.oldest_job?.age_human || ''"
                    ></p>

                </div>

                <div class="border-t border-gray-100 p-6 sm:border-t-0">

                    <p class="text-xs uppercase tracking-wide text-gray-500">
                        Latest Job
                    </p>

                    <p
                        class="mt-2 font-medium text-gray-800"
                        x-text="queue.latest_job?.job_short || '—'"
                    ></p>

                    <p
                        class="mt-1 text-xs text-gray-500"
                        x-text="queue.latest_job?.created_at
                            ? formatDate(queue.latest_job.created_at)
                            : ''"
                    ></p>

                </div>

            </div>

        </div>


        {{-- Status explanation --}}

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

            <div class="border-b border-gray-100 px-6 py-5">

                <h2 class="font-semibold text-gray-800">
                    Queue Status
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Operational interpretation of the current queue.
                </p>

            </div>

            <div class="space-y-4 p-6">

                <div class="flex items-start gap-3">

                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-green-500"></span>

                    <div>

                        <p class="text-sm font-medium text-gray-800">
                            Healthy
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            No significant queue backlog.
                        </p>

                    </div>

                </div>


                <div class="flex items-start gap-3">

                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-blue-500"></span>

                    <div>

                        <p class="text-sm font-medium text-gray-800">
                            Busy
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Jobs are waiting but the backlog is still recent.
                        </p>

                    </div>

                </div>


                <div class="flex items-start gap-3">

                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-yellow-500"></span>

                    <div>

                        <p class="text-sm font-medium text-gray-800">
                            Delayed
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            The oldest job has been waiting for more than five minutes.
                        </p>

                    </div>

                </div>


                <div class="flex items-start gap-3">

                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-red-500"></span>

                    <div>

                        <p class="text-sm font-medium text-gray-800">
                            Stalled / Critical
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Jobs have been waiting for an extended period and require investigation.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         PENDING JOBS
    ========================================================== --}}

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="border-b border-gray-100 p-6">

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                <div>

                    <h2 class="font-semibold text-gray-800">
                        Pending Jobs
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        Jobs currently waiting to be processed.
                    </p>

                </div>

                <div class="relative w-full lg:w-80">

                    <i class="ik ik-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>

                    <input
                        type="text"
                        x-model="pendingSearch"
                        @input.debounce.400ms="loadPending()"
                        placeholder="Search pending jobs..."
                        class="w-full rounded-lg border border-gray-200 py-2 pl-10 pr-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    >

                </div>

            </div>

        </div>


        <template x-if="loadingPending">

            <div class="p-10 text-center text-sm text-gray-500">
                Loading pending jobs...
            </div>

        </template>


        <template x-if="!loadingPending && pendingJobs.length === 0">

            <div class="p-12 text-center">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-50">

                    <i class="ik ik-check-circle text-xl text-green-600"></i>

                </div>

                <h3 class="mt-4 font-medium text-gray-800">
                    Queue is empty
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    There are no pending jobs waiting to be processed.
                </p>

            </div>

        </template>


        <template x-if="!loadingPending && pendingJobs.length > 0">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-100">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Job
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Queue
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Attempts
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Age
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Available
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">

                        <template
                            x-for="job in pendingJobs"
                            :key="job.id"
                        >

                            <tr class="hover:bg-gray-50">

                                <td class="px-6 py-4">

                                    <p
                                        class="max-w-xs truncate text-sm font-medium text-gray-800"
                                        x-text="job.job_short || job.job"
                                    ></p>

                                    <p
                                        class="mt-1 max-w-xs truncate text-xs text-gray-400"
                                        x-text="job.job"
                                    ></p>

                                </td>


                                <td class="whitespace-nowrap px-6 py-4">

                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700"
                                        x-text="job.queue || 'default'"
                                    ></span>

                                </td>


                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">

                                    <span
                                        x-text="job.attempts ?? 0"
                                    ></span>

                                </td>


                                <td class="whitespace-nowrap px-6 py-4">

                                    <span
                                        class="text-sm font-medium"
                                        :class="job.age_seconds >= 900
                                            ? 'text-red-600'
                                            : job.age_seconds >= 300
                                                ? 'text-yellow-600'
                                                : 'text-gray-700'"
                                        x-text="job.age_human"
                                    ></span>

                                </td>


                                <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-500">

                                    <span
                                        x-text="formatDate(job.available_at)"
                                    ></span>

                                </td>


                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <button
                                        type="button"
                                        @click="viewPendingJob(job.id)"
                                        class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        View
                                    </button>

                                </td>

                            </tr>

                        </template>

                    </tbody>

                </table>

            </div>

        </template>


        {{-- Pending pagination --}}

        <div
            x-show="pendingPagination.last_page > 1"
            class="flex items-center justify-between border-t border-gray-100 px-6 py-4"
        >

            <button
                type="button"
                @click="previousPendingPage()"
                :disabled="pendingPagination.current_page <= 1"
                class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 disabled:opacity-40"
            >
                Previous
            </button>

            <span
                class="text-xs text-gray-500"
                x-text="`Page ${pendingPagination.current_page} of ${pendingPagination.last_page}`"
            ></span>

            <button
                type="button"
                @click="nextPendingPage()"
                :disabled="pendingPagination.current_page >= pendingPagination.last_page"
                class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 disabled:opacity-40"
            >
                Next
            </button>

        </div>

    </div>


    {{-- =========================================================
         FAILED JOBS
    ========================================================== --}}

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
                        x-model="failedSearch"
                        @input.debounce.400ms="loadFailed()"
                        placeholder="Search failed jobs..."
                        class="w-full rounded-lg border border-gray-200 py-2 pl-10 pr-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    >

                </div>

            </div>

        </div>


        <template x-if="loadingFailed">

            <div class="p-10 text-center text-sm text-gray-500">
                Loading failed jobs...
            </div>

        </template>


        <template x-if="!loadingFailed && failedJobs.length === 0">

            <div class="p-12 text-center">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-50">

                    <i class="ik ik-check-circle text-xl text-green-600"></i>

                </div>

                <h3 class="mt-4 font-medium text-gray-800">
                    No failed jobs
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Your failed-job queue is clean.
                </p>

            </div>

        </template>


        <template x-if="!loadingFailed && failedJobs.length > 0">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-100">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Queue
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Job
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

                        <template
                            x-for="job in failedJobs"
                            :key="job.id"
                        >

                            <tr class="hover:bg-gray-50">

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">

                                    <span
                                        x-text="job.queue || 'default'"
                                    ></span>

                                </td>


                                <td class="px-6 py-4">

                                    <p
                                        class="max-w-sm truncate text-sm font-medium text-gray-800"
                                        x-text="jobName(job)"
                                    ></p>

                                    <code
                                        class="mt-1 block max-w-sm truncate text-xs text-gray-400"
                                        x-text="job.uuid"
                                    ></code>

                                </td>


                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">

                                    <span
                                        x-text="formatDate(job.failed_at)"
                                    ></span>

                                </td>


                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <div class="flex justify-end gap-2">

                                        <button
                                            type="button"
                                            @click="viewFailedJob(job.id)"
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


        {{-- Failed pagination --}}

        <div
            x-show="failedPagination.last_page > 1"
            class="flex items-center justify-between border-t border-gray-100 px-6 py-4"
        >

            <button
                type="button"
                @click="previousFailedPage()"
                :disabled="failedPagination.current_page <= 1"
                class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 disabled:opacity-40"
            >
                Previous
            </button>

            <span
                class="text-xs text-gray-500"
                x-text="`Page ${failedPagination.current_page} of ${failedPagination.last_page}`"
            ></span>

            <button
                type="button"
                @click="nextFailedPage()"
                :disabled="failedPagination.current_page >= failedPagination.last_page"
                class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 disabled:opacity-40"
            >
                Next
            </button>

        </div>

    </div>


    {{-- =========================================================
         JOB MODAL
    ========================================================== --}}

    <template x-if="selectedJob">

        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="selectedJob = null"
        >

            <div class="max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-xl bg-white shadow-xl">

                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">

                    <div>

                        <div class="flex items-center gap-3">

                            <h3
                                class="font-semibold text-gray-800"
                                x-text="selectedJobTitle()"
                            ></h3>

                            <span
                                class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600"
                                x-text="selectedJob.queue || 'default'"
                            ></span>

                        </div>

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

                    {{-- Metadata --}}

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                        <div>

                            <p class="text-xs text-gray-500">
                                Status
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-gray-800"
                                x-text="selectedJob.status || 'Failed'"
                            ></p>

                        </div>


                        <div>

                            <p class="text-xs text-gray-500">
                                Attempts
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-gray-800"
                                x-text="selectedJob.attempts ?? '—'"
                            ></p>

                        </div>


                        <div>

                            <p class="text-xs text-gray-500">
                                Created
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-800"
                                x-text="formatDate(selectedJob.created_at)"
                            ></p>

                        </div>


                        <div>

                            <p class="text-xs text-gray-500">
                                Failed / Available
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-800"
                                x-text="formatDate(
                                    selectedJob.failed_at ||
                                    selectedJob.available_at
                                )"
                            ></p>

                        </div>

                    </div>


                    {{-- UUID --}}

                    <template x-if="selectedJob.uuid">

                        <div>

                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">
                                UUID
                            </p>

                            <code
                                class="block break-all rounded-lg bg-gray-50 p-4 text-xs text-gray-700"
                                x-text="selectedJob.uuid"
                            ></code>

                        </div>

                    </template>


                    {{-- Exception --}}

                    <template x-if="selectedJob.exception">

                        <div>

                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-red-600">
                                Exception
                            </p>

                            <pre
                                class="max-h-96 overflow-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"
                                x-text="selectedJob.exception"
                            ></pre>

                        </div>

                    </template>


                    {{-- Payload --}}

                    <template x-if="selectedJob.payload">

                        <div>

                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">
                                Payload
                            </p>

                            <pre
                                class="max-h-96 overflow-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"
                                x-text="formatPayload(selectedJob.payload)"
                            ></pre>

                        </div>

                    </template>

                </div>

            </div>

        </div>

    </template>

</div>


<script>
function operationsQueue() {
    return {
        loading: false,

        loadingPending: false,

        loadingFailed: false,

        error: null,

        lastChecked: null,

        pendingSearch: '',

        failedSearch: '',

        selectedJob: null,

        selectedJobType: null,

        queue: {
            status: null,
            status_label: null,
            driver: null,
            connection: null,
            pending_jobs: null,
            failed_jobs: null,
            queue_size: null,
            oldest_job_age: 0,
            oldest_job_age_human: null,
            oldest_job: null,
            latest_job: null,
        },

        pendingJobs: [],

        failedJobs: [],

        pendingPagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
        },

        failedPagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
        },

        async init() {
            await this.load();

            /*
             * Refresh the queue automatically every 15 seconds.
             */
            setInterval(() => {
                this.load();
            }, 15000);
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
                        result.message ||
                        'Unable to load queue information.'
                    );
                }

                this.queue = result.data;

                this.lastChecked =
                    result.data.checked_at ||
                    new Date().toISOString();

                await Promise.all([
                    this.loadPending(
                        this.pendingPagination.current_page
                    ),
                    this.loadFailed(
                        this.failedPagination.current_page
                    ),
                ]);

            } catch (error) {

                this.error = error.message;

            } finally {

                this.loading = false;

            }
        },

        async loadPending(page = 1) {
            this.loadingPending = true;

            try {

                const params = new URLSearchParams({
                    page: page,
                    per_page: 25,
                });

                if (this.pendingSearch) {
                    params.append(
                        'search',
                        this.pendingSearch
                    );
                }

                const response = await fetch(
                    `/api/admin/operations/queue/pending?${params.toString()}`,
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
                        'Unable to load pending jobs.'
                    );
                }

                const data = result.data;

                this.pendingJobs =
                    data.data || [];

                this.pendingPagination = {
                    current_page:
                        data.current_page || 1,

                    last_page:
                        data.last_page || 1,

                    total:
                        data.total || 0,
                };

            } catch (error) {

                this.error = error.message;

            } finally {

                this.loadingPending = false;

            }
        },

        async loadFailed(page = 1) {
            this.loadingFailed = true;

            try {

                const params = new URLSearchParams({
                    page: page,
                    per_page: 25,
                });

                if (this.failedSearch) {
                    params.append(
                        'search',
                        this.failedSearch
                    );
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
                        result.message ||
                        'Unable to load failed jobs.'
                    );
                }

                const data = result.data;

                this.failedJobs =
                    data.data || [];

                this.failedPagination = {
                    current_page:
                        data.current_page || 1,

                    last_page:
                        data.last_page || 1,

                    total:
                        data.total || 0,
                };

            } catch (error) {

                this.error = error.message;

            } finally {

                this.loadingFailed = false;

            }
        },

        async viewPendingJob(id) {
    try {

        const response = await fetch(
            `/api/admin/operations/queue/jobs/${id}`,
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
                'Unable to load pending job.'
            );
        }

        this.selectedJobType = 'pending';

        this.selectedJob = result.data;

    } catch (error) {

        this.error = error.message;

    }
},

        async viewFailedJob(id) {
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
                        result.message ||
                        'Unable to load failed job.'
                    );
                }

                this.selectedJobType = 'failed';

                this.selectedJob = result.data;

            } catch (error) {

                this.error = error.message;

            }
        },

        async retryJob(id) {
            if (!confirm(
                'Retry this failed job?'
            )) {
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
                        'Unable to retry job.'
                    );
                }

                this.selectedJob = null;

                await this.load();

            } catch (error) {

                this.error = error.message;

            }
        },

        async deleteJob(id) {
            if (!confirm(
                'Delete this failed job permanently?'
            )) {
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
                        'Unable to delete job.'
                    );
                }

                this.selectedJob = null;

                await this.load();

            } catch (error) {

                this.error = error.message;

            }
        },

        previousPendingPage() {
            if (
                this.pendingPagination.current_page > 1
            ) {
                this.loadPending(
                    this.pendingPagination.current_page - 1
                );
            }
        },

        nextPendingPage() {
            if (
                this.pendingPagination.current_page <
                this.pendingPagination.last_page
            ) {
                this.loadPending(
                    this.pendingPagination.current_page + 1
                );
            }
        },

        previousFailedPage() {
            if (
                this.failedPagination.current_page > 1
            ) {
                this.loadFailed(
                    this.failedPagination.current_page - 1
                );
            }
        },

        nextFailedPage() {
            if (
                this.failedPagination.current_page <
                this.failedPagination.last_page
            ) {
                this.loadFailed(
                    this.failedPagination.current_page + 1
                );
            }
        },

        statusClass() {

            switch (this.queue.status) {

                case 'busy':
                    return 'bg-blue-50 text-blue-700';

                case 'delayed':
                    return 'bg-yellow-50 text-yellow-700';

                case 'stalled':
                case 'critical':
                    return 'bg-red-50 text-red-700';

                default:
                    return 'bg-green-50 text-green-700';
            }
        },

        healthIconClass() {

            switch (this.queue.status) {

                case 'busy':
                    return 'bg-blue-50 text-blue-600';

                case 'delayed':
                    return 'bg-yellow-50 text-yellow-600';

                case 'stalled':
                case 'critical':
                    return 'bg-red-50 text-red-600';

                default:
                    return 'bg-green-50 text-green-600';
            }
        },

        healthIcon() {

            switch (this.queue.status) {

                case 'busy':
                    return 'ik ik-loader';

                case 'delayed':
                    return 'ik ik-clock';

                case 'stalled':
                case 'critical':
                    return 'ik ik-alert-triangle';

                default:
                    return 'ik ik-check-circle';
            }
        },

        healthDescription() {

            switch (this.queue.status) {

                case 'busy':
                    return 'Jobs are currently waiting to be processed.';

                case 'delayed':
                    return 'The queue has a noticeable processing delay.';

                case 'stalled':
                    return 'Jobs have been waiting for more than 15 minutes.';

                case 'critical':
                    return 'The queue is stalled and failed jobs require attention.';

                default:
                    return 'The queue is operating normally.';
            }
        },

        jobName(job) {

            if (!job) {
                return 'Unknown Job';
            }

            if (job.job) {
                return job.job;
            }

            try {

                const payload =
                    JSON.parse(job.payload);

                return payload.displayName ||
                    payload.data?.commandName ||
                    'Unknown Job';

            } catch {

                return 'Unknown Job';

            }
        },

        selectedJobTitle() {

            if (!this.selectedJob) {
                return 'Job Details';
            }

            if (this.selectedJob.job_short) {
                return this.selectedJob.job_short;
            }

            return this.jobName(
                this.selectedJob
            );
        },

        formatDate(value) {

            if (!value) {
                return '—';
            }

            try {

                return new Date(value)
                    .toLocaleString();

            } catch {

                return value;

            }
        },

        formatPayload(payload) {

            if (!payload) {
                return '—';
            }

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