@extends('admin.layouts.main')

@section('title', 'Alerts')

@section('content')

<div
    x-data="operationsAlerts()"
    x-init="init()"
    class="space-y-6"
>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <div class="flex items-center gap-3">

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600">
                    <i class="ik ik-alert-triangle text-lg"></i>
                </div>

                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">
                        Alerts
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Monitor and manage important operational issues.
                    </p>
                </div>

            </div>
        </div>

        <button
            type="button"
            @click="refresh()"
            :disabled="loading"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
        >
            <i
                class="ik ik-refresh-cw"
                :class="{ 'animate-spin': loading }"
            ></i>

            <span x-text="loading ? 'Refreshing...' : 'Refresh'"></span>
        </button>

    </div>


    {{-- Error --}}
    <template x-if="error">

        <div class="rounded-xl border border-red-200 bg-red-50 p-4">

            <div class="flex items-start gap-3">

                <div class="mt-0.5 text-red-600">
                    <i class="ik ik-alert-circle"></i>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-red-800">
                        Unable to load alerts
                    </h3>

                    <p
                        class="mt-1 text-sm text-red-700"
                        x-text="error"
                    ></p>
                </div>

            </div>

        </div>

    </template>


    {{-- Statistics --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- Critical --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Critical
                    </p>

                    <p
                        class="mt-2 text-3xl font-semibold text-red-600"
                        x-text="statistics.critical ?? 0"
                    ></p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-red-600">
                    <i class="ik ik-alert-octagon text-lg"></i>
                </div>

            </div>

        </div>


        {{-- Warning --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Warnings
                    </p>

                    <p
                        class="mt-2 text-3xl font-semibold text-amber-600"
                        x-text="statistics.warning ?? 0"
                    ></p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <i class="ik ik-alert-triangle text-lg"></i>
                </div>

            </div>

        </div>


        {{-- Info --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Information
                    </p>

                    <p
                        class="mt-2 text-3xl font-semibold text-blue-600"
                        x-text="statistics.info ?? 0"
                    ></p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i class="ik ik-info text-lg"></i>
                </div>

            </div>

        </div>


        {{-- Total --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Total Active
                    </p>

                    <p
                        class="mt-2 text-3xl font-semibold text-gray-800"
                        x-text="statistics.active ?? 0"
                    ></p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-gray-600">
                    <i class="ik ik-bell text-lg"></i>
                </div>

            </div>

        </div>

    </div>


    {{-- Filters --}}
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div class="flex flex-wrap gap-2">

                <button
                    type="button"
                    @click="setStatus('active')"
                    :class="status === 'active'
                        ? 'bg-gray-900 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition"
                >
                    Active
                </button>

                <button
                    type="button"
                    @click="setStatus('acknowledged')"
                    :class="status === 'acknowledged'
                        ? 'bg-gray-900 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition"
                >
                    Acknowledged
                </button>

                <button
                    type="button"
                    @click="setStatus('resolved')"
                    :class="status === 'resolved'
                        ? 'bg-gray-900 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition"
                >
                    Resolved
                </button>

            </div>


            <select
                x-model="severity"
                @change="refresh()"
                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            >
                <option value="">All severities</option>
                <option value="critical">Critical</option>
                <option value="warning">Warning</option>
                <option value="info">Info</option>
            </select>

        </div>

    </div>


    {{-- Loading --}}
    <template x-if="loading && !loaded">

        <div class="rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-100">

            <i class="ik ik-loader animate-spin text-2xl text-primary-600"></i>

            <p class="mt-3 text-sm text-gray-500">
                Loading alerts...
            </p>

        </div>

    </template>


    {{-- Alerts --}}
    <template x-if="loaded">

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

            {{-- Empty --}}
            <template x-if="alerts.length === 0">

                <div class="px-6 py-14 text-center">

                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-50 text-green-600">
                        <i class="ik ik-check-circle text-xl"></i>
                    </div>

                    <h3 class="mt-4 text-sm font-semibold text-gray-800">
                        No alerts found
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Everything looks good for this filter.
                    </p>

                </div>

            </template>


            {{-- Alert list --}}
            <template x-if="alerts.length > 0">

                <div class="divide-y divide-gray-100">

                    <template
                        x-for="alert in alerts"
                        :key="alert.id"
                    >

                        <div class="p-5 transition hover:bg-gray-50/50">

                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

                                <div class="flex min-w-0 gap-4">

                                    {{-- Severity indicator --}}
                                    <div
                                        class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                        :class="severityClasses(alert.severity).icon"
                                    >
                                        <i
                                            class="text-lg"
                                            :class="severityClasses(alert.severity).iconName"
                                        ></i>
                                    </div>


                                    <div class="min-w-0">

                                        <div class="flex flex-wrap items-center gap-2">

                                            <h3
                                                class="font-semibold text-gray-800"
                                                x-text="alert.title"
                                            ></h3>

                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                                :class="severityClasses(alert.severity).badge"
                                                x-text="capitalize(alert.severity)"
                                            ></span>

                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                                :class="statusClasses(alert.status)"
                                                x-text="capitalize(alert.status)"
                                            ></span>

                                        </div>


                                        <p
                                            class="mt-2 text-sm text-gray-600"
                                            x-text="alert.message"
                                        ></p>


                                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400">

                                            <span>
                                                Source:
                                                <span
                                                    class="font-medium text-gray-500"
                                                    x-text="alert.source"
                                                ></span>
                                            </span>

                                            <span>
                                                Detected:
                                                <span
                                                    class="font-medium text-gray-500"
                                                    x-text="formatDate(alert.last_detected_at)"
                                                ></span>
                                            </span>

                                            <template x-if="alert.first_detected_at">
                                                <span>
                                                    First detected:
                                                    <span
                                                        class="font-medium text-gray-500"
                                                        x-text="formatDate(alert.first_detected_at)"
                                                    ></span>
                                                </span>
                                            </template>

                                        </div>

                                    </div>

                                </div>


                                {{-- Actions --}}
                                <div class="flex shrink-0 items-center gap-2 lg:pt-1">

                                    <template x-if="alert.status === 'active'">

                                        <button
                                            type="button"
                                            @click="acknowledge(alert)"
                                            :disabled="actionLoading === alert.id"
                                            class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Acknowledge
                                        </button>

                                    </template>


                                    <template x-if="alert.status !== 'resolved'">

                                        <button
                                            type="button"
                                            @click="resolve(alert)"
                                            :disabled="actionLoading === alert.id"
                                            class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <span
                                                x-text="actionLoading === alert.id ? 'Working...' : 'Resolve'"
                                            ></span>
                                        </button>

                                    </template>

                                </div>

                            </div>

                        </div>

                    </template>

                </div>

            </template>

        </div>

    </template>


    {{-- Footer --}}
    <div class="flex items-center justify-between text-xs text-gray-400">

        <span>
            Operations Center
        </span>

        <span>
            Last updated:
            <span
                class="font-medium text-gray-500"
                x-text="lastUpdated || '—'"
            ></span>
        </span>

    </div>

</div>


<script>
function operationsAlerts() {
    return {
        loading: false,
        loaded: false,
        error: null,

        alerts: [],
        statistics: {
            total: 0,
            active: 0,
            critical: 0,
            warning: 0,
            info: 0,
            resolved: 0,
        },

        status: 'active',
        severity: '',

        actionLoading: null,
        lastUpdated: null,

        refreshTimer: null,

        async init() {
            await this.refresh();

            this.refreshTimer = setInterval(() => {
                this.refresh(true);
            }, 30000);
        },

        async refresh(silent = false) {
            if (!silent) {
                this.loading = true;
            }

            this.error = null;

            try {
                const params = new URLSearchParams();

                params.set('status', this.status);

                if (this.severity) {
                    params.set('severity', this.severity);
                }

                const response = await fetch(
                    `/api/admin/operations/alerts?${params.toString()}`,
                    {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const json = await response.json();

                if (!response.ok || !json.success) {
                    throw new Error(
                        json.message || 'Unable to load alerts.'
                    );
                }

                const data = json.data || {};

                this.statistics = data.statistics || {
                    total: 0,
                    active: 0,
                    critical: 0,
                    warning: 0,
                    info: 0,
                    resolved: 0,
                };

                this.alerts = Array.isArray(data.alerts)
                    ? data.alerts
                    : [];

                this.lastUpdated = new Date().toLocaleTimeString();
                this.loaded = true;

            } catch (error) {
                console.error('Operations Alerts error:', error);

                this.error =
                    error?.message ||
                    'Unable to load alerts.';
            } finally {
                if (!silent) {
                    this.loading = false;
                }
            }
        },

        async setStatus(status) {
            this.status = status;
            await this.refresh();
        },

        async acknowledge(alert) {
            if (this.actionLoading) {
                return;
            }

            this.actionLoading = alert.id;

            try {
                const response = await this.post(
                    `/api/admin/operations/alerts/${alert.id}/acknowledge`
                );

                if (!response.ok || !response.data?.success) {
                    throw new Error(
                        response.data?.message ||
                        'Unable to acknowledge alert.'
                    );
                }

                await this.refresh();

            } catch (error) {
                console.error(error);
                this.error =
                    error?.message ||
                    'Unable to acknowledge alert.';
            } finally {
                this.actionLoading = null;
            }
        },

        async resolve(alert) {
            if (this.actionLoading) {
                return;
            }

            this.actionLoading = alert.id;

            try {
                const response = await this.post(
                    `/api/admin/operations/alerts/${alert.id}/resolve`
                );

                if (!response.ok || !response.data?.success) {
                    throw new Error(
                        response.data?.message ||
                        'Unable to resolve alert.'
                    );
                }

                await this.refresh();

            } catch (error) {
                console.error(error);
                this.error =
                    error?.message ||
                    'Unable to resolve alert.';
            } finally {
                this.actionLoading = null;
            }
        },

        async post(url) {
            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token
                        ? {
                            'X-CSRF-TOKEN': token,
                        }
                        : {}),
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            return {
                ok: response.ok,
                data,
            };
        },

        severityClasses(severity) {
            switch (severity) {
                case 'critical':
                    return {
                        icon: 'bg-red-50 text-red-600',
                        badge: 'bg-red-50 text-red-700',
                        iconName: 'ik-alert-octagon',
                    };

                case 'warning':
                    return {
                        icon: 'bg-amber-50 text-amber-600',
                        badge: 'bg-amber-50 text-amber-700',
                        iconName: 'ik-alert-triangle',
                    };

                default:
                    return {
                        icon: 'bg-blue-50 text-blue-600',
                        badge: 'bg-blue-50 text-blue-700',
                        iconName: 'ik-info',
                    };
            }
        },

        statusClasses(status) {
            switch (status) {
                case 'acknowledged':
                    return 'bg-purple-50 text-purple-700';

                case 'resolved':
                    return 'bg-green-50 text-green-700';

                default:
                    return 'bg-gray-100 text-gray-600';
            }
        },

        capitalize(value) {
            if (!value) {
                return '—';
            }

            return value.charAt(0).toUpperCase() + value.slice(1);
        },

        formatDate(value) {
            if (!value) {
                return '—';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleString();
        },

        destroy() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
            }
        },
    };
}
</script>

@endsection