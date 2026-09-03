<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

    {{-- Total Deployments --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">
                    Total Deployments
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-gray-900"
                    x-text="overview.statistics?.total ?? 0"
                >
                    0
                </p>
            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100">
                <svg
                    class="h-5 w-5 text-gray-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M14.5 4.5c3.5-1 5-1 5-1s0 1.5-1 5l-4.5 4.5-3-3L14.5 4.5Z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m11 10-5 1-2 2 5 1m2 0 1 5-2 2-1-5m-4-4 3 3"
                    />
                </svg>
            </div>
        </div>
    </div>

    {{-- Successful --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">
                    Successful
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-gray-900"
                    x-text="overview.statistics?.successful ?? 0"
                >
                    0
                </p>
            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50">
                <svg
                    class="h-5 w-5 text-green-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m8 12 2.5 2.5L16 9"
                    />
                </svg>
            </div>
        </div>
    </div>

    {{-- Failed --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">
                    Failed
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-gray-900"
                    x-text="overview.statistics?.failed ?? 0"
                >
                    0
                </p>
            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50">
                <svg
                    class="h-5 w-5 text-red-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m9 9 6 6m0-6-6 6"
                    />
                </svg>
            </div>
        </div>
    </div>

    {{-- Current Status --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">

            <div>
                <p class="text-sm font-medium text-gray-500">
                    Current Status
                </p>

                <span
                    class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="{
                        'bg-gray-100 text-gray-700': !deploymentRunning,
                        'bg-blue-50 text-blue-700': deploymentRunning
                    }"
                    x-text="deploymentRunning ? 'Deploying' : 'Idle'"
                >
                    Idle
                </span>
            </div>

            <div
                class="flex h-10 w-10 items-center justify-center rounded-lg"
                :class="deploymentRunning ? 'bg-blue-50' : 'bg-gray-100'"
            >
                <svg
                    class="h-5 w-5"
                    :class="deploymentRunning ? 'text-blue-600' : 'text-gray-600'"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 12h4l2.25-6 4.5 12L16 12h5"
                    />
                </svg>
            </div>

        </div>
    </div>

</div>