<div
    x-show="showDeployModal"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
    x-transition.opacity
>
    <div
        class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
        @click.outside="closeDeployModal()"
        x-transition
    >
        {{-- Header --}}
        <div class="border-b border-gray-100 p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-100">
                    <svg class="h-5 w-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 4.5c3.5-1 5-1 5-1s0 1.5-1 5l-4.5 4.5-3-3L14.5 4.5Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11 10-5 1-2 2 5 1m2 0 1 5-2 2-1-5m-4-4 3 3" />
                    </svg>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Start Deployment
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Review the deployment target before continuing.
                    </p>
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div class="space-y-4 p-6">

            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <p class="text-xs text-gray-500">
                            Environment
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold text-gray-900"
                            x-text="deploymentConfig.environment || 'staging'"
                        ></p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">
                            Branch
                        </p>

                        <p
                            class="mt-1 font-mono text-sm font-semibold text-gray-900"
                            x-text="deploymentConfig.branch || 'main'"
                        ></p>
                    </div>

                </div>
            </div>

            <div class="flex items-start gap-3 rounded-lg bg-yellow-50 p-4">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M10.29 3.86 2.82 17.25A1.5 1.5 0 0 0 4.12 19.5h15.76a1.5 1.5 0 0 0 1.3-2.25L13.71 3.86a1.95 1.95 0 0 0-3.42 0Z" />
                </svg>

                <p class="text-xs leading-5 text-yellow-800">
                    The deployment will update the application using the
                    configured deployment pipeline.
                </p>
            </div>

        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 p-5">

            <button
                type="button"
                @click="closeDeployModal()"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
                Cancel
            </button>

            <button
                type="button"
                @click="beginDeployment()"
                class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 19.5 6v5.25c0 4.45-3.15 7.72-7.5 9-4.35-1.28-7.5-4.55-7.5-9V6L12 3.75Z" />
                </svg>

                Run Pre-flight Check
            </button>

        </div>
    </div>
</div>