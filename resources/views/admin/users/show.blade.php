@extends('admin.layouts.main')

@section('title', $user->name . ' — User Details')

@section('content')

<div class="space-y-6">


{{-- ================================================================
     HEADER
================================================================= --}}

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

    <div class="flex items-center gap-4">

        <a
            href="{{ route('admin.users.index') }}"
            class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50"
        >
            <i class="ik ik-arrow-left"></i>
        </a>

        <div>

            <h1 class="text-2xl font-bold text-gray-800">
                User Details
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Player profile and gameplay information.
            </p>

        </div>

    </div>


    <div class="flex gap-2">

        <a
            href="{{ route('admin.users.edit', $user) }}"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
            Edit User
        </a>


        <a
            href="{{ route('admin.users.index') }}"
            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
        >
            Back
        </a>

    </div>

</div>



{{-- ================================================================
     PROFILE
================================================================= --}}

<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">


        {{-- =========================================================
             PLAYER IDENTITY
        ========================================================== --}}

        <div class="flex min-w-0 items-center gap-4">


            {{-- AVATAR --}}

            <div class="relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-2xl font-bold text-gray-500 ring-1 ring-gray-200">

                @if($user->avatar)

                    <img
                        src="{{ asset('storage/' . ltrim($user->avatar, '/')) }}"
                        alt="{{ $user->name ?? 'User' }}"
                        class="absolute inset-0 h-full w-full object-cover"
                        onerror="this.style.display='none'"
                    >

                @endif

                <span>
                    {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                </span>

            </div>



            {{-- PLAYER INFORMATION --}}

            <div class="min-w-0">

                {{-- NAME --}}

                <h2 class="break-words text-xl font-bold text-gray-800">
                    {{ $user->name ?? 'Unknown User' }}
                </h2>


                {{-- USERNAME / TELEGRAM / EMAIL --}}

                <p class="mt-1 break-all text-sm text-gray-500">

                    @if($user->username)

                        {{ '@' . $user->username }}

                    @elseif($user->telegram_id)

                        Telegram:
                        {{ $user->telegram_id }}

                    @elseif($user->email)

                        {{ $user->email }}

                    @else

                        No identifier

                    @endif

                </p>


                {{-- STATUS BADGES --}}

                <div class="mt-2 flex flex-wrap items-center gap-2">

                    @if($user->has_onboarded)

                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-600">
                            Onboarded
                        </span>

                    @else

                        <span class="rounded-full bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-600">
                            Not Onboarded
                        </span>

                    @endif


                    @if($user->language)

                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                            {{ strtoupper($user->language) }}
                        </span>

                    @endif

                </div>

            </div>

        </div>



        {{-- =========================================================
             REGISTRATION
        ========================================================== --}}

        <div class="text-left md:text-right">

            <p class="text-xs text-gray-400">
                Registered
            </p>

            <p class="mt-1 text-sm font-medium text-gray-700">
                {{ $user->created_at?->format('M d, Y') ?? 'Unknown' }}
            </p>

            <p class="mt-1 text-xs text-gray-400">
                {{ $user->created_at?->format('h:i A') ?? '' }}
            </p>

        </div>

    </div>



    {{-- =========================================================
         PROFILE METADATA
    ========================================================== --}}

    <div class="mt-6 grid grid-cols-2 gap-4 border-t border-gray-100 pt-6 md:grid-cols-4">


        {{-- TELEGRAM ID --}}

        <div>

            <p class="text-xs text-gray-400">
                Telegram ID
            </p>

            <p class="mt-1 truncate text-sm font-medium text-gray-700">
                {{ $user->telegram_id ?? '—' }}
            </p>

        </div>


        {{-- EMAIL --}}

        <div>

            <p class="text-xs text-gray-400">
                Email
            </p>

            <p class="mt-1 truncate text-sm font-medium text-gray-700">
                {{ $user->email ?: '—' }}
            </p>

        </div>


        {{-- GENDER --}}

        <div>

            <p class="text-xs text-gray-400">
                Gender
            </p>

            <p class="mt-1 text-sm font-medium text-gray-700">
                {{ $user->gender ? ucfirst($user->gender) : '—' }}
            </p>

        </div>


        {{-- LAST PLAYED --}}

        <div>

            <p class="text-xs text-gray-400">
                Last Played
            </p>

            <p class="mt-1 text-sm font-medium text-gray-700">
                {{ $user->last_played_date?->format('M d, Y') ?? 'Never' }}
            </p>

        </div>

    </div>

</div>



{{-- ================================================================
     PLAYER STATS
================================================================= --}}

<div class="grid grid-cols-2 gap-5 md:grid-cols-3 xl:grid-cols-6">


    {{-- XP --}}

    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

        <p class="text-xs text-gray-500">
            Total XP
        </p>

        <p class="mt-2 text-2xl font-bold text-gray-800">
            {{ number_format($user->total_xp) }}
        </p>

        <p class="mt-1 text-xs text-gray-400">
            {{ number_format($user->weekly_xp) }} weekly
        </p>

    </div>


    {{-- SR --}}

    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

        <p class="text-xs text-gray-500">
            Current SR
        </p>

        <p class="mt-2 text-2xl font-bold text-indigo-600">
            {{ number_format($user->current_sr, 1) }}
        </p>

        <p class="mt-1 text-xs text-gray-400">
            Best: {{ number_format($user->best_sr, 1) }}
        </p>

    </div>


    {{-- STREAK --}}

    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

        <p class="text-xs text-gray-500">
            Current Streak
        </p>

        <p class="mt-2 text-2xl font-bold text-orange-500">
            🔥 {{ number_format($user->current_streak) }}
        </p>

        <p class="mt-1 text-xs text-gray-400">
            Best: {{ number_format($user->best_streak) }}
        </p>

    </div>


    {{-- ANSWERS --}}

    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

        <p class="text-xs text-gray-500">
            Answers
        </p>

        <p class="mt-2 text-2xl font-bold text-gray-800">
            {{ number_format($user->total_answers_count) }}
        </p>

        <p class="mt-1 text-xs text-gray-400">
            Total submitted
        </p>

    </div>


    {{-- COINS --}}

    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

        <p class="text-xs text-gray-500">
            Coins
        </p>

        <p class="mt-2 text-2xl font-bold text-yellow-600">
            {{ number_format($user->total_coins) }}
        </p>

        <p class="mt-1 text-xs text-gray-400">
            Total balance
        </p>

    </div>


    {{-- WINS --}}

    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

        <p class="text-xs text-gray-500">
            Wins
        </p>

        <p class="mt-2 text-2xl font-bold text-green-600">
            {{ number_format($user->total_wins) }}
        </p>

        <p class="mt-1 text-xs text-gray-400">
            Total wins
        </p>

    </div>

</div>



{{-- ================================================================
     GAME OVERVIEW
================================================================= --}}

<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

    <div class="mb-5">

        <h2 class="font-semibold text-gray-800">
            Game Overview
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Overall gameplay performance for this player.
        </p>

    </div>


    <div class="grid grid-cols-2 gap-5 md:grid-cols-3 xl:grid-cols-6">


        <div class="rounded-lg bg-gray-50 p-4">

            <p class="text-xs text-gray-500">
                Sessions
            </p>

            <p class="mt-1 text-xl font-bold text-gray-800">
                {{ number_format($gameStats['sessions']) }}
            </p>

        </div>


        <div class="rounded-lg bg-gray-50 p-4">

            <p class="text-xs text-gray-500">
                Completed
            </p>

            <p class="mt-1 text-xl font-bold text-green-600">
                {{ number_format($gameStats['completed_sessions']) }}
            </p>

        </div>


        <div class="rounded-lg bg-gray-50 p-4">

            <p class="text-xs text-gray-500">
                Questions
            </p>

            <p class="mt-1 text-xl font-bold text-gray-800">
                {{ number_format($gameStats['questions']) }}
            </p>

        </div>


        <div class="rounded-lg bg-gray-50 p-4">

            <p class="text-xs text-gray-500">
                Correct
            </p>

            <p class="mt-1 text-xl font-bold text-green-600">
                {{ number_format($gameStats['correct_answers']) }}
            </p>

        </div>


        <div class="rounded-lg bg-gray-50 p-4">

            <p class="text-xs text-gray-500">
                Accuracy
            </p>

            <p class="mt-1 text-xl font-bold text-indigo-600">
                {{ $gameStats['accuracy'] }}%
            </p>

        </div>


        <div class="rounded-lg bg-gray-50 p-4">

            <p class="text-xs text-gray-500">
                XP Earned
            </p>

            <p class="mt-1 text-xl font-bold text-gray-800">
                {{ number_format($gameStats['xp_earned']) }}
            </p>

        </div>

    </div>

</div>



{{-- ================================================================
     USER ACTIONS
================================================================= --}}

<div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">


    <div class="flex items-start justify-between">

        <div>

            <h2 class="text-lg font-semibold text-gray-800">
                User Actions
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Manage player progress, rewards, and gameplay state.
            </p>

        </div>


        <div class="rounded-lg bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
            Admin Tools
        </div>

    </div>



    <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-4">


        {{-- XP MANAGEMENT --}}

        <form
            method="POST"
            action="{{ route('admin.users.adjust-xp', $user) }}"
            class="group rounded-xl border border-indigo-100 bg-indigo-50/40 p-5 transition hover:shadow-md"
        >

            @csrf


            <div class="flex items-center justify-between">

                <div class="flex items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                        <i class="ik ik-star"></i>
                    </div>


                    <div>

                        <p class="font-semibold text-gray-800">
                            XP Points
                        </p>

                        <p class="text-xs text-gray-500">
                            Player progression
                        </p>

                    </div>

                </div>


                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-indigo-600">
                    {{ number_format($user->total_xp) }}
                </span>

            </div>



            <div class="mt-5 flex gap-2">

                <input
                    name="amount"
                    type="number"
                    placeholder="+100"
                    class="w-full rounded-lg border-gray-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >


                <button
                    type="submit"
                    class="rounded-lg bg-indigo-600 px-4 text-sm font-medium text-white transition hover:bg-indigo-700"
                >
                    Add
                </button>

            </div>

        </form>



        {{-- COINS MANAGEMENT --}}

        <form
            method="POST"
            action="{{ route('admin.users.adjust-coins', $user) }}"
            class="group rounded-xl border border-yellow-100 bg-yellow-50/40 p-5 transition hover:shadow-md"
        >

            @csrf


            <div class="flex items-center justify-between">

                <div class="flex items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-600">
                        🪙
                    </div>


                    <div>

                        <p class="font-semibold text-gray-800">
                            Coins
                        </p>

                        <p class="text-xs text-gray-500">
                            Player currency
                        </p>

                    </div>

                </div>


                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-yellow-600">
                    {{ number_format($user->total_coins) }}
                </span>

            </div>



            <div class="mt-5 flex gap-2">

                <input
                    name="amount"
                    type="number"
                    placeholder="+100"
                    class="w-full rounded-lg border-gray-200 bg-white px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500"
                >


                <button
                    type="submit"
                    class="rounded-lg bg-yellow-500 px-4 text-sm font-medium text-white hover:bg-yellow-600"
                >
                    Add
                </button>

            </div>

        </form>



        {{-- RESET STREAK --}}

        <form
            method="POST"
            action="{{ route('admin.users.reset-streak', $user) }}"
            class="rounded-xl border border-red-100 bg-red-50/40 p-5 transition hover:shadow-md"
        >

            @csrf


            <div class="flex items-center justify-between">

                <div class="flex items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600">
                        🔥
                    </div>


                    <div>

                        <p class="font-semibold text-gray-800">
                            Streak
                        </p>

                        <p class="text-xs text-gray-500">
                            Daily progress
                        </p>

                    </div>

                </div>


                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-red-600">
                    {{ $user->current_streak }}
                </span>

            </div>



            <button
                type="submit"
                class="mt-5 w-full rounded-lg bg-red-500 py-2 text-sm font-medium text-white transition hover:bg-red-600"
            >
                Reset Streak
            </button>

        </form>



        {{-- RESET LIVES --}}

        <form
            method="POST"
            action="{{ route('admin.users.reset-lives', $user) }}"
            class="rounded-xl border border-green-100 bg-green-50/40 p-5 transition hover:shadow-md"
        >

            @csrf


            <div class="flex items-center justify-between">

                <div class="flex items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100 text-green-600">
                        ❤️
                    </div>


                    <div>

                        <p class="font-semibold text-gray-800">
                            Lives
                        </p>

                        <p class="text-xs text-gray-500">
                            Gameplay attempts
                        </p>

                    </div>

                </div>


                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-green-600">
                    {{ $user->daily_lives }}
                </span>

            </div>



            <button
                type="submit"
                class="mt-5 w-full rounded-lg bg-green-600 py-2 text-sm font-medium text-white transition hover:bg-green-700"
            >
                Restore Lives
            </button>

        </form>

    </div>

</div>



{{-- ================================================================
     STREAK
================================================================= --}}

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">


    {{-- CURRENT STREAK --}}

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

        <div class="mb-5">

            <h2 class="font-semibold text-gray-800">
                Streak
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Player's current streak status.
            </p>

        </div>


        @if($user->streak)

            <div class="grid grid-cols-2 gap-4">

                <div class="rounded-lg bg-orange-50 p-4">

                    <p class="text-xs text-orange-600">
                        Current
                    </p>

                    <p class="mt-1 text-2xl font-bold text-orange-600">
                        🔥 {{ number_format($user->streak->current_streak) }}
                    </p>

                </div>


                <div class="rounded-lg bg-gray-50 p-4">

                    <p class="text-xs text-gray-500">
                        Best
                    </p>

                    <p class="mt-1 text-2xl font-bold text-gray-800">
                        {{ number_format($user->streak->best_streak) }}
                    </p>

                </div>


                <div class="rounded-lg bg-blue-50 p-4">

                    <p class="text-xs text-blue-600">
                        Freeze Shields
                    </p>

                    <p class="mt-1 text-2xl font-bold text-blue-600">
                        {{ number_format($user->streak->freeze_shields) }}
                    </p>

                </div>


                <div class="rounded-lg bg-gray-50 p-4">

                    <p class="text-xs text-gray-500">
                        Last Played
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-700">
                        {{ $user->streak->last_played_at?->format('M d, Y H:i') ?? 'Never' }}
                    </p>

                </div>

            </div>

        @else

            <div class="rounded-lg bg-gray-50 p-6 text-center">

                <p class="text-sm font-medium text-gray-600">
                    No streak record
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    This player does not have a streak record yet.
                </p>

            </div>

        @endif

    </div>



    {{-- STREAK HISTORY --}}

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

        <div class="mb-5">

            <h2 class="font-semibold text-gray-800">
                Recent Streak History
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Latest streak activity.
            </p>

        </div>


        <div class="max-h-72 space-y-2 overflow-y-auto">

            @forelse($streakHistory as $history)

                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">

                    <div>

                        <p class="text-sm font-medium text-gray-700">
                            {{ \Carbon\Carbon::parse($history->activity_date)->format('M d, Y') }}
                        </p>

                        <p class="text-xs text-gray-400">
                            {{ ucfirst($history->status) }}
                        </p>

                    </div>


                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-gray-600">
                        {{ ucfirst($history->status) }}
                    </span>

                </div>

            @empty

                <div class="py-8 text-center">

                    <p class="text-sm text-gray-500">
                        No streak history yet.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

</div>



{{-- ================================================================
     CATEGORY PERFORMANCE
================================================================= --}}

<div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

    <div class="border-b border-gray-100 p-6">

        <h2 class="font-semibold text-gray-800">
            Category Performance
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Player performance by trivia category.
        </p>

    </div>


    <div class="overflow-x-auto">

        <table class="min-w-full">

            <thead class="bg-gray-50">

                <tr>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Category
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        SR
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Answered
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Correct
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Accuracy
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Last Answered
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-gray-100">

    @forelse($user->categoryRatings as $rating)

        @php

            $answered = (int) $rating->questions_answered;

            $correct = (int) $rating->correct_answers;

            $accuracy = $answered > 0
                ? round(($correct / $answered) * 100, 1)
                : 0;

            $categoryName = $rating->category?->name;

            /*
             * Category names are stored as:
             *
             * {
             *     "en": "Science",
             *     "am": "ሳይንስ"
             * }
             */

            if (is_array($categoryName)) {

                $categoryEnglish = $categoryName['en'] ?? null;

                $categoryAmharic = $categoryName['am'] ?? null;

            } else {

                $categoryEnglish = $categoryName;

                $categoryAmharic = null;

            }

        @endphp


        <tr class="transition hover:bg-gray-50">

            {{-- CATEGORY --}}

            <td class="px-5 py-4">

                <p class="text-sm font-semibold text-gray-800">
                    {{ $categoryEnglish ?: 'Unknown Category' }}
                </p>

                @if($categoryAmharic && $categoryAmharic !== $categoryEnglish)

                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ $categoryAmharic }}
                    </p>

                @endif

            </td>


            {{-- SR --}}

            <td class="px-5 py-4">

                <span class="inline-flex rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-600">
                    {{ number_format($rating->sr, 1) }}
                </span>

            </td>


            {{-- ANSWERED --}}

            <td class="px-5 py-4 text-sm text-gray-700">
                {{ number_format($answered) }}
            </td>


            {{-- CORRECT --}}

            <td class="px-5 py-4">

                <span class="text-sm font-semibold text-green-600">
                    {{ number_format($correct) }}
                </span>

            </td>


            {{-- ACCURACY --}}

            <td class="px-5 py-4">

                <span
                    class="text-sm font-semibold
                        @if($accuracy >= 70)
                            text-green-600
                        @elseif($accuracy >= 40)
                            text-yellow-600
                        @else
                            text-red-600
                        @endif
                    "
                >
                    {{ $accuracy }}%
                </span>

            </td>


            {{-- LAST ANSWERED --}}

            <td class="px-5 py-4 text-sm text-gray-500">
                {{ $rating->last_answered_at?->format('M d, Y') ?? 'Never' }}
            </td>

        </tr>

    @empty

        <tr>

            <td colspan="6" class="px-5 py-10 text-center">

                <p class="text-sm font-medium text-gray-500">
                    No category performance data.
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    This player has not answered category-based questions yet.
                </p>

            </td>

        </tr>

    @endforelse

