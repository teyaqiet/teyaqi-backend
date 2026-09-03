<div class="rounded-xl border border-gray-200 bg-white shadow-sm">

    {{-- Header --}}
    <div class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Deployment History
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Recent deployments and their results.
            </p>
        </div>

        <button
            type="button"
            @click="loadHistory()"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8.1 8.1 0 0 0-14.8-4M4 5v4h4M4 13a8.1 8.1 0 0 0 14.8 4M20 19v-4h-4" /></svg>

            Refresh
        </button>
    </div>

    {{-- Desktop --}}
    <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Deployment
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Environment
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Branch
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Status
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Duration
                    </th>

                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 bg-white">

                <template x-if="historyLoading">
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3" /></svg>
                                Loading deployment history...
                            </div>
                        </td>
                    </tr>
                </template>

                <template x-if="!historyLoading && history.length === 0">
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37 12 18l-3.59-3.63M12 18v3m0-3V9m0 0 3-3m-3 3L9 6M5.25 15.75l-1.5 1.5M18.75 15.75l1.5 1.5" /></svg>
                                </div>

                                <p class="mt-3 text-sm font-medium text-gray-900">
                                    No deployments yet
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Your deployment history will appear here.
                                </p>
                            </div>
                        </td>
                    </tr>
                </template>

                <template x-for="deployment in history" :key="deployment.id">
                    <tr class="transition hover:bg-gray-50">

                        {{-- Deployment --}}
                        <td class="whitespace-nowrap px-5 py-4">
                            <div>
                                <p
                                    class="text-sm font-semibold text-gray-900"
                                    x-text="'#' + deployment.id"
                                ></p>

                                <p
                                    class="mt-0.5 max-w-xs truncate text-xs text-gray-500"
                                    x-text="deployment.commit_message || deployment.commit_hash || 'No commit message'"
                                ></p>
                            </div>
                        </td>

                        {{-- Environment --}}
                        <td class="whitespace-nowrap px-5 py-4">
                            <span
                                class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700"
                                x-text="deployment.environment || 'staging'"
                            ></span>
                        </td>

                        {{-- Branch --}}
                        <td class="whitespace-nowrap px-5 py-4">
                            <code
                                class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700"
                                x-text="deployment.branch || 'main'"
                            ></code>
                        </td>

                        {{-- Status --}}
                        <td class="whitespace-nowrap px-5 py-4">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="deploymentStatusClass(deployment.status)"
                                x-text="formatStatus(deployment.status)"
                            ></span>
                        </td>

                        {{-- Duration --}}
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                            <span
                                x-text="formatDuration(deployment.duration_seconds)"
                            ></span>
                        </td>

                        {{-- Action --}}
                        <td class="whitespace-nowrap px-5 py-4 text-right">
                            <button
                                type="button"
                                @click="showDeploymentDetails(deployment.id)"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6 9.75-6 9.75 6 9.75 6-3.75 6-9.75 6-9.75-6-9.75-6Z" /><circle cx="12" cy="12" r="2.5" /></svg>

                                View
                            </button>
                        </td>

                    </tr>
                </template>

            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    <div class="divide-y divide-gray-100 md:hidden">

        <template x-if="historyLoading">
            <div class="flex items-center justify-center gap-2 p-8 text-sm text-gray-500">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3" /></svg>
                Loading...
            </div>
        </template>

        <template x-if="!historyLoading && history.length === 0">
            <div class="p-8 text-center">
                <p class="text-sm font-medium text-gray-900">
                    No deployments yet
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    Your deployment history will appear here.
                </p>
            </div>
        </template>

        <template x-for="deployment in history" :key="deployment.id">
            <div class="p-5">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p
                            class="text-sm font-semibold text-gray-900"
                            x-text="'Deployment #' + deployment.id"
                        ></p>

                        <p
                            class="mt-1 truncate text-xs text-gray-500"
                            x-text="deployment.commit_message || deployment.commit_hash || 'No commit message'"
                        ></p>
                    </div>

                    <span
                        class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                        :class="deploymentStatusClass(deployment.status)"
                        x-text="formatStatus(deployment.status)"
                    ></span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-xs">

                    <div>
                        <p class="text-gray-400">
                            Environment
                        </p>

                        <p
                            class="mt-1 font-medium text-gray-700"
                            x-text="deployment.environment || 'staging'"
                        ></p>
                    </div>

                    <div>
                        <p class="text-gray-400">
                            Branch
                        </p>

                        <p
                            class="mt-1 font-medium text-gray-700"
                            x-text="deployment.branch || 'main'"
                        ></p>
                    </div>

                    <div>
                        <p class="text-gray-400">
                            Duration
                        </p>

                        <p
                            class="mt-1 font-medium text-gray-700"
                            x-text="formatDuration(deployment.duration_seconds)"
                        ></p>
                    </div>

                    <div>
                        <p class="text-gray-400">
                            Created
                        </p>

                        <p
                            class="mt-1 font-medium text-gray-700"
                            x-text="formatDate(deployment.created_at)"
                        ></p>
                    </div>

                </div>

                <button
                    type="button"
                    @click="showDeploymentDetails(deployment.id)"
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6 9.75-6 9.75 6 9.75 6-3.75 6-9.75 6-9.75-6-9.75-6Z" /><circle cx="12" cy="12" r="2.5" /></svg>

                    View Details
                </button>

            </div>
        </template>

    </div>

</div>