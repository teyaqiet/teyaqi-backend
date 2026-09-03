<div class="rounded-xl border border-gray-200 bg-white shadow-sm">

    {{-- Header --}}
    <div class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Deployment Pipeline
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Monitor every deployment stage in real time.
            </p>
        </div>

        <div class="flex items-center gap-2">

            {{-- Deployment status --}}
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                :class="deploymentRunning
                    ? 'bg-blue-50 text-blue-700'
                    : 'bg-gray-100 text-gray-600'"
            >
                <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="deploymentRunning
                        ? 'bg-blue-500 animate-pulse'
                        : 'bg-gray-400'"
                ></span>

                <span
                    x-text="deploymentRunning ? 'Deployment Running' : 'Idle'"
                >
                    Idle
                </span>
            </span>

            {{-- Environment --}}
            <span
                class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600"
                x-text="deploymentConfig?.environment ?? 'staging'"
            >
                staging
            </span>

        </div>
    </div>


    {{-- Overall Progress --}}
    <div
        class="border-b border-gray-100 p-5"
        x-show="activeDeployment"
        x-cloak
    >

        <div class="flex items-center justify-between">

            <div>
                <p class="text-sm font-semibold text-gray-900">
                    Overall Progress
                </p>

                <p
                    class="mt-1 text-xs text-gray-500"
                    x-text="activeDeployment
                        ? '#' + activeDeployment.id + ' · ' + formatStatus(activeDeployment.status)
                        : 'No active deployment'"
                >
                    No active deployment
                </p>
            </div>

            <div class="text-right">
                <p
                    class="text-2xl font-bold text-gray-900"
                    x-text="deploymentProgress + '%'"
                >
                    0%
                </p>
            </div>

        </div>


        {{-- Overall progress bar --}}
        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-gray-100">

            <div
                class="h-full rounded-full transition-all duration-500"
                :class="
                    activeDeployment?.status === 'failed'
                        ? 'bg-red-500'
                        : activeDeployment?.status === 'completed'
                            ? 'bg-green-500'
                            : 'bg-blue-500'
                "
                :style="`width: ${deploymentProgress}%`"
            ></div>

        </div>


        {{-- Current stage --}}
        <div
            class="mt-3 flex items-center justify-between text-xs"
        >

            <span class="text-gray-500">
                Current stage
            </span>

            <span
                class="font-medium text-gray-700"
                x-text="
                    runningStep()
                        ? stepLabels[runningStep()]
                        : (
                            activeDeployment?.status === 'completed'
                                ? 'Deployment completed'
                                : activeDeployment?.status === 'failed'
                                    ? 'Deployment failed'
                                    : 'Preparing deployment'
                        )
                "
            >
                Preparing deployment
            </span>

        </div>

    </div>


    {{-- Pipeline --}}
    <div class="p-5">

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">


            {{-- Git --}}
            <div
                class="rounded-lg border p-4 transition"
                :class="pipelineStepClass('git')"
            >

                <div class="flex items-start gap-4">

                    {{-- Icon --}}
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :class="pipelineIconClass('git')"
                    >

                        <template x-if="stepStatus('git') === 'completed'">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m5 12 4 4L19 6"
                                />
                            </svg>
                        </template>

                        <template x-if="stepStatus('git') === 'failed'">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 6l12 12M18 6 6 18"
                                />
                            </svg>
                        </template>

                        <template x-if="stepStatus('git') === 'running'">
                            <svg
                                class="h-5 w-5 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="8"
                                    class="opacity-25"
                                />

                                <path
                                    stroke-linecap="round"
                                    d="M20 12a8 8 0 0 0-8-8"
                                />
                            </svg>
                        </template>

                        <template x-if="stepStatus('git') === 'pending'">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="8"
                                />
                            </svg>
                        </template>

                    </div>


                    <div class="min-w-0 flex-1">

                        <div class="flex items-center justify-between gap-3">

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Git
                                </p>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    Pull latest source code
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('git')"
                                x-text="formatStatus(stepStatus('git'))"
                            >
                                Pending
                            </span>

                        </div>


                        {{-- Progress --}}
                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">

                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('git')"
                                :style="
                                    stepStatus('git') === 'running'
                                        ? 'width: 70%'
                                        : `width: ${stepProgress('git')}%`
                                "
                            ></div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Composer --}}
            <div
                class="rounded-lg border p-4 transition"
                :class="pipelineStepClass('composer')"
            >

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :class="pipelineIconClass('composer')"
                    >

                        <template x-if="stepStatus('composer') === 'completed'">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m5 12 4 4L19 6"
                                />
                            </svg>
                        </template>

                        <template x-if="stepStatus('composer') === 'failed'">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 6l12 12M18 6 6 18"
                                />
                            </svg>
                        </template>

                        <template x-if="stepStatus('composer') === 'running'">
                            <svg
                                class="h-5 w-5 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="8"
                                    class="opacity-25"
                                />

                                <path
                                    stroke-linecap="round"
                                    d="M20 12a8 8 0 0 0-8-8"
                                />
                            </svg>
                        </template>

                        <template x-if="stepStatus('composer') === 'pending'">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="8"
                                />
                            </svg>
                        </template>

                    </div>


                    <div class="min-w-0 flex-1">

                        <div class="flex items-center justify-between gap-3">

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Composer
                                </p>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    PHP dependencies
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('composer')"
                                x-text="formatStatus(stepStatus('composer'))"
                            >
                                Pending
                            </span>

                        </div>


                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">

                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('composer')"
                                :style="
                                    stepStatus('composer') === 'running'
                                        ? 'width: 70%'
                                        : `width: ${stepProgress('composer')}%`
                                "
                            ></div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- NPM --}}
            <div
                class="rounded-lg border p-4 transition"
                :class="pipelineStepClass('npm')"
            >

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :class="pipelineIconClass('npm')"
                    >

                        <template x-if="stepStatus('npm') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('npm') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('npm') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" class="opacity-25" />
                                <path stroke-linecap="round" d="M20 12a8 8 0 0 0-8-8" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('npm') === 'pending'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" />
                            </svg>
                        </template>

                    </div>


                    <div class="min-w-0 flex-1">

                        <div class="flex items-center justify-between gap-3">

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    NPM
                                </p>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    Frontend dependencies
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('npm')"
                                x-text="formatStatus(stepStatus('npm'))"
                            >
                                Pending
                            </span>

                        </div>


                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">

                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('npm')"
                                :style="
                                    stepStatus('npm') === 'running'
                                        ? 'width: 70%'
                                        : `width: ${stepProgress('npm')}%`
                                "
                            ></div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Build --}}
            <div
                class="rounded-lg border p-4 transition"
                :class="pipelineStepClass('build')"
            >

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :class="pipelineIconClass('build')"
                    >

                        <template x-if="stepStatus('build') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('build') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('build') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" class="opacity-25" />
                                <path stroke-linecap="round" d="M20 12a8 8 0 0 0-8-8" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('build') === 'pending'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" />
                            </svg>
                        </template>

                    </div>


                    <div class="min-w-0 flex-1">

                        <div class="flex items-center justify-between gap-3">

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Frontend Build
                                </p>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    Production build
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('build')"
                                x-text="formatStatus(stepStatus('build'))"
                            >
                                Pending
                            </span>

                        </div>


                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">

                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('build')"
                                :style="
                                    stepStatus('build') === 'running'
                                        ? 'width: 70%'
                                        : `width: ${stepProgress('build')}%`
                                "
                            ></div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Migrations --}}
            <div
                class="rounded-lg border p-4 transition"
                :class="pipelineStepClass('migrations')"
            >

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :class="pipelineIconClass('migrations')"
                    >

                        <template x-if="stepStatus('migrations') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('migrations') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('migrations') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" class="opacity-25" />
                                <path stroke-linecap="round" d="M20 12a8 8 0 0 0-8-8" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('migrations') === 'pending'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" />
                            </svg>
                        </template>

                    </div>


                    <div class="min-w-0 flex-1">

                        <div class="flex items-center justify-between gap-3">

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Database Migrations
                                </p>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    Run database migrations
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('migrations')"
                                x-text="formatStatus(stepStatus('migrations'))"
                            >
                                Pending
                            </span>

                        </div>


                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">

                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('migrations')"
                                :style="
                                    stepStatus('migrations') === 'running'
                                        ? 'width: 70%'
                                        : `width: ${stepProgress('migrations')}%`
                                "
                            ></div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Optimize --}}
            <div
                class="rounded-lg border p-4 transition"
                :class="pipelineStepClass('optimize')"
            >

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :class="pipelineIconClass('optimize')"
                    >

                        <template x-if="stepStatus('optimize') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('optimize') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('optimize') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" class="opacity-25" />
                                <path stroke-linecap="round" d="M20 12a8 8 0 0 0-8-8" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('optimize') === 'pending'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" />
                            </svg>
                        </template>

                    </div>


                    <div class="min-w-0 flex-1">

                        <div class="flex items-center justify-between gap-3">

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Laravel Optimize
                                </p>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    Application optimization
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('optimize')"
                                x-text="formatStatus(stepStatus('optimize'))"
                            >
                                Pending
                            </span>

                        </div>


                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">

                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('optimize')"
                                :style="
                                    stepStatus('optimize') === 'running'
                                        ? 'width: 70%'
                                        : `width: ${stepProgress('optimize')}%`
                                "
                            ></div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Queue Restart --}}
            <div
                class="rounded-lg border p-4 transition"
                :class="pipelineStepClass('queue_restart')"
            >

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :class="pipelineIconClass('queue_restart')"
                    >

                        <template x-if="stepStatus('queue_restart') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('queue_restart') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('queue_restart') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" class="opacity-25" />
                                <path stroke-linecap="round" d="M20 12a8 8 0 0 0-8-8" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('queue_restart') === 'pending'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" />
                            </svg>
                        </template>

                    </div>


                    <div class="min-w-0 flex-1">

                        <div class="flex items-center justify-between gap-3">

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Queue Restart
                                </p>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    Queue worker restart signal
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('queue_restart')"
                                x-text="formatStatus(stepStatus('queue_restart'))"
                            >
                                Pending
                            </span>

                        </div>


                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">

                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('queue_restart')"
                                :style="
                                    stepStatus('queue_restart') === 'running'
                                        ? 'width: 70%'
                                        : `width: ${stepProgress('queue_restart')}%`
                                "
                            ></div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Health Check --}}
            <div
                class="rounded-lg border p-4 transition md:col-span-2"
                :class="pipelineStepClass('health_check')"
            >

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :class="pipelineIconClass('health_check')"
                    >

                        <template x-if="stepStatus('health_check') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('health_check') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('health_check') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" class="opacity-25" />
                                <path stroke-linecap="round" d="M20 12a8 8 0 0 0-8-8" />
                            </svg>
                        </template>

                        <template x-if="stepStatus('health_check') === 'pending'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" />
                            </svg>
                        </template>

                    </div>


                    <div class="min-w-0 flex-1">

                        <div class="flex items-center justify-between gap-3">

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Health Check
                                </p>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    Verify application health
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('health_check')"
                                x-text="formatStatus(stepStatus('health_check'))"
                            >
                                Pending
                            </span>

                        </div>


                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">

                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('health_check')"
                                :style="
                                    stepStatus('health_check') === 'running'
                                        ? 'width: 70%'
                                        : `width: ${stepProgress('health_check')}%`
                                "
                            ></div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>