</tbody>

        </table>

    </div>

</div>



{{-- ================================================================
     ACCOUNT / SYSTEM INFORMATION
================================================================= --}}

<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

    <div class="mb-5">

        <h2 class="font-semibold text-gray-800">
            Account Information
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Internal account and game state information.
        </p>

    </div>


    <div class="grid grid-cols-2 gap-5 md:grid-cols-3 xl:grid-cols-5">


        <div>

            <p class="text-xs text-gray-400">
                User ID
            </p>

            <p class="mt-1 font-mono text-sm text-gray-700">
                #{{ $user->id }}
            </p>

        </div>


        <div>

            <p class="text-xs text-gray-400">
                Daily Lives
            </p>

            <p class="mt-1 text-sm font-semibold text-gray-700">
                {{ number_format($user->daily_lives) }}
            </p>

        </div>


        <div>

            <p class="text-xs text-gray-400">
                Lives Updated
            </p>

            <p class="mt-1 text-sm text-gray-700">
                {{ $user->lives_updated_at?->format('M d, Y H:i') ?? 'Never' }}
            </p>

        </div>


        <div>

            <p class="text-xs text-gray-400">
                Last Reward
            </p>

            <p class="mt-1 text-sm text-gray-700">
                {{ $user->last_reward_at?->format('M d, Y H:i') ?? 'Never' }}
            </p>

        </div>


        <div>

            <p class="text-xs text-gray-400">
                Weekly Reset
            </p>

            <p class="mt-1 text-sm text-gray-700">
                {{ $user->weekly_reset_at?->format('M d, Y H:i') ?? 'Not set' }}
            </p>

        </div>

    </div>

