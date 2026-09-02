@extends('admin.layouts.main')

@section('title', 'Game Sessions')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Game Sessions</h1>
            <p class="mt-1 text-sm text-gray-500">
                Monitor player games, performance, and session history.
            </p>
        </div>

        <div class="w-fit rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-500">
            {{ number_format($sessions->total()) }} total sessions
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm font-medium text-gray-500">Total Sessions</p>
            <p class="mt-2 text-2xl font-bold text-gray-800">
                {{ number_format($totalSessions ?? 0) }}
            </p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm font-medium text-gray-500">Finished</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">
                {{ number_format($completedSessions ?? 0) }}
            </p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm font-medium text-gray-500">XP Earned</p>
            <p class="mt-2 text-2xl font-bold text-amber-500">
                {{ number_format($totalXp ?? 0) }}
            </p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm font-medium text-gray-500">Average Accuracy</p>
            <p class="mt-2 text-2xl font-bold text-indigo-600">
                {{ number_format($averageAccuracy ?? 0, 1) }}%
            </p>
        </div>
    </div>

    {{-- Filters (Single-Row Layout) --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('admin.game-sessions.index') }}">
            <div class="flex flex-wrap items-end gap-3 md:flex-nowrap">
                
                {{-- Search --}}
                <div class="w-full md:w-2/5">
                    <label class="mb-1 block text-xs font-medium text-gray-500">Search Player</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, username, Telegram ID..."
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                </div>

                {{-- From Date --}}
                <div class="w-full sm:w-auto">
                    <label class="mb-1 block text-xs font-medium text-gray-500">From</label>
                    <input
                        type="date"
                        name="from"
                        value="{{ request('from') }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                </div>

                {{-- To Date --}}
                <div class="w-full sm:w-auto">
                    <label class="mb-1 block text-xs font-medium text-gray-500">To</label>
                    <input
                        type="date"
                        name="to"
                        value="{{ request('to') }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                </div>

                {{-- Status --}}
                <div class="w-full sm:w-auto">
                    <label class="mb-1 block text-xs font-medium text-gray-500">Status</label>
                    <select
                        name="status"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >
                        <option value="">All Statuses</option>
                        <option value="finished" @selected(request('status') === 'finished')>Finished</option>
                        <option value="stopped" @selected(request('status') === 'stopped')>Quit / Stopped</option>
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 pt-1 sm:pt-0">
                    <button
                        type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800"
                    >
                        Filter
                    </button>

                    @if(request()->anyFilled(['search', 'from', 'to', 'status']))
                        <a
                            href="{{ route('admin.game-sessions.index') }}"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                        >
                            Reset
                        </a>
                    @endif
                </div>

            </div>
        </form>
    </div>

    {{-- Sessions Table --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-sm text-gray-600">
                <thead class="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">Player</th>
                        <th scope="col" class="px-5 py-3.5">Score</th>
                        <th scope="col" class="px-5 py-3.5">XP</th>
                        <th scope="col" class="px-5 py-3.5">Lives Lost</th>
                        <th scope="col" class="px-5 py-3.5">Streak</th>
                        <th scope="col" class="px-5 py-3.5">Status</th>
                        <th scope="col" class="px-5 py-3.5">Date</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                @forelse($sessions as $session)
                    <tr class="transition hover:bg-gray-50/80">
                        {{-- Player --}}
                        <td class="px-5 py-4">
                           <a href="{{ route('admin.game-sessions.show',$session) }}"
                               class="flex items-center gap-3">
                            <div class="flex items-center gap-3">
                            
                                <div class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-sm font-bold text-gray-500 ring-1 ring-gray-200">
                                    @if($session->user?->avatar)
                                        <img
                                            src="{{ asset('storage/' . ltrim($session->user->avatar, '/')) }}"
                                            alt="{{ $session->user->name }}"
                                            class="absolute inset-0 h-full w-full object-cover"
                                            onerror="this.remove()"
                                        >
                                    @endif
                                    <span>{{ strtoupper(substr($session->user?->name ?? 'U', 0, 1)) }}</span>
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900">
                                        {{ $session->user?->name ?? 'Unknown User' }}
                                    </p>
                                    <p class="truncate text-xs text-gray-500">
                                        @if($session->user?->username)
                                            {{ '@' . $session->user->username }}
                                        @elseif($session->user?->telegram_id)
                                            Telegram: {{ $session->user->telegram_id }}
                                        @else
                                            No identifier
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>

                        {{-- Score --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="inline-flex rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                                {{ $session->correct_answers }} / {{ $session->total_questions }}
                            </span>
                            <p class="mt-1 text-xs text-gray-400">
                                {{ number_format(($session->correct_answers / max($session->total_questions, 1)) * 100, 1) }}%
                            </p>
                        </td>

                        {{-- XP --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <p class="font-semibold text-gray-900">
                                +{{ number_format($session->xp_earned) }}
                            </p>
                            <p class="text-xs text-gray-400">
                                Base: {{ number_format($session->base_xp) }}
                            </p>
                        </td>

                        {{-- Lives --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-semibold text-rose-600">
                                -{{ $session->lives_lost }}
                            </span>
                        </td>

                        {{-- Streak --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 font-semibold text-gray-800">
                                <span>🔥</span>
                                <span>{{ $session->current_streak }}</span>
                            </div>
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($session->is_completed)
                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                    Finished
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                    Quit
                                </span>
                            @endif
                        </td>

                        {{-- Date --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <p class="text-xs font-medium text-gray-700">
                                {{ $session->created_at->format('M d, Y') }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $session->created_at->format('H:i') }}
                            </p>
                        </td>

                        {{-- Action --}}
                        <td class="px-5 py-4 text-right whitespace-nowrap">
                            <a
                                href="{{ route('admin.game-sessions.show', $session) }}"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-indigo-600 focus:outline-none"
                            >
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                🔍
                            </div>
                            <div class="mt-3 text-sm font-medium text-gray-900">
                                No sessions found
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Try adjusting or clearing your filters.
                            </p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($sessions->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $sessions->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

@endsection