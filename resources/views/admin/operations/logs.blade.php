@extends('admin.layouts.main')

@section('title', 'System Logs')

@section('content')

<div
    x-data="operationsSystemLogs()"
    x-init="init()"
    class="space-y-6"
>

    {{-- ================================================================
         HEADER
         ================================================================ --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                System Logs
            </h1>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Monitor application events, errors, warnings, and system activity.
            </p>
        </div>

        <div class="flex items-center gap-2">

            {{-- Auto Refresh --}}
            <button
                type="button"
                @click="toggleAutoRefresh()"
                class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition"
                :class="autoRefresh
                    ? 'border-green-300 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400'
                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h5M20 20v-5h-5M5.5 9A7.5 7.5 0 0118.5 6.5L20 9M18.5 15A7.5 7.5 0 015.5 17.5L4 15"
                    />
                </svg>

                <span x-text="autoRefresh ? 'Auto Refresh On' : 'Auto Refresh'"></span>
            </button>

            {{-- Refresh --}}
            <button
                type="button"
                @click="refresh()"
                :disabled="loading"
                class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
            >
                <svg
                    class="h-4 w-4"
                    :class="loading ? 'animate-spin' : ''"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h5M20 20v-5h-5M5 19A9 9 0 1119 5"
                    />
                </svg>

                Refresh
            </button>

        </div>
    </div>


    {{-- ================================================================
         ERROR
         ================================================================ --}}
    <template x-if="error">
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900/50 dark:bg-red-900/20">
            <div class="flex items-start gap-3">

                <svg
                    class="mt-0.5 h-5 w-5 shrink-0 text-red-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.36h15.6a2 2 0 001.73-3L13.71 3.86a2 2 0 00-3.42 0z"
                    />
                </svg>

                <div>
                    <p class="text-sm font-medium text-red-800 dark:text-red-300">
                        Unable to load logs
                    </p>

                    <p
                        class="mt-1 text-sm text-red-700 dark:text-red-400"
                        x-text="error"
                    ></p>
                </div>
            </div>
        </div>
    </template>


    {{-- ================================================================
         LOG FILE INFORMATION
         ================================================================ --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

        {{-- File --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Log File
                    </p>

                    <p
                        class="mt-1 truncate text-sm font-semibold text-gray-900 dark:text-white"
                        x-text="file.filename || 'laravel.log'"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-100 p-2.5 dark:bg-gray-700">
                    <svg
                        class="h-5 w-5 text-gray-600 dark:text-gray-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2z"
                        />
                    </svg>
                </div>

            </div>
        </div>


        {{-- Size --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        File Size
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-900 dark:text-white"
                        x-text="file.size_human || '0 B'"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-100 p-2.5 dark:bg-gray-700">
                    <svg
                        class="h-5 w-5 text-gray-600 dark:text-gray-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 7h16M4 12h16M4 17h10"
                        />
                    </svg>
                </div>

            </div>
        </div>


        {{-- Last Modified --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Last Modified
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-gray-900 dark:text-white"
                        x-text="file.last_modified || '—'"
                    ></p>
                </div>

                <div class="rounded-lg bg-gray-100 p-2.5 dark:bg-gray-700">
                    <svg
                        class="h-5 w-5 text-gray-600 dark:text-gray-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>

            </div>
        </div>

    </div>


    {{-- ================================================================
         FILTERS
         ================================================================ --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

            {{-- Search --}}
            <div class="lg:col-span-5">

                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    Search
                </label>

                <div class="relative">

                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
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
                        @keydown.enter="refresh()"
                        placeholder="Search messages..."
                        class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-400 focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-gray-600 dark:focus:ring-gray-700"
                    >

                </div>

            </div>


            {{-- Level --}}
            <div class="lg:col-span-3">

                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    Level
                </label>

                <select
                    x-model="level"
                    @change="refresh()"
                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-400 focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">All Levels</option>
                    <option value="emergency">Emergency</option>
                    <option value="alert">Alert</option>
                    <option value="critical">Critical</option>
                    <option value="error">Error</option>
                    <option value="warning">Warning</option>
                    <option value="notice">Notice</option>
                    <option value="info">Info</option>
                    <option value="debug">Debug</option>
                </select>

            </div>


            {{-- Limit --}}
            <div class="lg:col-span-2">

                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    Entries
                </label>

                <select
                    x-model="limit"
                    @change="refresh()"
                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-400 focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="250">250</option>
                    <option value="500">500</option>
                </select>

            </div>


            {{-- Apply --}}
            <div class="flex items-end lg:col-span-2">

                <button
                    type="button"
                    @click="refresh()"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                >
                    Apply Filters
                </button>

            </div>

        </div>

    </div>


    {{-- ================================================================
         LOG ACTIONS
         ================================================================ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">

        <div class="text-sm text-gray-500 dark:text-gray-400">
            <span
                class="font-medium text-gray-900 dark:text-white"
                x-text="entries.length"
            ></span>

            entries loaded
        </div>

        <div class="flex items-center gap-2">

            {{-- Download --}}
            <a
                href="/api/admin/operations/logs/download"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"
                    />
                </svg>

                Download
            </a>


            {{-- Clear --}}
            <button
                type="button"
                @click="confirmClear()"
                class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-900/50 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-900/20"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3m-9 0h12"
                    />
                </svg>

                Clear Logs
            </button>

        </div>

    </div>


    {{-- ================================================================
         LOG TABLE
         ================================================================ --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">

        {{-- Loading --}}
        <template x-if="loading && entries.length === 0">
            <div class="flex items-center justify-center px-6 py-16">

                <svg
                    class="h-7 w-7 animate-spin text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h5M20 20v-5h-5M5 19A9 9 0 1119 5"
                    />
                </svg>

            </div>
        </template>


        {{-- Empty --}}
        <template x-if="!loading && entries.length === 0">

            <div class="px-6 py-16 text-center">

                <svg
                    class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M9 12h6m-6 4h4m5-10v12a2 2 0 01-2 2H8a2 2 0 01-2-2V6m2 0V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0H5"
                    />
                </svg>

                <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">
                    No log entries found
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Try changing your search or level filter.
                </p>

            </div>

        </template>


        {{-- Table --}}
        <template x-if="entries.length > 0">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                    <thead class="bg-gray-50 dark:bg-gray-900/50">

                        <tr>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Time
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Level
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Environment
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Message
                            </th>

                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                        <template
                            x-for="entry in entries"
                            :key="entry.id"
                        >

                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30">

                                {{-- Time --}}
                                <td class="whitespace-nowrap px-5 py-4 align-top">

                                    <div
                                        class="text-xs font-medium text-gray-900 dark:text-white"
                                        x-text="formatTime(entry.timestamp)"
                                    ></div>

                                </td>


                                {{-- Level --}}
                                <td class="whitespace-nowrap px-5 py-4 align-top">

                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="levelClass(entry.level)"
                                        x-text="(entry.level || 'unknown').toUpperCase()"
                                    ></span>

                                </td>


                                {{-- Environment --}}
                                <td class="whitespace-nowrap px-5 py-4 align-top">

                                    <span class="text-xs text-gray-600 dark:text-gray-300">
                                        <span x-text="entry.environment || '—'"></span>
                                    </span>

                                </td>


                                {{-- Message --}}
                                <td class="max-w-xl px-5 py-4 align-top">

                                    <div
                                        class="truncate text-sm font-medium text-gray-900 dark:text-white"
                                        x-text="entry.message || '—'"
                                    ></div>

                                    <template x-if="entry.details">

                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Multiline details available
                                        </div>

                                    </template>

                                </td>


                                {{-- Action --}}
                                <td class="whitespace-nowrap px-5 py-4 text-right align-top">

                                    <button
                                        type="button"
                                        @click="viewEntry(entry)"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
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

    </div>


    {{-- ================================================================
         FOOTER
         ================================================================ --}}
    <div class="flex flex-col gap-2 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between dark:text-gray-400">

        <span>
            Showing latest
            <span x-text="entries.length"></span>
            entries
        </span>

        <span>
            Last refreshed:
            <span x-text="lastUpdated || '—'"></span>
        </span>

    </div>


    {{-- ================================================================
         DETAIL MODAL
         ================================================================ --}}
    <template x-if="selectedEntry">

        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="selectedEntry = null"
        >

            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/50"
                @click="selectedEntry = null"
            ></div>


            {{-- Modal --}}
            <div
                class="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800"
                @click.stop
            >

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">

                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Log Entry
                        </h2>

                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            Application log details
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="selectedEntry = null"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                </div>


                {{-- Content --}}
                <div class="overflow-y-auto p-6">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                Timestamp
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-900 dark:text-white"
                                x-text="selectedEntry.timestamp || '—'"
                            ></p>
                        </div>


                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                Level
                            </p>

                            <span
                                class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="levelClass(selectedEntry.level)"
                                x-text="(selectedEntry.level || 'unknown').toUpperCase()"
                            ></span>
                        </div>


                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                Environment
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-900 dark:text-white"
                                x-text="selectedEntry.environment || '—'"
                            ></p>
                        </div>

                    </div>


                    {{-- Message --}}
                    <div class="mt-6">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Message
                        </p>

                        <div class="mt-2 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">

                            <pre
                                class="whitespace-pre-wrap break-words font-mono text-sm leading-6 text-gray-800 dark:text-gray-200"
                                x-text="selectedEntry.message || '—'"
                            ></pre>

                        </div>

                    </div>


                    {{-- Context --}}
                    <template x-if="selectedEntry.context">

                        <div class="mt-6">

                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                Context
                            </p>

                            <pre
                                class="mt-2 overflow-x-auto rounded-lg bg-gray-900 p-4 font-mono text-xs leading-5 text-gray-200"
                                x-text="JSON.stringify(selectedEntry.context, null, 2)"
                            ></pre>

                        </div>

                    </template>


                    {{-- Details --}}
                    <template x-if="selectedEntry.details">

                        <div class="mt-6">

                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                Details / Stack Trace
                            </p>

                            <pre
                                class="mt-2 max-h-96 overflow-auto rounded-lg bg-gray-900 p-4 font-mono text-xs leading-5 text-gray-200"
                                x-text="selectedEntry.details"
                            ></pre>

                        </div>

                    </template>


                    {{-- Raw --}}
                    <div class="mt-6">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Raw Entry
                        </p>

                        <pre
                            class="mt-2 max-h-64 overflow-auto rounded-lg bg-gray-50 p-4 font-mono text-xs leading-5 text-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            x-text="selectedEntry.raw || '—'"
                        ></pre>

                    </div>

                </div>


                {{-- Footer --}}
                <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-700">

                    <button
                        type="button"
                        @click="selectedEntry = null"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900"
                    >
                        Close
                    </button>

                </div>

            </div>

        </div>

    </template>


    {{-- ================================================================
         CLEAR CONFIRMATION
         ================================================================ --}}
    <template x-if="showClearConfirm">

        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">

            <div
                class="absolute inset-0 bg-black/50"
                @click="showClearConfirm = false"
            ></div>

            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-800">

                <div class="flex items-start gap-4">

                    <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/30">

                        <svg
                            class="h-6 w-6 text-red-600 dark:text-red-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3m-9 0h12"
                            />
                        </svg>

                    </div>

                    <div>

                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Clear application logs?
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                            This will permanently remove the contents of
                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                laravel.log
                            </span>.
                            This action cannot be undone.
                        </p>

                    </div>

                </div>


                <div class="mt-6 flex justify-end gap-3">

                    <button
                        type="button"
                        @click="showClearConfirm = false"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="clearLogs()"
                        :disabled="clearing"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span x-show="!clearing">
                            Clear Logs
                        </span>

                        <span x-show="clearing">
                            Clearing...
                        </span>
                    </button>

                </div>

            </div>

        </div>

    </template>

</div>


<script>
function operationsSystemLogs() {
    return {
        entries: [],

        selectedEntry: null,

        file: {
            exists: false,
            filename: 'laravel.log',
            size: 0,
            size_human: '0 B',
            last_modified: null,
        },

        search: '',
        level: '',
        limit: 100,

        loading: false,
        clearing: false,

        error: null,

        autoRefresh: false,
        refreshTimer: null,

        lastUpdated: null,

        async init() {
            await this.refresh();
        },

        async refresh() {
            if (this.loading) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const params = new URLSearchParams();

                params.set('limit', this.limit);

                if (this.level) {
                    params.set('level', this.level);
                }

                if (this.search.trim()) {
                    params.set('search', this.search.trim());
                }

                const response = await fetch(
                    `/api/admin/operations/logs/entries?${params.toString()}`,
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Unable to load application logs.'
                    );
                }

                this.entries = result.data?.entries || [];

                this.file = result.data?.file || this.file;

                this.lastUpdated = this.now();

            } catch (error) {
                console.error(error);

                this.error =
                    error.message ||
                    'Unable to load application logs.';
            } finally {
                this.loading = false;
            }
        },

        async viewEntry(entry) {
            /*
             * Open immediately using the already-loaded entry.
             * Then fetch the complete server-side entry in case
             * additional details are available.
             */
            this.selectedEntry = entry;

            try {
                const response = await fetch(
                    `/api/admin/operations/logs/${encodeURIComponent(entry.id)}`,
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result = await response.json();

                if (response.ok && result.success && result.data) {
                    this.selectedEntry = result.data;
                }

            } catch (error) {
                console.error(error);
            }
        },

        confirmClear() {
            this.showClearConfirm = true;
        },

        async clearLogs() {
            if (this.clearing) {
                return;
            }

            this.clearing = true;

            try {
                const response = await fetch(
                    '/api/admin/operations/logs/clear',
                    {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Unable to clear logs.'
                    );
                }

                this.showClearConfirm = false;

                this.entries = [];

                this.file = result.data?.file || this.file;

                this.lastUpdated = this.now();

            } catch (error) {
                console.error(error);

                this.error =
                    error.message ||
                    'Unable to clear application logs.';

                this.showClearConfirm = false;

            } finally {
                this.clearing = false;
            }
        },

        toggleAutoRefresh() {
            this.autoRefresh = !this.autoRefresh;

            if (this.autoRefresh) {

                this.startAutoRefresh();

            } else {

                this.stopAutoRefresh();

            }
        },

        startAutoRefresh() {
            this.stopAutoRefresh();

            this.refreshTimer = setInterval(
                () => this.refresh(),
                10000
            );
        },

        stopAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }
        },

        levelClass(level) {
            switch ((level || '').toLowerCase()) {

                case 'emergency':
                case 'alert':
                case 'critical':
                    return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';

                case 'error':
                    return 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400';

                case 'warning':
                    return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';

                case 'notice':
                    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';

                case 'info':
                    return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';

                case 'debug':
                    return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';

                default:
                    return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
            }
        },

        formatTime(timestamp) {
            if (!timestamp) {
                return '—';
            }

            return timestamp;
        },

        now() {
            return new Date().toLocaleString();
        },

        destroy() {
            this.stopAutoRefresh();
        },

        showClearConfirm: false,
    };
}
</script>

@endsection