</div>



{{-- ================================================================
     RECENT GAMES
================================================================= --}}

<div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">


    {{-- HEADER --}}

    <div class="border-b border-gray-100 p-6">

        <h2 class="font-semibold text-gray-800">
            Recent Games
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Latest game sessions played by this user.
        </p>

    </div>



    {{-- TABLE --}}

    <div class="overflow-x-auto">

        <table class="min-w-full">


            {{-- TABLE HEADER --}}

            <thead class="bg-gray-50">

                <tr>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Date
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Questions
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Correct
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Accuracy
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        XP
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Lives Lost
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Status
                    </th>

                </tr>

            </thead>



            {{-- TABLE BODY --}}

            <tbody class="divide-y divide-gray-100">

                @forelse($recentGames as $game)

                    @php

                        $questions = (int) $game->total_questions;

                        $correct = (int) $game->correct_answers;

                        $accuracy = $questions > 0
                            ? round(($correct / $questions) * 100, 1)
                            : 0;

                    @endphp


                    {{-- =================================================
                         CLICKABLE GAME SESSION ROW
                    ================================================== --}}

                    <tr
                        class="group cursor-pointer transition hover:bg-gray-50"
                        onclick="window.location='{{ route('admin.game-sessions.show', $game) }}'"
                    >


                        {{-- DATE --}}

                        <td class="px-5 py-4">

                            <p class="text-sm font-medium text-gray-700 group-hover:text-indigo-600">
                                {{ $game->created_at?->format('M d, Y') }}
                            </p>

                            <p class="text-xs text-gray-400">
                                {{ $game->created_at?->format('h:i A') }}
                            </p>

                        </td>



                        {{-- QUESTIONS --}}

                        <td class="px-5 py-4 text-sm text-gray-700">

                            {{ number_format($questions) }}

                        </td>



                        {{-- CORRECT --}}

                        <td class="px-5 py-4">

                            <span class="text-sm font-semibold text-green-600">
                                {{ number_format($correct) }}
                            </span>

                        </td>



                        {{-- ACCURACY --}}

                        <td class="px-5 py-4">

                            <span
                                class="text-sm font-semibold
                                    @if($accuracy >= 70)
                                        text-green-600
                                    @elseif($accuracy >= 40)
                                        text-yellow-600
                                    @else
                                        text-red-600
                                    @endif
                                "
                            >
                                {{ $accuracy }}%
                            </span>

                        </td>



                        {{-- XP --}}

                        <td class="px-5 py-4">

                            <span class="text-sm font-semibold text-indigo-600">
                                +{{ number_format($game->xp_earned) }}
                            </span>

                        </td>



                        {{-- LIVES LOST --}}

                        <td class="px-5 py-4 text-sm text-gray-700">

                            {{ number_format($game->lives_lost) }}

                        </td>



                        {{-- STATUS --}}

                        <td class="px-5 py-4">

                            @if($game->is_completed)

                                <span class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-600">
                                    Completed
                                </span>

                            @else

                                <span class="rounded-full bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-600">
                                    Incomplete
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            class="px-5 py-10 text-center"
                        >

                            <p class="text-sm font-medium text-gray-500">
                                No games played yet.
                            </p>

                            <p class="mt-1 text-xs text-gray-400">
                                This player has no recorded game sessions.
                            </p>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


</div>

@endsection