@props([
    'title',
    'subtitle' => null,
    'icon' => 'ik ik-trending-up'
])

<x-page-header
    :title="$title"
    :subtitle="$subtitle"
    :icon="$icon"
    :breadcrumbs="[
        'Home' => url('dashboard'),
        'Analytics' => null
    ]"
/>

{{-- FILTER BAR --}}
<x-card
    class="mb-5"
    x-data="{
        from: '{{ request('from', now()->subDays(29)->format('Y-m-d')) }}',
        to: '{{ request('to', now()->format('Y-m-d')) }}',
        group: '{{ request('group', 'day') }}',
        setQuickRange(range) {
            const today = new Date();
            let startDate = new Date();
            
            if (range === 'Today') {
                startDate = today;
            } else if (range === 'This Week') {
                const dayOfWeek = today.getDay(); // 0 is Sunday
                startDate.setDate(today.getDate() - dayOfWeek);
            } else if (range === 'This Month') {
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            } else if (range === 'This Year') {
                startDate = new Date(today.getFullYear(), 0, 1);
            }
            
            this.from = startDate.toISOString().split('T')[0];
            this.to = today.toISOString().split('T')[0];
            $nextTick(() => { $refs.filterForm.submit(); });
        }
    }"
>
    <form x-ref="filterForm" method="GET" action="{{ request()->url() }}" class="flex flex-wrap items-end gap-3">

        {{-- FROM --}}
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-500">
                From
            </label>
            <input
                type="date"
                name="from"
                x-model="from"
                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
            >
        </div>

        {{-- TO --}}
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-500">
                To
            </label>
            <input
                type="date"
                name="to"
                x-model="to"
                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
            >
        </div>

        {{-- GROUP BY --}}
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-500">
                Group By
            </label>
            <select
                name="group"
                x-model="group"
                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"
            >
                <option value="day">Day</option>
                <option value="week">Week</option>
                <option value="month">Month</option>
                <option value="year">Year</option>
            </select>
        </div>

        {{-- QUICK RANGES --}}
        <div class="flex gap-2">
            @foreach([
                'Today',
                'This Week',
                'This Month',
                'This Year'
            ] as $range)
                <button
                    type="button"
                    @click="setQuickRange('{{ $range }}')"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50"
                >
                    {{ $range }}
                </button>
            @endforeach
        </div>

        {{-- ACTIONS --}}
        <div class="ml-auto flex gap-2">
            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:ring-2 focus:ring-primary-100"
            >
                <i class="ik ik-filter"></i>
                Apply
            </button>

            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
            >
                <i class="ik ik-download"></i>
                Export
            </button>
        </div>

    </form>
</x-card>

{{ $slot }}