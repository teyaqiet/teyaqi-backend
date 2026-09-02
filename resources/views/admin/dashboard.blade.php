@extends('admin.layouts.main')

@section('title', 'Dashboard')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            Teyaqi Dashboard
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Monitor your game, players, content and activity.
        </p>
    </div>


    {{-- Main Stats --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Users --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">
                        Total Users
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-800">
                        {{ number_format($stats['users']) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i class="ik ik-users text-xl"></i>
                </div>
            </div>

            <p class="mt-4 text-xs text-gray-500">
                +{{ number_format($stats['new_users_today']) }} today
            </p>
        </div>


        {{-- Active Users --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">
                        Active Today
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-800">
                        {{ number_format($stats['active_today']) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <i class="ik ik-activity text-xl"></i>
                </div>
            </div>

            <p class="mt-4 text-xs text-gray-500">
                Players active today
            </p>
        </div>


        {{-- Games --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">
                        Games Today
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-800">
                        {{ number_format($stats['games_today']) }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                    <i class="ik ik-play text-xl"></i>
                </div>
            </div>

            <p class="mt-4 text-xs text-gray-500">
                Game sessions started today
            </p>
        </div>


        {{-- Accuracy --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">
                        Accuracy Today
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-800">
                        {{ $stats['accuracy_today'] }}%
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                    <i class="ik ik-check-circle text-xl"></i>
                </div>
            </div>

            <p class="mt-4 text-xs text-gray-500">
                {{ number_format($stats['answers_today']) }} answers submitted
            </p>
        </div>

    </div>


    {{-- Secondary Stats --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm text-gray-500">
                Questions
            </p>

            <p class="mt-2 text-2xl font-bold text-gray-800">
                {{ number_format($stats['questions']) }}
            </p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm text-gray-500">
                Categories
            </p>

            <p class="mt-2 text-2xl font-bold text-gray-800">
                {{ number_format($stats['categories']) }}
            </p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm text-gray-500">
                Challenges
            </p>

            <p class="mt-2 text-2xl font-bold text-gray-800">
                {{ number_format($stats['challenges']) }}
            </p>
        </div>

    </div>


</pre>
    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        {{-- Games Chart --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

            <div class="mb-5">
                <h2 class="font-semibold text-gray-800">
                    Games Played
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Game sessions over the last 7 days
                </p>
            </div>

            <div
                id="gamesChart"
                class="h-[300px]"
                data-chart='@json($gameChart)'
            ></div>

        </div>


        {{-- Users Chart --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

            <div class="mb-5">
                <h2 class="font-semibold text-gray-800">
                    New Users
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    New registrations over the last 7 days
                </p>
            </div>

            <div
                id="usersChart"
                class="h-[300px]"
                data-chart='@json($userChart)'
            ></div>

        </div>

    </div>


    {{-- Quick Overview --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

        <div class="mb-5">
            <h2 class="font-semibold text-gray-800">
                Today's Activity
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Quick overview of what is happening in Teyaqi today.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs text-gray-500">
                    Answers
                </p>

                <p class="mt-1 text-xl font-bold text-gray-800">
                    {{ number_format($stats['answers_today']) }}
                </p>
            </div>

            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs text-gray-500">
                    Correct Answers
                </p>

                <p class="mt-1 text-xl font-bold text-gray-800">
                    {{ number_format($stats['correct_answers_today']) }}
                </p>
            </div>

            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs text-gray-500">
                    Active Players
                </p>

                <p class="mt-1 text-xl font-bold text-gray-800">
                    {{ number_format($stats['active_today']) }}
                </p>
            </div>

        </div>

    </div>

</div>


{{-- Charts --}}

@push('head')
    @vite('resources/js/charts.js')
@endpush

@push('script')

<script>
document.addEventListener('DOMContentLoaded', function () {

    function createChart(elementId) {

        const element = document.getElementById(elementId);

        if (!element) {
            console.error('Chart element not found:', elementId);
            return;
        }

        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts is not loaded.');
            return;
        }

        let data = [];

        try {
            data = JSON.parse(element.dataset.chart || '[]');
        } catch (error) {
            console.error('Invalid chart data:', error);
            return;
        }

        console.log(elementId, data);

        const categories = data.map(item => item.date);
        const values = data.map(item => Number(item.value));

        const chart = new ApexCharts(element, {
            chart: {
                type: 'area',
                height: 300,
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                }
            },

            series: [{
                name: 'Total',
                data: values
            }],

            xaxis: {
                categories: categories
            },

            stroke: {
                curve: 'smooth',
                width: 3
            },

            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.35,
                    opacityTo: 0.05
                }
            },

            dataLabels: {
                enabled: false
            },

            tooltip: {
                y: {
                    formatter: function (value) {
                        return value.toLocaleString();
                    }
                }
            }
        });

        chart.render();
    }

    function bootCharts() {
        createChart('gamesChart');
        createChart('usersChart');
    }

    if (window.chartsReady) {
        bootCharts();
    } else {
        document.addEventListener('charts:loaded', bootCharts, {
            once: true
        });
    }

});
</script>

@endpush

@endsection