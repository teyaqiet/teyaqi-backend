@if(isset($activeDeployment))
<div
    x-show="activeDeployment"
    x-cloak
    class="rounded-xl border border-gray-200 bg-white shadow-sm"
>
    {{-- Header --}}
    <div class="border-b border-gray-100 p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Active Deployment
                    </h2>

                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                        :class="deploymentStatusClass(activeDeployment?.status)"
                        x-text="formatStatus(activeDeployment?.status)"
                    >
                    </span>
                </div>

                <p class="mt-1 text-sm text-gray-500">
                    Deployment
                    <span
                        class="font-medium text-gray-700"
                        x-text="'#' + (activeDeployment?.id ?? '')"
                    ></span>

                    ·

                    <span
                        x-text="activeDeployment?.branch ?? 'main'"
                    ></span>
                </p>
            </div>

            <div class="text-left sm:text-right">
                <p class="text-xs text-gray-500">
                    Environment
                </p>

                <p
                    class="mt-1 text-sm font-semibold text-gray-900"
                    x-text="activeDeployment?.environment ?? 'staging'"
                ></p>
            </div>
        </div>
    </div>

    {{-- Overall Progress --}}
    <div class="border-b border-gray-100 p-5">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">
                Deployment Progress
            </span>

            <span
                class="text-sm font-semibold text-gray-900"
                x-text="deploymentProgress + '%'"
            >
                0%
            </span>
        </div>

        <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
            <div
                class="h-full rounded-full transition-all duration-500"
                :class="{
                    'bg-blue-600': activeDeployment?.status === 'running',
                    'bg-green-600': activeDeployment?.status === 'completed',
                    'bg-red-600': activeDeployment?.status === 'failed',
                    'bg-gray-400': !['running', 'completed', 'failed'].includes(activeDeployment?.status)
                }"
                :style="'width: ' + deploymentProgress + '%'"
            ></div>
        </div>

        <div class="mt-2 flex justify-between text-xs text-gray-400">
            <span>Started</span>

            <span
                x-text="formatDate(activeDeployment?.started_at)"
            ></span>
        </div>
    </div>

    {{-- Pipeline Steps --}}
    <div class="p-5">
        <div class="space-y-4">

            {{-- Git --}}
            <div
                class="rounded-lg border border-gray-100 p-4"
                :class="pipelineStepClass('git')"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="pipelineIconClass('git')"
                    >
                        <template x-if="stepStatus('git') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                        </template>

                        <template x-if="stepStatus('git') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M7.757 16.243l-2.121 2.121m12.728 0-2.121-2.121M7.757 7.757 5.636 5.636" /></svg>
                        </template>

                        <template x-if="stepStatus('git') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </template>

                        <template x-if="stepStatus('git') === 'pending'">
                            <span class="text-sm font-semibold">1</span>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    Git Update
                                </p>

                                <p class="text-xs text-gray-500">
                                    Fetch latest code
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('git')"
                                x-text="formatStatus(stepStatus('git'))"
                            ></span>
                        </div>

                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('git')"
                                :style="'width:' + stepProgress('git') + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Composer --}}
            <div
                class="rounded-lg border border-gray-100 p-4"
                :class="pipelineStepClass('composer')"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="pipelineIconClass('composer')"
                    >
                        <template x-if="stepStatus('composer') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                        </template>

                        <template x-if="stepStatus('composer') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M7.757 16.243l-2.121 2.121m12.728 0-2.121-2.121M7.757 7.757 5.636 5.636" /></svg>
                        </template>

                        <template x-if="stepStatus('composer') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </template>

                        <template x-if="stepStatus('composer') === 'pending'">
                            <span class="text-sm font-semibold">2</span>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    Composer
                                </p>

                                <p class="text-xs text-gray-500">
                                    Install PHP dependencies
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('composer')"
                                x-text="formatStatus(stepStatus('composer'))"
                            ></span>
                        </div>

                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('composer')"
                                :style="'width:' + stepProgress('composer') + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- NPM --}}
            <div
                class="rounded-lg border border-gray-100 p-4"
                :class="pipelineStepClass('npm')"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="pipelineIconClass('npm')"
                    >
                        <template x-if="stepStatus('npm') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                        </template>

                        <template x-if="stepStatus('npm') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M7.757 16.243l-2.121 2.121m12.728 0-2.121-2.121M7.757 7.757 5.636 5.636" /></svg>
                        </template>

                        <template x-if="stepStatus('npm') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </template>

                        <template x-if="stepStatus('npm') === 'pending'">
                            <span class="text-sm font-semibold">3</span>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    NPM
                                </p>

                                <p class="text-xs text-gray-500">
                                    Install frontend dependencies
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('npm')"
                                x-text="formatStatus(stepStatus('npm'))"
                            ></span>
                        </div>

                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('npm')"
                                :style="'width:' + stepProgress('npm') + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Build --}}
            <div
                class="rounded-lg border border-gray-100 p-4"
                :class="pipelineStepClass('build')"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="pipelineIconClass('build')"
                    >
                        <template x-if="stepStatus('build') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                        </template>

                        <template x-if="stepStatus('build') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M7.757 16.243l-2.121 2.121m12.728 0-2.121-2.121M7.757 7.757 5.636 5.636" /></svg>
                        </template>

                        <template x-if="stepStatus('build') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </template>

                        <template x-if="stepStatus('build') === 'pending'">
                            <span class="text-sm font-semibold">4</span>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    Frontend Build
                                </p>

                                <p class="text-xs text-gray-500">
                                    Build production frontend
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('build')"
                                x-text="formatStatus(stepStatus('build'))"
                            ></span>
                        </div>

                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('build')"
                                :style="'width:' + stepProgress('build') + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Migrations --}}
            <div
                class="rounded-lg border border-gray-100 p-4"
                :class="pipelineStepClass('migrations')"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="pipelineIconClass('migrations')"
                    >
                        <template x-if="stepStatus('migrations') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                        </template>

                        <template x-if="stepStatus('migrations') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M7.757 16.243l-2.121 2.121m12.728 0-2.121-2.121M7.757 7.757 5.636 5.636" /></svg>
                        </template>

                        <template x-if="stepStatus('migrations') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </template>

                        <template x-if="stepStatus('migrations') === 'pending'">
                            <span class="text-sm font-semibold">5</span>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    Database Migrations
                                </p>

                                <p class="text-xs text-gray-500">
                                    Run pending migrations
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('migrations')"
                                x-text="formatStatus(stepStatus('migrations'))"
                            ></span>
                        </div>

                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('migrations')"
                                :style="'width:' + stepProgress('migrations') + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Optimize --}}
            <div
                class="rounded-lg border border-gray-100 p-4"
                :class="pipelineStepClass('optimize')"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="pipelineIconClass('optimize')"
                    >
                        <template x-if="stepStatus('optimize') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                        </template>

                        <template x-if="stepStatus('optimize') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M7.757 16.243l-2.121 2.121m12.728 0-2.121-2.121M7.757 7.757 5.636 5.636" /></svg>
                        </template>

                        <template x-if="stepStatus('optimize') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </template>

                        <template x-if="stepStatus('optimize') === 'pending'">
                            <span class="text-sm font-semibold">6</span>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    Optimize
                                </p>

                                <p class="text-xs text-gray-500">
                                    Optimize Laravel application
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('optimize')"
                                x-text="formatStatus(stepStatus('optimize'))"
                            ></span>
                        </div>

                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('optimize')"
                                :style="'width:' + stepProgress('optimize') + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Queue Restart --}}
            <div
                class="rounded-lg border border-gray-100 p-4"
                :class="pipelineStepClass('queue_restart')"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="pipelineIconClass('queue_restart')"
                    >
                        <template x-if="stepStatus('queue_restart') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                        </template>

                        <template x-if="stepStatus('queue_restart') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M7.757 16.243l-2.121 2.121m12.728 0-2.121-2.121M7.757 7.757 5.636 5.636" /></svg>
                        </template>

                        <template x-if="stepStatus('queue_restart') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </template>

                        <template x-if="stepStatus('queue_restart') === 'pending'">
                            <span class="text-sm font-semibold">7</span>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    Queue Restart
                                </p>

                                <p class="text-xs text-gray-500">
                                    Restart queue workers
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('queue_restart')"
                                x-text="formatStatus(stepStatus('queue_restart'))"
                            ></span>
                        </div>

                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('queue_restart')"
                                :style="'width:' + stepProgress('queue_restart') + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Health Check --}}
            <div
                class="rounded-lg border border-gray-100 p-4"
                :class="pipelineStepClass('health_check')"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="pipelineIconClass('health_check')"
                    >
                        <template x-if="stepStatus('health_check') === 'completed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                        </template>

                        <template x-if="stepStatus('health_check') === 'running'">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M7.757 16.243l-2.121 2.121m12.728 0-2.121-2.121M7.757 7.757 5.636 5.636" /></svg>
                        </template>

                        <template x-if="stepStatus('health_check') === 'failed'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </template>

                        <template x-if="stepStatus('health_check') === 'pending'">
                            <span class="text-sm font-semibold">8</span>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    Health Check
                                </p>

                                <p class="text-xs text-gray-500">
                                    Verify application health
                                </p>
                            </div>

                            <span
                                class="text-xs font-medium"
                                :class="stepStatusTextClass('health_check')"
                                x-text="formatStatus(stepStatus('health_check'))"
                            ></span>
                        </div>

                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="pipelineBarClass('health_check')"
                                :style="'width:' + stepProgress('health_check') + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Deployment Output --}}
    <div
        x-show="activeDeployment?.output"
        class="border-t border-gray-100"
    >
        <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">
                    Deployment Output
                </h3>

                <span
                    class="text-xs text-gray-500"
                    x-text="outputLines + ' lines'"
                ></span>
            </div>

            <div class="max-h-80 overflow-auto rounded-lg bg-gray-950 p-4 font-mono text-xs leading-5 text-gray-300">
                <pre
                    class="whitespace-pre-wrap"
                    x-text="activeDeployment?.output ?? ''"
                ></pre>
            </div>
        </div>
    </div>

    {{-- Error --}}
    <div
        x-show="activeDeployment?.error"
        class="border-t border-red-100 bg-red-50"
    >
        <div class="p-5">
            <h3 class="text-sm font-semibold text-red-800">
                Deployment Error
            </h3>

            <pre
                class="mt-3 max-h-60 overflow-auto whitespace-pre-wrap rounded-lg bg-red-100 p-4 font-mono text-xs leading-5 text-red-800"
                x-text="activeDeployment?.error ?? ''"
            ></pre>
        </div>
    </div>
</div>
@endif