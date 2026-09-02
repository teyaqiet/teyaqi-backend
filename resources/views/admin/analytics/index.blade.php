@extends('admin.layouts.main')

@section('title', 'Analytics')

@section('content')

<x-analytics-layout
    title="Analytics"
    subtitle="Monitor Teyaqi growth and player behavior."
>

    {{-- FILTER FORM --}}
    <div class="mb-5 flex justify-end">
        <form method="GET" action="{{ request()->url() }}" class="flex items-center gap-2">
            <label for="days" class="text-sm font-medium text-gray-700">Range:</label>
            <select name="days" id="days" onchange="this.form.submit()" class="form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="7" {{ $selectedDays == 7 ? 'selected' : '' }}>Last 7 Days</option>
                <option value="14" {{ $selectedDays == 14 ? 'selected' : '' }}>Last 14 Days</option>
                <option value="30" {{ $selectedDays == 30 ? 'selected' : '' }}>Last 30 Days</option>
                <option value="60" {{ $selectedDays == 60 ? 'selected' : '' }}>Last 60 Days</option>
                <option value="90" {{ $selectedDays == 90 ? 'selected' : '' }}>Last 90 Days</option>
            </select>
        </form>
    </div>

    {{-- KPI OVERVIEW --}}
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <x-analytics-stat
            title="Total Players"
            value="{{ number_format($totalUsers) }}"
            change="+12%"
            description="vs last month"
            icon="ik ik-users"
        />

        <x-analytics-stat
            title="Active Today"
            value="{{ number_format($activeToday) }}"
            change="+8%"
            description="daily active users"
            icon="ik ik-activity"
        />

        <x-analytics-stat
            title="Questions"
            value="{{ number_format($totalQuestions) }}"
            change="+5%"
            description="question bank size"
            icon="ik ik-help-circle"
        />

        <x-analytics-stat
            title="Challenges"
            value="{{ number_format($totalChallenges) }}"
            change="+15%"
            description="available challenges"
            icon="ik ik-award"
        />
    </div>

    <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <x-analytics-stat
            title="New Players"
            value="{{ number_format($newPlayers) }}"
            description="In selected period"
            icon="ik ik-user-plus"
        />

        <x-analytics-stat
            title="Best SR"
            value="{{ number_format($bestSr) }}"
            description="Highest player rating"
            icon="ik ik-award"
        />

        <x-analytics-stat
            title="Average XP"
            value="{{ number_format($averageXp) }}"
            description="Player experience"
            icon="ik ik-star"
        />

        <x-analytics-stat
            title="Average SR"
            value="{{ number_format($averageSr) }}"
            description="Skill rating"
            icon="ik ik-target"
        />
    </div>

    {{-- CHARTS --}}
    <div class="mt-6 grid gap-5 xl:grid-cols-2">
        <x-analytics-chart title="User Growth" subtitle="New registrations over time">
            <div id="user-growth-chart"></div>
        </x-analytics-chart>

        <x-analytics-chart title="Question Performance" subtitle="Question accuracy distribution">
            <div id="question-performance-chart"></div>
        </x-analytics-chart>
    </div>

    <div class="mt-6 grid gap-5 xl:grid-cols-2">
        <x-analytics-chart title="XP Distribution" subtitle="Players grouped by XP">
            <div id="level-chart"></div>
        </x-analytics-chart>

        <x-analytics-chart title="SR Distribution" subtitle="Skill rating ranges">
            <div id="sr-chart"></div>
        </x-analytics-chart>
    </div>

    <div class="mt-6 grid gap-5 xl:grid-cols-2">
        <x-analytics-chart title="Challenge Attempts" subtitle="Daily challenge activity">
            <div id="challenge-attempt-chart"></div>
        </x-analytics-chart>

        <x-analytics-chart title="Most Played Challenges" subtitle="Challenges with highest attempts">
            <div id="top-challenges-chart"></div>
        </x-analytics-chart>
    </div>

</x-analytics-layout>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const userGrowth = document.querySelector("#user-growth-chart");
    if (userGrowth) {
        new ApexCharts(userGrowth, {
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            series: [{ name: 'Users', data: @json($userGrowth) }],
            xaxis: { categories: @json($userGrowthLabels) },
            stroke: { curve: 'smooth' },
            dataLabels: { enabled: false }
        }).render();
    }

    const questionChart = document.querySelector("#question-performance-chart");
    if (questionChart) {
        new ApexCharts(questionChart, {
            chart: { type: 'donut', height: 300 },
            series: @json($questionAccuracy),
            labels: ['Easy', 'Medium', 'Hard']
        }).render();
    }

    const attemptsChart = document.querySelector("#challenge-attempt-chart");
    if (attemptsChart) {
        new ApexCharts(attemptsChart, {
            chart: { type: 'line', height: 300 },
            series: [{ name: 'Attempts', data: @json($challengeAttemptData) }],
            xaxis: { categories: @json($challengeAttemptLabels) },
            stroke: { curve: 'smooth' }
        }).render();
    }

    const topChallengesChart = document.querySelector("#top-challenges-chart");
    if (topChallengesChart) {
        new ApexCharts(topChallengesChart, {
            chart: { type: 'bar', height: 300 },
            series: [{ name: 'Attempts', data: @json($topChallenges->pluck('attempts_count')) }],
            xaxis: { categories: @json($topChallenges->pluck('title')) }
        }).render();
    }

    const xpChart = document.querySelector("#level-chart");
    if (xpChart) {
        new ApexCharts(xpChart, {
            chart: { type: 'bar', height: 300 },
            series: [{ name: 'Players', data: @json(array_values($xpDistribution)) }],
            xaxis: { categories: @json(array_keys($xpDistribution)) }
        }).render();
    }

    const srChart = document.querySelector("#sr-chart");
    if (srChart) {
        new ApexCharts(srChart, {
            chart: { type: 'donut', height: 300 },
            series: @json(array_values($srDistribution)),
            labels: @json(array_keys($srDistribution))
        }).render();
    }

});
</script>

@endsection