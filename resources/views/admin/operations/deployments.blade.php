@extends('admin.layouts.main')

@section('title', 'Deployments')

@section('content')

<div
    x-data="operationsDeployments()"
    x-init="init()"
    class="space-y-6"
>

    {{-- ================================================================
        HEADER
    ================================================================= --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <div class="flex items-center gap-3">

                <h1 class="text-xl font-semibold text-gray-800">
                    Deployments
                </h1>

                <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="deploymentSystemClass()"
                    x-text="deploymentSystemLabel()"
                ></span>

            </div>

            <p class="mt-1 text-sm text-gray-500">
                Deploy and monitor application releases from the Operations Center.
            </p>

        </div>

        <div class="flex items-center gap-2">

            <button
                type="button"
                @click="refreshAll()"
                :disabled="loading"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
            >
                <i
                    class="ik ik-refresh-cw"
                    :class="{ 'animate-spin': loading }"
                ></i>

                Refresh
            </button>

            <button
                type="button"
                @click="openDeployModal()"
                :disabled="!canDeploy()"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <i class="ik ik-upload-cloud"></i>
                Deploy Now
            </button>

        </div>

    </div>


    {{-- ================================================================
        ERROR
    ================================================================= --}}
    <template x-if="error">

        <div class="rounded-xl border border-red-200 bg-red-50 p-4">

            <div class="flex items-start gap-3">

                <i class="ik ik-alert-circle mt-0.5 text-red-600"></i>

                <span
                    class="text-sm text-red-700"
                    x-text="error"
                ></span>

            </div>

        </div>

    </template>


    {{-- ================================================================
        SUCCESS
    ================================================================= --}}
    <template x-if="message">

        <div class="rounded-xl border border-green-200 bg-green-50 p-4">

            <div class="flex items-start gap-3">

                <i class="ik ik-check-circle mt-0.5 text-green-600"></i>

                <span
                    class="text-sm text-green-700"
                    x-text="message"
                ></span>

            </div>

        </div>

    </template>


    {{-- ================================================================
        ENVIRONMENT WARNING
    ================================================================= --}}
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">

        <div class="flex items-start gap-3">

            <i class="ik ik-info mt-0.5 text-blue-600"></i>

            <div>

                <p class="text-sm font-medium text-blue-800">
                    Deployment Environment
                </p>

                <p class="mt-1 text-xs text-blue-700">

                    Deployments are currently configured for

                    <strong x-text="overview.environment || 'staging'"></strong>

                    using branch

                    <strong x-text="overview.branch || 'main'"></strong>.

                </p>

            </div>

        </div>

    </div>


    {{-- ================================================================
        OVERVIEW STATS
    ================================================================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- Total --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Total Deployments
            </p>

            <p
                class="mt-2 text-2xl font-semibold text-gray-800"
                x-text="overview.statistics?.total ?? '—'"
            ></p>

        </div>


        {{-- Successful --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Successful
            </p>

            <p
                class="mt-2 text-2xl font-semibold text-green-600"
                x-text="overview.statistics?.successful ?? '—'"
            ></p>

        </div>


        {{-- Failed --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Failed
            </p>

            <p
                class="mt-2 text-2xl font-semibold text-red-600"
                x-text="overview.statistics?.failed ?? '—'"
            ></p>

        </div>


        {{-- Running --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                Active
            </p>

            <p
                class="mt-2 text-2xl font-semibold text-yellow-600"
                x-text="overview.statistics?.running ?? '—'"
            ></p>

        </div>

    </div>


    {{-- ================================================================
        ACTIVE DEPLOYMENT
    ================================================================= --}}
    <template x-if="activeDeployment">

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

            <div class="border-b border-gray-100 p-6">

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <div class="flex items-center gap-3">

                            <h2 class="font-semibold text-gray-800">
                                Active Deployment
                            </h2>

                            <span
                                class="rounded-full px-3 py-1 text-xs font-medium"
                                :class="statusClass(activeDeployment.status)"
                                x-text="formatStatus(activeDeployment.status)"
                            ></span>

                        </div>

                        <p class="mt-1 text-xs text-gray-500">

                            Deployment #

                            <span
                                class="font-medium text-gray-700"
                                x-text="activeDeployment.id"
                            ></span>

                            ·

                            <span x-text="activeDeployment.environment"></span>

                            ·

                            <span x-text="activeDeployment.branch"></span>

                        </p>

                    </div>

                    <button
                        type="button"
                        @click="loadDeployment(activeDeployment.id)"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <i class="ik ik-refresh-cw"></i>
                        Refresh
                    </button>

                </div>

            </div>


            {{-- Deployment Metadata --}}
            <div class="grid grid-cols-1 gap-4 border-b border-gray-100 p-6 sm:grid-cols-2 lg:grid-cols-4">

                <div>

                    <p class="text-xs text-gray-500">
                        Environment
                    </p>

                    <p
                        class="mt-1 text-sm font-medium text-gray-800"
                        x-text="activeDeployment.environment || '—'"
                    ></p>

                </div>

                <div>

                    <p class="text-xs text-gray-500">
                        Branch
                    </p>

                    <p
                        class="mt-1 text-sm font-medium text-gray-800"
                        x-text="activeDeployment.branch || '—'"
                    ></p>

                </div>

                <div>

                    <p class="text-xs text-gray-500">
                        Commit
                    </p>

                    <p
                        class="mt-1 break-all font-mono text-xs text-gray-800"
                        x-text="shortCommit(activeDeployment.commit_hash)"
                    ></p>

                </div>

                <div>

                    <p class="text-xs text-gray-500">
                        Duration
                    </p>

                    <p
                        class="mt-1 text-sm font-medium text-gray-800"
                        x-text="duration(activeDeployment)"
                    ></p>

                </div>

            </div>


            {{-- Pipeline --}}
            <div class="p-6">

                <h3 class="mb-4 text-sm font-semibold text-gray-800">
                    Deployment Pipeline
                </h3>

                <div class="space-y-3">

                    <template
                        x-for="(step, index) in pipeline"
                        :key="step.key"
                    >

                        <div
                            class="flex items-center gap-4 rounded-lg border p-4"
                            :class="pipelineStepClass(step)"
                        >

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                :class="pipelineIconClass(step)"
                            >

                                <template x-if="pipelineStepStatus(step) === 'completed'">

                                    <i class="ik ik-check text-sm"></i>

                                </template>

                                <template x-if="pipelineStepStatus(step) === 'running'">

                                    <i class="ik ik-loader text-sm animate-spin"></i>

                                </template>

                                <template x-if="pipelineStepStatus(step) === 'failed'">

                                    <i class="ik ik-x text-sm"></i>

                                </template>

                                <template x-if="pipelineStepStatus(step) === 'pending'">

                                    <span
                                        class="text-xs font-semibold"
                                        x-text="index + 1"
                                    ></span>

                                </template>

                            </div>


                            <div class="min-w-0 flex-1">

                                <div class="flex items-center justify-between gap-4">

                                    <p
                                        class="text-sm font-medium text-gray-800"
                                        x-text="step.name"
                                    ></p>

                                    <span
                                        class="shrink-0 text-xs font-medium"
                                        :class="pipelineStatusTextClass(step)"
                                        x-text="formatStatus(pipelineStepStatus(step))"
                                    ></span>

                                </div>

                                <p
                                    class="mt-1 text-xs text-gray-500"
                                    x-text="step.description"
                                ></p>

                            </div>

                        </div>

                    </template>

                </div>

            </div>


            {{-- Output --}}
            <div class="border-t border-gray-100 p-6">

                <div class="mb-3 flex items-center justify-between">

                    <div>

                        <h3 class="text-sm font-semibold text-gray-800">
                            Deployment Output
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Live output from the deployment process.
                        </p>

                    </div>

                </div>

                <pre
                    class="max-h-96 overflow-auto rounded-lg bg-gray-900 p-4 font-mono text-xs leading-5 text-gray-200"
                    x-text="activeDeployment.output || 'Waiting for deployment output...'"
                ></pre>

            </div>


            {{-- Error --}}
            <template x-if="activeDeployment.error">

                <div class="border-t border-gray-100 p-6">

                    <div class="rounded-lg border border-red-200 bg-red-50 p-4">

                        <div class="flex items-start gap-3">

                            <i class="ik ik-alert-circle mt-0.5 text-red-600"></i>

                            <div>

                                <p class="text-sm font-medium text-red-800">
                                    Deployment Error
                                </p>

                                <pre
                                    class="mt-2 whitespace-pre-wrap text-xs leading-5 text-red-700"
                                    x-text="activeDeployment.error"
                                ></pre>

                            </div>

                        </div>

                    </div>

                </div>

            </template>

        </div>

    </template>


    {{-- ================================================================
        DEPLOYMENT PIPELINE CONFIG
    ================================================================= --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="border-b border-gray-100 p-6">

            <h2 class="font-semibold text-gray-800">
                Pipeline
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Deployment stages configured for the Operations Center.
            </p>

        </div>

        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 lg:grid-cols-3">

            <template
                x-for="step in pipeline"
                :key="'config-' + step.key"
            >

                <div class="rounded-xl border border-gray-100 p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div class="flex items-start gap-3">

                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-600">

                                <i
                                    :class="step.icon"
                                    class="ik"
                                ></i>

                            </div>

                            <div>

                                <h3
                                    class="text-sm font-medium text-gray-800"
                                    x-text="step.name"
                                ></h3>

                                <p
                                    class="mt-1 text-xs text-gray-500"
                                    x-text="step.description"
                                ></p>

                            </div>

                        </div>

                        <span
                            class="rounded-full px-2.5 py-1 text-[10px] font-medium"
                            :class="step.enabled
                                ? 'bg-green-50 text-green-700'
                                : 'bg-gray-100 text-gray-500'"
                            x-text="step.enabled ? 'Enabled' : 'Disabled'"
                        ></span>

                    </div>

                </div>

            </template>

        </div>

    </div>


    {{-- ================================================================
        DEPLOYMENT HISTORY
    ================================================================= --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="flex flex-col gap-4 border-b border-gray-100 p-6 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="font-semibold text-gray-800">
                    Deployment History
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Recent deployments and their results.
                </p>

            </div>

            <button
                type="button"
                @click="loadHistory()"
                :disabled="historyLoading"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
            >
                <i
                    class="ik ik-refresh-cw"
                    :class="{ 'animate-spin': historyLoading }"
                ></i>

                Refresh
            </button>

        </div>


        {{-- Desktop Table --}}
        <div class="hidden overflow-x-auto md:block">

            <table class="w-full">

                <thead class="border-b border-gray-100 bg-gray-50">

                    <tr>

                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Deployment
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Branch
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Commit
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Status
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Duration
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">

                    <template x-if="history.length === 0 && !historyLoading">

                        <tr>

                            <td
                                colspan="6"
                                class="px-6 py-10 text-center text-sm text-gray-500"
                            >
                                No deployments yet.
                            </td>

                        </tr>

                    </template>


                    <template
                        x-for="deployment in history"
                        :key="deployment.id"
                    >

                        <tr class="hover:bg-gray-50">

                            <td class="px-6 py-4">

                                <div>

                                    <p class="text-sm font-medium text-gray-800">

                                        #

                                        <span x-text="deployment.id"></span>

                                    </p>

                                    <p
                                        class="mt-1 text-xs text-gray-500"
                                        x-text="formatDate(deployment.created_at)"
                                    ></p>

                                </div>

                            </td>


                            <td class="px-6 py-4">

                                <span
                                    class="rounded-md bg-gray-100 px-2 py-1 font-mono text-xs text-gray-700"
                                    x-text="deployment.branch || '—'"
                                ></span>

                            </td>


                            <td class="px-6 py-4">

                                <div>

                                    <p
                                        class="font-mono text-xs text-gray-700"
                                        x-text="shortCommit(deployment.commit_hash)"
                                    ></p>

                                    <p
                                        class="mt-1 max-w-xs truncate text-xs text-gray-500"
                                        x-text="deployment.commit_message || '—'"
                                    ></p>

                                </div>

                            </td>


                            <td class="px-6 py-4">

                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="statusClass(deployment.status)"
                                    x-text="formatStatus(deployment.status)"
                                ></span>

                            </td>


                            <td class="px-6 py-4">

                                <span
                                    class="text-xs text-gray-600"
                                    x-text="duration(deployment)"
                                ></span>

                            </td>


                            <td class="px-6 py-4 text-right">

                                <button
                                    type="button"
                                    @click="viewDeployment(deployment.id)"
                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    <i class="ik ik-eye"></i>
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

            <template x-if="history.length === 0 && !historyLoading">

                <div class="p-8 text-center text-sm text-gray-500">
                    No deployments yet.
                </div>

            </template>


            <template
                x-for="deployment in history"
                :key="'mobile-' + deployment.id"
            >

                <div class="p-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <p class="text-sm font-medium text-gray-800">

                                Deployment #

                                <span x-text="deployment.id"></span>

                            </p>

                            <p
                                class="mt-1 text-xs text-gray-500"
                                x-text="formatDate(deployment.created_at)"
                            ></p>

                        </div>

                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="statusClass(deployment.status)"
                            x-text="formatStatus(deployment.status)"
                        ></span>

                    </div>


                    <div class="mt-4 grid grid-cols-2 gap-4">

                        <div>

                            <p class="text-[11px] text-gray-500">
                                Branch
                            </p>

                            <p
                                class="mt-1 font-mono text-xs text-gray-700"
                                x-text="deployment.branch || '—'"
                            ></p>

                        </div>

                        <div>

                            <p class="text-[11px] text-gray-500">
                                Duration
                            </p>

                            <p
                                class="mt-1 text-xs text-gray-700"
                                x-text="duration(deployment)"
                            ></p>

                        </div>

                    </div>


                    <button
                        type="button"
                        @click="viewDeployment(deployment.id)"
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <i class="ik ik-eye"></i>
                        View Deployment
                    </button>

                </div>

            </template>

        </div>

    </div>


    {{-- ================================================================
        DEPLOY MODAL
    ================================================================= --}}
    <template x-if="showDeployModal">

        <div
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
            @keydown.escape.window="closeDeployModal()"
        >

            <div
                class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl"
                @click.stop
            >

                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 p-6">

                    <div>

                        <h2 class="text-lg font-semibold text-gray-800">
                            Deploy Application
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Start a new deployment using the configured pipeline.
                        </p>

                    </div>

                    <button
                        type="button"
                        @click="closeDeployModal()"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    >
                        <i class="ik ik-x"></i>
                    </button>

                </div>


                {{-- Modal Body --}}
                <div class="space-y-6 p-6">

                    {{-- Environment --}}
                    <div>

                        <label class="mb-2 block text-xs font-medium text-gray-700">
                            Environment
                        </label>

                        <select
                            x-model="deployForm.environment"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        >

                            <option value="staging">
                                Staging
                            </option>

                            <option value="production">
                                Production
                            </option>

                        </select>

                    </div>


                    {{-- Branch --}}
                    <div>

                        <label class="mb-2 block text-xs font-medium text-gray-700">
                            Branch
                        </label>

                        <input
                            type="text"
                            x-model="deployForm.branch"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 font-mono text-sm text-gray-700 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                            placeholder="main"
                        >

                    </div>


                    {{-- Preflight --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="text-sm font-medium text-gray-800">
                                    Preflight Checks
                                </h3>

                                <p class="mt-1 text-xs text-gray-500">
                                    Verify the server is ready before deployment.
                                </p>

                            </div>

                            <button
                                type="button"
                                @click="runPreflight()"
                                :disabled="preflightLoading"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                            >
                                <i
                                    class="ik ik-refresh-cw"
                                    :class="{ 'animate-spin': preflightLoading }"
                                ></i>

                                Check
                            </button>

                        </div>


                        <template x-if="preflight">

                            <div class="mt-4 space-y-2">

                                <template
                                    x-for="check in normalizedPreflightChecks()"
                                    :key="check.name"
                                >

                                    <div class="flex items-center justify-between rounded-lg bg-white px-3 py-2.5">

                                        <div class="flex items-center gap-2">

                                            <i
                                                class="ik"
                                                :class="check.status === 'healthy'
                                                    ? 'ik-check-circle text-green-600'
                                                    : 'ik-alert-circle text-red-600'"
                                            ></i>

                                            <span
                                                class="text-xs text-gray-700"
                                                x-text="preflightCheckName(check.name)"
                                            ></span>

                                        </div>

                                        <span
                                            class="text-[11px] font-medium"
                                            :class="check.status === 'healthy'
                                                ? 'text-green-600'
                                                : 'text-red-600'"
                                            x-text="formatStatus(check.status)"
                                        ></span>

                                    </div>

                                </template>

                            </div>

                        </template>


                        <template x-if="preflight && !preflight.ready">

                            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3">

                                <p class="text-xs font-medium text-red-800">
                                    Deployment is not ready.
                                </p>

                                <p class="mt-1 text-xs text-red-700">
                                    Resolve the failed preflight checks before deploying.
                                </p>

                            </div>

                        </template>


                        <template x-if="preflight && preflight.ready">

                            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3">

                                <div class="flex items-center gap-2">

                                    <i class="ik ik-check-circle text-green-600"></i>

                                    <p class="text-xs font-medium text-green-800">
                                        All preflight checks passed.
                                    </p>

                                </div>

                            </div>

                        </template>

                    </div>


                    {{-- Warning --}}
                    <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">

                        <div class="flex items-start gap-3">

                            <i class="ik ik-alert-triangle mt-0.5 text-yellow-600"></i>

                            <div>

                                <p class="text-sm font-medium text-yellow-800">
                                    Deployment will modify the application
                                </p>

                                <p class="mt-1 text-xs leading-5 text-yellow-700">
                                    The deployment will fetch the selected branch,
                                    install dependencies, build the application,
                                    run migrations, optimize Laravel, restart
                                    queues, and perform a health check.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Modal Footer --}}
                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 p-6 sm:flex-row sm:justify-end">

                    <button
                        type="button"
                        @click="closeDeployModal()"
                        class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="createDeployment()"
                        :disabled="deploying || !deploymentReady()"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >

                        <template x-if="deploying">

                            <i class="ik ik-loader animate-spin"></i>

                        </template>

                        <template x-if="!deploying">

                            <i class="ik ik-upload-cloud"></i>

                        </template>

                        <span
                            x-text="deploying
                                ? 'Starting...'
                                : 'Start Deployment'"
                        ></span>

                    </button>

                </div>

            </div>

        </div>

    </template>


    {{-- ================================================================
        DETAILS MODAL
    ================================================================= --}}
    <template x-if="selectedDeployment && !showDeployModal">

        <div
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
            @keydown.escape.window="selectedDeployment = null"
        >

            <div
                class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
                @click.stop
            >

                <div class="flex items-center justify-between border-b border-gray-100 p-6">

                    <div>

                        <div class="flex items-center gap-3">

                            <h2 class="text-lg font-semibold text-gray-800">

                                Deployment #

                                <span x-text="selectedDeployment.id"></span>

                            </h2>

                            <span
                                class="rounded-full px-3 py-1 text-xs font-medium"
                                :class="statusClass(selectedDeployment.status)"
                                x-text="formatStatus(selectedDeployment.status)"
                            ></span>

                        </div>

                        <p class="mt-1 text-xs text-gray-500">

                            <span x-text="selectedDeployment.environment"></span>

                            ·

                            <span x-text="selectedDeployment.branch"></span>

                        </p>

                    </div>

                    <button
                        type="button"
                        @click="selectedDeployment = null"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    >
                        <i class="ik ik-x"></i>
                    </button>

                </div>


                <div class="space-y-6 p-6">

                    {{-- Details --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

                        <div class="rounded-lg bg-gray-50 p-4">

                            <p class="text-xs text-gray-500">
                                Branch
                            </p>

                            <p
                                class="mt-1 font-mono text-sm text-gray-800"
                                x-text="selectedDeployment.branch || '—'"
                            ></p>

                        </div>

                        <div class="rounded-lg bg-gray-50 p-4">

                            <p class="text-xs text-gray-500">
                                Commit
                            </p>

                            <p
                                class="mt-1 break-all font-mono text-xs text-gray-800"
                                x-text="selectedDeployment.commit_hash || '—'"
                            ></p>

                        </div>

                        <div class="rounded-lg bg-gray-50 p-4">

                            <p class="text-xs text-gray-500">
                                Duration
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-800"
                                x-text="duration(selectedDeployment)"
                            ></p>

                        </div>

                    </div>


                    {{-- Commit Message --}}
                    <div>

                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">
                            Commit Message
                        </p>

                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">

                            <p
                                class="whitespace-pre-wrap text-sm text-gray-700"
                                x-text="selectedDeployment.commit_message || 'No commit message available.'"
                            ></p>

                        </div>

                    </div>


                    {{-- Output --}}
                    <div>

                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">
                            Deployment Output
                        </p>

                        <pre
                            class="max-h-96 overflow-auto rounded-lg bg-gray-900 p-4 font-mono text-xs leading-5 text-gray-200"
                            x-text="selectedDeployment.output || 'No output available.'"
                        ></pre>

                    </div>


                    {{-- Error --}}
                    <template x-if="selectedDeployment.error">

                        <div>

                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-red-600">
                                Error
                            </p>

                            <pre
                                class="max-h-64 overflow-auto whitespace-pre-wrap rounded-lg border border-red-200 bg-red-50 p-4 font-mono text-xs leading-5 text-red-700"
                                x-text="selectedDeployment.error"
                            ></pre>

                        </div>

                    </template>

                </div>


                <div class="border-t border-gray-100 p-6 text-right">

                    <button
                        type="button"
                        @click="selectedDeployment = null"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Close
                    </button>

                </div>

            </div>

        </div>

    </template>

</div>


<script>

function operationsDeployments() {

    return {

        /*
        |--------------------------------------------------------------------------
        | State
        |--------------------------------------------------------------------------
        */

        loading: false,

        historyLoading: false,

        preflightLoading: false,

        deploying: false,

        error: null,

        message: null,

        showDeployModal: false,

        selectedDeployment: null,

        pollingTimer: null,

        pollingInProgress: false,


        /*
        |--------------------------------------------------------------------------
        | Overview
        |--------------------------------------------------------------------------
        */

        overview: {

            status: null,

            enabled: false,

            environment: '—',

            branch: '—',

            path: '—',

            timeout: 0,

            remote: '—',

            pipeline: {},

            latest_deployment: null,

            running_deployment: null,

            statistics: {

                total: 0,

                successful: 0,

                failed: 0,

                running: 0,

            },

        },


        history: [],

        activeDeployment: null,

        preflight: null,


        /*
        |--------------------------------------------------------------------------
        | Deploy Form
        |--------------------------------------------------------------------------
        */

        deployForm: {

            environment: 'staging',

            branch: 'main',

        },


        /*
        |--------------------------------------------------------------------------
        | Pipeline
        |--------------------------------------------------------------------------
        */

        pipeline: [

            {
                key: 'git',

                name: 'Git',

                description: 'Fetch and checkout the deployment branch.',

                icon: 'ik-git-branch',

                enabled: true,
            },

            {
                key: 'composer',

                name: 'Composer',

                description: 'Install PHP dependencies.',

                icon: 'ik-package',

                enabled: true,
            },

            {
                key: 'npm',

                name: 'NPM',

                description: 'Install frontend dependencies.',

                icon: 'ik-box',

                enabled: true,
            },

            {
                key: 'build',

                name: 'Build',

                description: 'Build the frontend application.',

                icon: 'ik-layers',

                enabled: true,
            },

            {
                key: 'migrations',

                name: 'Database Migrations',

                description: 'Run Laravel database migrations.',

                icon: 'ik-database',

                enabled: true,
            },

            {
                key: 'optimize',

                name: 'Laravel Optimize',

                description: 'Optimize Laravel configuration and services.',

                icon: 'ik-zap',

                enabled: true,
            },

            {
                key: 'queue_restart',

                name: 'Queue Restart',

                description: 'Gracefully restart Laravel queue workers.',

                icon: 'ik-refresh-cw',

                enabled: true,
            },

            {
                key: 'health_check',

                name: 'Health Check',

                description: 'Verify the application database is healthy.',

                icon: 'ik-heart',

                enabled: true,
            },

        ],


        /*
        |--------------------------------------------------------------------------
        | Init
        |--------------------------------------------------------------------------
        */

        async init() {

            await this.refreshAll();

            /*
             * Only poll if a deployment is actually active.
             */
            if (this.hasActiveDeployment()) {

                this.startPolling();

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Load Everything
        |--------------------------------------------------------------------------
        */

        async refreshAll() {

            this.loading = true;

            this.error = null;

            try {

                await Promise.all([

                    this.loadOverview(),

                    this.loadHistory(),

                    this.loadPreflight(),

                ]);

            } catch (error) {

                this.error =
                    error?.message ||
                    'Unable to refresh deployment data.';

            } finally {

                this.loading = false;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Overview
        |--------------------------------------------------------------------------
        */

        async loadOverview() {

            const response = await fetch(

                '/api/admin/operations/deployments',

                {

                    credentials: 'same-origin',

                    headers: {

                        'Accept': 'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest',

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
                    'Unable to load deployment overview.'

                );

            }


            const data =
                result.data || {};


            const statistics =
                data.statistics || {};


            /*
             * The backend doesn't return a "status".
             *
             * Derive it from:
             *
             * enabled
             * running_deployment
             */

            let status = 'disabled';


            if (data.enabled === true) {

                status =
                    data.running_deployment
                        ? 'running'
                        : 'enabled';

            }


            this.overview = {

                ...this.overview,

                ...data,

                status,

                statistics: {

                    total:
                        Number(statistics.total ?? 0),

                    successful:
                        Number(statistics.successful ?? 0),

                    failed:
                        Number(statistics.failed ?? 0),

                    running:
                        Number(statistics.running ?? 0),

                },

            };


            /*
             * Synchronize active deployment.
             */

            if (data.running_deployment) {

                this.activeDeployment =
                    data.running_deployment;

            } else if (
                !this.activeDeployment ||
                ![
                    'pending',
                    'running',
                ].includes(
                    this.activeDeployment.status
                )
            ) {

                this.activeDeployment = null;

            }


            /*
             * Synchronize pipeline configuration
             * returned by the backend.
             */

            this.syncPipelineConfiguration();


            return this.overview;

        },


        /*
        |--------------------------------------------------------------------------
        | Sync Pipeline Configuration
        |--------------------------------------------------------------------------
        */

        syncPipelineConfiguration() {

            const configured =
                this.overview.pipeline || {};


            this.pipeline =
                this.pipeline.map(step => {

                    if (
                        configured[step.key] !== undefined
                    ) {

                        return {

                            ...step,

                            enabled:
                                configured[step.key]?.enabled
                                    ?? step.enabled,

                        };

                    }

                    return step;

                });

        },


        /*
        |--------------------------------------------------------------------------
        | History
        |--------------------------------------------------------------------------
        */

        async loadHistory() {

            this.historyLoading = true;

            try {

                const response = await fetch(

                    '/api/admin/operations/deployments/history?limit=20',

                    {

                        credentials: 'same-origin',

                        headers: {

                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

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
                        'Unable to load deployment history.'

                    );

                }


                this.history =
                    this.normalizeHistory(
                        result.data
                    );


                this.detectActiveDeployment();


            } catch (error) {

                this.error =
                    error?.message ||
                    'Unable to load deployment history.';

            } finally {

                this.historyLoading = false;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Preflight
        |--------------------------------------------------------------------------
        */

        async loadPreflight() {

            this.preflightLoading = true;

            try {

                const response = await fetch(

                    '/api/admin/operations/deployments/preflight',

                    {

                        credentials: 'same-origin',

                        headers: {

                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

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
                        'Unable to run deployment preflight.'

                    );

                }


                this.preflight =
                    result.data || null;


                return this.preflight;


            } catch (error) {

                this.error =
                    error?.message ||
                    'Unable to run deployment preflight.';

                return null;

            } finally {

                this.preflightLoading = false;

            }

        },


        async runPreflight() {

            await this.loadPreflight();

        },


        /*
        |--------------------------------------------------------------------------
        | Open Deploy Modal
        |--------------------------------------------------------------------------
        */

        async openDeployModal() {

            if (!this.canDeploy()) {

                return;

            }


            this.message = null;

            this.error = null;


            this.deployForm.environment =

                this.overview.environment ||
                'staging';


            this.deployForm.branch =

                this.overview.branch ||
                'main';


            this.showDeployModal = true;


            await this.runPreflight();

        },


        closeDeployModal() {

            if (this.deploying) {

                return;

            }


            this.showDeployModal = false;

        },


        /*
        |--------------------------------------------------------------------------
        | Create Deployment
        |--------------------------------------------------------------------------
        */

        async createDeployment() {

            if (!this.deploymentReady()) {

                return;

            }


            if (
                !this.canDeploy()
            ) {

                this.error =
                    'A deployment is already running or the deployment system is disabled.';

                return;

            }


            if (!confirm(

                `Deploy branch "${this.deployForm.branch}" to "${this.deployForm.environment}"?`

            )) {

                return;

            }


            this.deploying = true;

            this.error = null;

            this.message = null;


            try {

                const response = await fetch(

                    '/api/admin/operations/deployments/create',

                    {

                        method: 'POST',

                        credentials: 'same-origin',

                        headers: {

                            'Accept':
                                'application/json',

                            'Content-Type':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                this.csrfToken(),

                        },

                        body: JSON.stringify({

                            environment:
                                this.deployForm.environment,

                            branch:
                                this.deployForm.branch,

                        }),

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
                        'Unable to create deployment.'

                    );

                }


                this.message =
                    result.message ||
                    'Deployment has been queued.';


                this.showDeployModal = false;


                /*
                 * Immediately refresh state.
                 */

                await this.loadOverview();

                await this.loadHistory();


                /*
                 * Load the newly-created deployment.
                 */

                if (result.data?.id) {

                    await this.loadDeployment(
                        result.data.id
                    );

                }


                /*
                 * Start live polling.
                 */

                this.startPolling();


            } catch (error) {

                this.error =
                    error?.message ||
                    'Unable to create deployment.';

            } finally {

                this.deploying = false;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Load Deployment
        |--------------------------------------------------------------------------
        */

        async loadDeployment(id) {

            if (!id) {

                return null;

            }


            try {

                const response = await fetch(

                    `/api/admin/operations/deployments/${id}`,

                    {

                        credentials: 'same-origin',

                        headers: {

                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

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
                        'Unable to load deployment.'

                    );

                }


                this.activeDeployment =
                    result.data;


                /*
                 * Continue polling only while pending/running.
                 */

                if (
                    this.activeDeployment &&
                    [
                        'pending',
                        'running',
                    ].includes(
                        this.activeDeployment.status
                    )
                ) {

                    this.startPolling();

                } else {

                    /*
                     * Deployment finished.
                     */

                    this.stopPolling();

                    await this.loadOverview();

                    await this.loadHistory();

                }


                return this.activeDeployment;


            } catch (error) {

                this.error =
                    error?.message ||
                    'Unable to load deployment.';

                return null;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | View Deployment
        |--------------------------------------------------------------------------
        */

        async viewDeployment(id) {

            const deployment =
                await this.loadDeployment(id);


            if (deployment) {

                this.selectedDeployment =
                    deployment;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Detect Active Deployment
        |--------------------------------------------------------------------------
        */

        detectActiveDeployment() {

            /*
             * Prefer backend's authoritative
             * running deployment.
             */

            if (
                this.overview.running_deployment
            ) {

                this.activeDeployment =
                    this.overview.running_deployment;

                return;

            }


            /*
             * Otherwise inspect history.
             */

            const active =
                this.history.find(

                    deployment =>

                        [
                            'pending',
                            'running',
                        ].includes(
                            deployment.status
                        )

                );


            if (active) {

                this.activeDeployment =
                    active;

                this.loadDeployment(
                    active.id
                );

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Active Deployment Check
        |--------------------------------------------------------------------------
        */

        hasActiveDeployment() {

            if (
                this.overview.running_deployment
            ) {

                return true;

            }


            if (
                this.activeDeployment &&
                [
                    'pending',
                    'running',
                ].includes(
                    this.activeDeployment.status
                )
            ) {

                return true;

            }


            return this.history.some(

                deployment =>

                    [
                        'pending',
                        'running',
                    ].includes(
                        deployment.status
                    )

            );

        },


        /*
        |--------------------------------------------------------------------------
        | Polling
        |--------------------------------------------------------------------------
        */

        startPolling() {

            /*
             * Don't create duplicate timers.
             */

            if (this.pollingTimer) {

                return;

            }


            this.pollingTimer = setInterval(

                async () => {

                    /*
                     * Prevent overlapping poll requests.
                     */

                    if (this.pollingInProgress) {

                        return;

                    }


                    if (!this.hasActiveDeployment()) {

                        this.stopPolling();

                        return;

                    }


                    this.pollingInProgress = true;


                    try {

                        await this.loadOverview();


                        /*
                         * Overview contains the authoritative
                         * running deployment.
                         */

                        const active =
                            this.overview.running_deployment;


                        if (active?.id) {

                            await this.loadDeployment(
                                active.id
                            );

                        } else {

                            /*
                             * Deployment may have just completed.
                             */

                            await this.loadHistory();


                            /*
                             * If no active deployment remains,
                             * stop polling.
                             */

                            if (
                                !this.hasActiveDeployment()
                            ) {

                                this.stopPolling();

                            }

                        }

                    } catch (error) {

                        console.error(
                            'Deployment polling error:',
                            error
                        );

                    } finally {

                        this.pollingInProgress = false;

                    }

                },

                3000

            );

        },


        stopPolling() {

            if (this.pollingTimer) {

                clearInterval(
                    this.pollingTimer
                );

                this.pollingTimer = null;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Deployment Ready
        |--------------------------------------------------------------------------
        */

        deploymentReady() {

            if (!this.preflight) {

                return false;

            }


            if (
                this.preflight.ready !== true
            ) {

                return false;

            }


            if (
                !this.deployForm.branch ||
                !this.deployForm.branch.trim()
            ) {

                return false;

            }


            return true;

        },


        /*
        |--------------------------------------------------------------------------
        | Can Deploy
        |--------------------------------------------------------------------------
        */

        canDeploy() {

            /*
             * Deployment system must be enabled.
             */

            if (
                this.overview.enabled !== true
            ) {

                return false;

            }


            /*
             * Backend reports an active deployment.
             */

            if (
                this.overview.running_deployment
            ) {

                return false;

            }


            /*
             * Statistics should also report zero running.
             */

            if (
                Number(
                    this.overview.statistics?.running ?? 0
                ) > 0
            ) {

                return false;

            }


            /*
             * Local active deployment state.
             */

            if (
                this.activeDeployment &&
                [
                    'pending',
                    'running',
                ].includes(
                    this.activeDeployment.status
                )
            ) {

                return false;

            }


            return true;

        },


        /*
        |--------------------------------------------------------------------------
        | Pipeline Status
        |--------------------------------------------------------------------------
        */

        pipelineStepStatus(step) {

            if (!this.activeDeployment) {

                return 'pending';

            }


            const status =
                this.activeDeployment.status;


            /*
             * Completed deployment:
             * every enabled step is complete.
             */

            if (
                status === 'completed'
            ) {

                return step.enabled
                    ? 'completed'
                    : 'pending';

            }


            /*
             * Failed deployment:
             * determine the last visible step
             * from output.
             */

            if (
                status === 'failed'
            ) {

                const output =
                    this.activeDeployment.output || '';


                if (
                    this.stepAppearsInOutput(
                        step.key,
                        output
                    )
                ) {

                    return 'completed';

                }


                return 'failed';

            }


            /*
             * Pending/running deployment.
             */

            const output =
                this.activeDeployment.output || '';


            const current =
                this.detectCurrentStep(output);


            if (
                current === step.key
            ) {

                return 'running';

            }


            const currentIndex =
                this.pipeline.findIndex(

                    item =>
                        item.key === current

                );


            const stepIndex =
                this.pipeline.findIndex(

                    item =>
                        item.key === step.key

                );


            if (
                currentIndex >= 0 &&
                stepIndex < currentIndex
            ) {

                return 'completed';

            }


            return 'pending';

        },


        /*
        |--------------------------------------------------------------------------
        | Detect Current Step
        |--------------------------------------------------------------------------
        */

        detectCurrentStep(output) {

            if (!output) {

                return 'git';

            }


            const checks = [

                [
                    'health_check',
                    'health check',
                ],

                [
                    'queue_restart',
                    'queue restart',
                ],

                [
                    'queue_restart',
                    'queue:restart',
                ],

                [
                    'optimize',
                    'optimize',
                ],

                [
                    'migrations',
                    'migrations',
                ],

                [
                    'migrations',
                    'migration',
                ],

                [
                    'build',
                    'build',
                ],

                [
                    'npm',
                    'npm',
                ],

                [
                    'composer',
                    'composer',
                ],

                [
                    'git',
                    'git',
                ],

            ];


            const normalized =
                String(output).toLowerCase();


            /*
             * Find the LAST occurrence rather than
             * the first occurrence.
             */

            let latestKey = 'git';

            let latestIndex = -1;


            for (
                const [key, keyword]
                of checks
            ) {

                const index =
                    normalized.lastIndexOf(
                        keyword
                    );


                if (
                    index > latestIndex
                ) {

                    latestIndex = index;

                    latestKey = key;

                }

            }


            return latestKey;

        },


        /*
        |--------------------------------------------------------------------------
        | Step Appears In Output
        |--------------------------------------------------------------------------
        */

        stepAppearsInOutput(key, output) {

            const keywords = {

                git: [

                    'git',

                    'fetch',

                    'checkout',

                    'pull',

                ],

                composer: [

                    'composer',

                ],

                npm: [

                    'npm',

                ],

                build: [

                    'build',

                ],

                migrations: [

                    'migrate',

                    'migration',

                ],

                optimize: [

                    'optimize',

                ],

                queue_restart: [

                    'queue:restart',

                    'queue restart',

                ],

                health_check: [

                    'health check',

                    'database health',

                ],

            };


            const terms =
                keywords[key] || [];


            const normalized =
                String(output).toLowerCase();


            return terms.some(

                term =>
                    normalized.includes(term)

            );

        },


        /*
        |--------------------------------------------------------------------------
        | Pipeline Classes
        |--------------------------------------------------------------------------
        */

        pipelineStepClass(step) {

            const status =
                this.pipelineStepStatus(step);


            if (
                status === 'completed'
            ) {

                return 'border-green-200 bg-green-50';

            }


            if (
                status === 'running'
            ) {

                return 'border-primary-200 bg-primary-50';

            }


            if (
                status === 'failed'
            ) {

                return 'border-red-200 bg-red-50';

            }


            return 'border-gray-100 bg-white';

        },


        pipelineIconClass(step) {

            const status =
                this.pipelineStepStatus(step);


            if (
                status === 'completed'
            ) {

                return 'bg-green-100 text-green-600';

            }


            if (
                status === 'running'
            ) {

                return 'bg-primary-100 text-primary-600';

            }


            if (
                status === 'failed'
            ) {

                return 'bg-red-100 text-red-600';

            }


            return 'bg-gray-100 text-gray-500';

        },


        pipelineStatusTextClass(step) {

            const status =
                this.pipelineStepStatus(step);


            if (
                status === 'completed'
            ) {

                return 'text-green-600';

            }


            if (
                status === 'running'
            ) {

                return 'text-primary-600';

            }


            if (
                status === 'failed'
            ) {

                return 'text-red-600';

            }


            return 'text-gray-400';

        },


        /*
        |--------------------------------------------------------------------------
        | Status Helpers
        |--------------------------------------------------------------------------
        */

        statusClass(status) {

            if (
                status === 'completed'
            ) {

                return 'bg-green-50 text-green-700';

            }


            if (
                status === 'failed'
            ) {

                return 'bg-red-50 text-red-700';

            }


            if (
                status === 'running'
            ) {

                return 'bg-blue-50 text-blue-700';

            }


            if (
                status === 'pending'
            ) {

                return 'bg-yellow-50 text-yellow-700';

            }


            return 'bg-gray-100 text-gray-600';

        },


        deploymentSystemClass() {

            if (
                this.overview.status === 'enabled'
            ) {

                return 'bg-green-50 text-green-700';

            }


            if (
                this.overview.status === 'running'
            ) {

                return 'bg-blue-50 text-blue-700';

            }


            if (
                this.overview.status === 'disabled'
            ) {

                return 'bg-red-50 text-red-700';

            }


            return 'bg-gray-100 text-gray-600';

        },


        deploymentSystemLabel() {

            if (
                this.overview.status === 'enabled'
            ) {

                return 'Enabled';

            }


            if (
                this.overview.status === 'running'
            ) {

                return 'Deployment Running';

            }


            if (
                this.overview.status === 'disabled'
            ) {

                return 'Disabled';

            }


            return 'Checking...';

        },


        formatStatus(status) {

            if (!status) {

                return 'Unknown';

            }


            return String(status)

                .replaceAll(
                    '_',
                    ' '
                )

                .replace(
                    /\b\w/g,
                    char =>
                        char.toUpperCase()
                );

        },


        /*
        |--------------------------------------------------------------------------
        | Formatting
        |--------------------------------------------------------------------------
        */

        shortCommit(commit) {

            if (!commit) {

                return '—';

            }


            return String(commit).substring(
                0,
                8
            );

        },


        duration(deployment) {

            if (!deployment) {

                return '—';

            }


            if (
                deployment.duration_seconds !== null &&
                deployment.duration_seconds !== undefined
            ) {

                return this.formatSeconds(

                    deployment.duration_seconds

                );

            }


            if (
                deployment.started_at
            ) {

                const start =
                    new Date(
                        deployment.started_at
                    );


                const end =

                    deployment.completed_at

                        ? new Date(
                            deployment.completed_at
                        )

                        : new Date();


                const seconds =
                    Math.max(

                        0,

                        Math.floor(

                            (
                                end.getTime() -
                                start.getTime()
                            ) / 1000

                        )

                    );


                return this.formatSeconds(
                    seconds
                );

            }


            return '—';

        },


        formatSeconds(seconds) {

            seconds =
                Number(seconds) || 0;


            const minutes =
                Math.floor(
                    seconds / 60
                );


            const remaining =
                seconds % 60;


            if (
                minutes === 0
            ) {

                return `${remaining}s`;

            }


            return `${minutes}m ${remaining}s`;

        },


        formatDate(date) {

            if (!date) {

                return '—';

            }


            try {

                return new Intl.DateTimeFormat(

                    undefined,

                    {

                        dateStyle:
                            'medium',

                        timeStyle:
                            'short',

                    }

                ).format(

                    new Date(date)

                );

            } catch {

                return date;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Preflight Helpers
        |--------------------------------------------------------------------------
        */

        normalizedPreflightChecks() {

            if (
                !this.preflight ||
                !this.preflight.checks
            ) {

                return [];

            }


            const checks =
                this.preflight.checks;


            /*
             * Array:
             *
             * [
             *   {
             *      name: "...",
             *      status: "healthy"
             *   }
             * ]
             */

            if (
                Array.isArray(checks)
            ) {

                return checks.map(
                    (check, index) => ({

                        name:
                            check?.name ??
                            `check_${index}`,

                        status:
                            check?.status ??
                            'unknown',

                        message:
                            check?.message ??
                            null,

                    })
                );

            }


            /*
             * Object:
             *
             * {
             *    git: {
             *       status: "healthy"
             *    }
             * }
             */

            return Object.entries(
                checks
            ).map(
                ([name, check]) => ({

                    name,

                    status:
                        check?.status ??
                        'unknown',

                    message:
                        check?.message ??
                        null,

                })
            );

        },


        preflightCheckName(name) {

            if (!name) {

                return 'Check';

            }


            return String(name)

                .replaceAll(
                    '_',
                    ' '
                )

                .replace(
                    /\b\w/g,
                    char =>
                        char.toUpperCase()
                );

        },


        /*
        |--------------------------------------------------------------------------
        | History Helpers
        |--------------------------------------------------------------------------
        */

        normalizeHistory(data) {

            if (
                Array.isArray(data)
            ) {

                return data;

            }


            if (
                Array.isArray(data?.data)
            ) {

                return data.data;

            }


            if (
                Array.isArray(data?.items)
            ) {

                return data.items;

            }


            /*
             * Some Laravel responses may return
             * paginated data.
             */

            if (
                Array.isArray(data?.data?.data)
            ) {

                return data.data.data;

            }


            return [];

        },


        /*
        |--------------------------------------------------------------------------
        | CSRF
        |--------------------------------------------------------------------------
        */

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