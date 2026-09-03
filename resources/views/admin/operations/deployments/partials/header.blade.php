<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-3">
            <a
     
    href="{{ url('/admin/operations') }}"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-900"
                title="Back to Operations"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15.75 19.5 8.25 12l7.5-7.5"
                    />
                </svg>
            </a>

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Deployments
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Deploy and monitor application updates.
                </p>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        {{-- Refresh --}}
        <button
            type="button"
            @click="refresh()"
            :disabled="loading"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <svg
                class="h-4 w-4"
                :class="{ 'animate-spin': loading }"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.8"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16.023 9.348h4.992V4.356M20.49 9.348A9 9 0 1 1 18.36 5.64"
                />
            </svg>

            <span>Refresh</span>
        </button>

        {{-- Deploy --}}
        <button
            type="button"
            @click="openDeployModal()"
            :disabled="deploymentRunning"
            class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.8"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 16.5V3.75m0 0 4.5 4.5M12 3.75l-4.5 4.5M5.25 14.25v3.375A2.625 2.625 0 0 0 7.875 20.25h8.25a2.625 2.625 0 0 0 2.625-2.625V14.25"
                />
            </svg>

            <span
                x-text="deploymentRunning
                    ? 'Deployment Running...'
                    : 'Deploy'"
            >
                Deploy
            </span>
        </button>
    </div>
</div>
