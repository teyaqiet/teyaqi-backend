<div class="rounded-xl border border-gray-200 bg-white shadow-sm">

    {{-- Header --}}
    <div class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Deployment Pipeline
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Current deployment pipeline configuration.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span
                class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700"
            >
                <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-green-500"></span>
                Enabled
            </span>

            <span
                class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600"
                x-text="deploymentConfig?.environment ?? 'staging'"
            >
                staging
            </span>
        </div>
    </div>

    {{-- Pipeline --}}
    <div class="p-5">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">

            {{-- Composer --}}
            <div class="flex items-center gap-4 rounded-lg border border-gray-100 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100">
<svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V6.75Z" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h5M8 16h3" />
</svg>                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                        Composer
                    </p>

                    <p class="mt-0.5 text-xs text-gray-500">
                        PHP dependencies
                    </p>
                </div>

                <span
                    class="h-2 w-2 rounded-full"
                    :class="deploymentConfig?.pipeline?.composer?.enabled
                        ? 'bg-green-500'
                        : 'bg-gray-300'"
                ></span>
            </div>

            {{-- NPM --}}
            <div class="flex items-center gap-4 rounded-lg border border-gray-100 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100">
<svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
    <rect x="4" y="4" width="6" height="6" rx="1" />
    <rect x="14" y="4" width="6" height="6" rx="1" />
    <rect x="4" y="14" width="6" height="6" rx="1" />
    <rect x="14" y="14" width="6" height="6" rx="1" />
</svg>                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                        NPM
                    </p>

                    <p class="mt-0.5 text-xs text-gray-500">
                        Frontend dependencies
                    </p>
                </div>

                <span
                    class="h-2 w-2 rounded-full"
                    :class="deploymentConfig?.pipeline?.npm?.enabled
                        ? 'bg-green-500'
                        : 'bg-gray-300'"
                ></span>
            </div>

            {{-- Build --}}
            <div class="flex items-center gap-4 rounded-lg border border-gray-100 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100">
<svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" d="m14.25 4.5-9 9 5.25 5.25 9-9-5.25-5.25Z" />
    <path stroke-linecap="round" stroke-linejoin="round" d="m13 6 5 5M7.5 12.5l4 4" />
</svg>                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                        Frontend Build
                    </p>

                    <p class="mt-0.5 text-xs text-gray-500">
                        Production build
                    </p>
                </div>

                <span
                    class="h-2 w-2 rounded-full"
                    :class="deploymentConfig?.pipeline?.build?.enabled
                        ? 'bg-green-500'
                        : 'bg-gray-300'"
                ></span>
            </div>

            {{-- Migrations --}}
            <div class="flex items-center gap-4 rounded-lg border border-gray-100 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100">
<svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
    <ellipse cx="12" cy="5.5" rx="7.5" ry="3" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 5.5v6c0 1.66 3.36 3 7.5 3s7.5-1.34 7.5-3v-6" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 11.5v6c0 1.66 3.36 3 7.5 3s7.5-1.34 7.5-3v-6" />
</svg>                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                        Migrations
                    </p>

                    <p class="mt-0.5 text-xs text-gray-500">
                        Database migrations
                    </p>
                </div>

                <span
                    class="h-2 w-2 rounded-full"
                    :class="deploymentConfig?.pipeline?.migrations?.enabled
                        ? 'bg-green-500'
                        : 'bg-gray-300'"
                ></span>
            </div>

            {{-- Optimize --}}
            <div class="flex items-center gap-4 rounded-lg border border-gray-100 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100">
<svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" d="m13 2-8 12h6l-1 8 8-12h-6l1-8Z" />
</svg>                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                        Laravel Optimize
                    </p>

                    <p class="mt-0.5 text-xs text-gray-500">
                        Application optimization
                    </p>
                </div>

                <span
                    class="h-2 w-2 rounded-full"
                    :class="deploymentConfig?.pipeline?.optimize?.enabled
                        ? 'bg-green-500'
                        : 'bg-gray-300'"
                ></span>
            </div>

            {{-- Queue Restart --}}
            <div class="flex items-center gap-4 rounded-lg border border-gray-100 p-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100">
<svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5A8 8 0 0 1 18.5 5L20 7" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M20 3.5v3.5h-3.5" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M20 16.5A8 8 0 0 1 5.5 19L4 17" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20.5V17h3.5" />
</svg>                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                        Queue Restart
                    </p>

                    <p class="mt-0.5 text-xs text-gray-500">
                        Queue worker restart signal
                    </p>
                </div>

                <span
                    class="h-2 w-2 rounded-full"
                    :class="deploymentConfig?.pipeline?.queue_restart?.enabled
                        ? 'bg-green-500'
                        : 'bg-gray-300'"
                ></span>
            </div>

            {{-- Health Check --}}
            <div class="flex items-center gap-4 rounded-lg border border-gray-100 p-4 md:col-span-2">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100">
<svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2-5 4 10 2-5h6" />
</svg>                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                        Health Check
                    </p>

                    <p class="mt-0.5 text-xs text-gray-500">
                        Verify the application after deployment
                    </p>
                </div>

                <span
                    class="h-2 w-2 rounded-full"
                    :class="deploymentConfig?.pipeline?.health_check?.enabled
                        ? 'bg-green-500'
                        : 'bg-gray-300'"
                ></span>
            </div>

        </div>
    </div>
</div>