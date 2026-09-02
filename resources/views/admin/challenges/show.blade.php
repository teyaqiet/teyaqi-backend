@extends('admin.layouts.main')

@section('title', 'Challenge Details')

@section('content')
@php
    // Helper to safely parse normal, JSON string, or double-encoded JSON attributes
    $parseJsonField = function ($field) {
        if (is_array($field)) {
            return $field;
        }

        if (is_string($field)) {
            $decoded = json_decode($field, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : ['en' => $field];
        }

        return [];
    };

    // Category Name Parsing
    $catNameData = $challenge->category ? $parseJsonField($challenge->category->name) : [];
    $catNameEn = !empty($catNameData['en']) ? $catNameData['en'] : ($catNameData['am'] ?? null);

    // Topic Name Parsing
    $topicNameData = $challenge->topic ? $parseJsonField($challenge->topic->name) : [];
    $topicNameEn = !empty($topicNameData['en']) ? $topicNameData['en'] : ($topicNameData['am'] ?? null);

    // Bilingual Description Parsing
    $descData = $parseJsonField($challenge->description);
    $descEn = !empty($descData['en']) ? $descData['en'] : (is_string($challenge->description) ? $challenge->description : null);
    $descAm = !empty($descData['am']) ? $descData['am'] : null;

    // Status string resolution
    $statusStr = strtolower(is_object($challenge->status) ? ($challenge->status->value ?? '') : (string) $challenge->status);

    // Difficulty badge color scheme
    $diffStr = strtolower($challenge->difficulty ?? 'medium');
    $diffBadge = match($diffStr) {
        'easy'   => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'medium' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'hard'   => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        default  => 'bg-gray-50 text-gray-700 ring-gray-600/20',
    };

    // Safe stats resolution (prefers $stats array passed from controller, falls back to model)
    $totalAttempts  = $stats['total_attempts'] ?? $challenge->total_attempts ?? 0;
    $uniquePlayers  = $stats['unique_players'] ?? $challenge->unique_players ?? 0;
    $passRate       = $stats['pass_rate'] ?? $challenge->pass_rate ?? 0;
    $highScore      = $stats['high_score'] ?? $challenge->high_score ?? 0;
    $avgScore       = $stats['avg_score'] ?? $challenge->avg_score ?? 0;
@endphp

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ $challenge->title }}
                </h1>

                @if($statusStr === 'active')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                        Active
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                        {{ ucfirst($statusStr ?: 'Draft') }}
                    </span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500">
                View challenge configurations, performance analytics, reward structures, and assigned questions.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a
                href="{{ route('admin.challenges.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50"
            >
                <i class="ik ik-arrow-left"></i>
                Back
            </a>

            <a
                href="{{ route('admin.challenges.edit', $challenge) }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
                <i class="ik ik-edit"></i>
                Edit Challenge
            </a>
        </div>
    </div>

    {{-- REWARDS & OVERVIEW STAT CARDS --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Reward XP</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600 font-mono">
                        {{ number_format($challenge->reward_xp ?? 0) }} <span class="text-xs font-normal text-gray-400">XP</span>
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i class="ik ik-award text-xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Reward Coins</p>
                    <p class="mt-2 text-2xl font-bold text-amber-500 font-mono">
                        {{ number_format($challenge->reward_coins ?? 0) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-500">
                    <i class="ik ik-dollar-sign text-xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Question Count</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 font-mono">
                        {{ $challenge->question_count ?? 0 }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                    <i class="ik ik-help-circle text-xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Passing Score</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 font-mono">
                        {{ $challenge->passing_score ? $challenge->passing_score . '%' : '-' }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <i class="ik ik-target text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- PERFORMANCE & ANALYTICS METRICS --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-700">
            Performance & Analytics
        </h2>

        <div class="grid gap-5 sm:grid-cols-2 md:grid-cols-4">
            <div class="rounded-lg bg-gray-50/70 p-4 border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                        <i class="ik ik-play-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Total Attempts</p>
                        <p class="text-xl font-bold font-mono text-gray-800">
                            {{ number_format($totalAttempts) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-gray-50/70 p-4 border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <i class="ik ik-users text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Unique Players</p>
                        <p class="text-xl font-bold font-mono text-gray-800">
                            {{ number_format($uniquePlayers) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-gray-50/70 p-4 border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                        <i class="ik ik-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Pass Rate</p>
                        <p class="text-xl font-bold font-mono text-gray-800">
                            {{ $passRate }}%
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-gray-50/70 p-4 border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                        <i class="ik ik-trending-up text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">High Score (Avg: {{ $avgScore }})</p>
                        <p class="text-xl font-bold font-mono text-gray-800">
                            {{ number_format($highScore) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN INFO CARD --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-700">
            Basic Information
        </h2>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Title</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ $challenge->title }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Category & Topic</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        @if($catNameEn)
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                <i class="ik ik-folder text-xs text-gray-400"></i>
                                {{ $catNameEn }}
                            </span>
                        @else
                            <span class="text-sm text-gray-400">—</span>
                        @endif

                        @if($topicNameEn)
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                <i class="ik ik-hash text-xs text-blue-400"></i>
                                {{ $topicNameEn }}
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Type</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">
                        {{ ucfirst(is_object($challenge->type) ? ($challenge->type->value ?? 'Standard') : ($challenge->type ?: 'Standard')) }}
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Difficulty</p>
                    <div class="mt-1">
                        <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $diffBadge }}">
                            {{ ucfirst($diffStr) }}
                        </span>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Description</p>
                    <div class="mt-1 space-y-1.5">
                        @if($descEn)
                            <p class="text-sm text-gray-700 leading-relaxed">
                                {{ $descEn }}
                            </p>
                        @endif

                        @if($descAm)
                            <p dir="auto" class="text-xs text-gray-500 leading-relaxed font-normal">
                                {{ $descAm }}
                            </p>
                        @endif

                        @if(!$descEn && !$descAm)
                            <p class="text-sm text-gray-400 italic">No description provided.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CHALLENGE SETTINGS --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-700">
            Challenge Settings
        </h2>

        <div class="grid gap-5 sm:grid-cols-2 md:grid-cols-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Time Mode</p>
                <p class="mt-1 text-sm font-medium text-gray-800">
                    {{ ucfirst(is_object($challenge->time_mode) ? ($challenge->time_mode->value ?? 'Default') : ($challenge->time_mode ?: 'Default')) }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Time Limit</p>
                <p class="mt-1 text-sm font-medium text-gray-800 font-mono">
                    {{ $challenge->time_limit ? $challenge->time_limit . ' sec' : 'No Limit' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Daily Challenge</p>
                <p class="mt-1 text-sm font-medium text-gray-800">
                    @if($challenge->is_daily)
                        <span class="text-emerald-600 font-semibold">Yes</span>
                    @else
                        <span class="text-gray-400">No</span>
                    @endif
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Ranked</p>
                <p class="mt-1 text-sm font-medium text-gray-800">
                    @if($challenge->is_ranked)
                        <span class="text-emerald-600 font-semibold">Yes</span>
                    @else
                        <span class="text-gray-400">No</span>
                    @endif
                </p>
            </div>
        </div>
    </div>


    {{-- QUESTION RULES --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                Question Rules
            </h2>
        </div>

        <div class="overflow-x-auto">
            @if($challenge->rules && $challenge->rules->count())
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50/50">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Selection
                            </th>
                            <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Difficulty
                            </th>
                            <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Count
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($challenge->rules as $rule)
                            @php
                                $selType = is_object($rule->selection_type) ? ($rule->selection_type->value ?? '') : (string) $rule->selection_type;
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50/50">
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    {{ ucfirst($selType ?: 'Random') }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ ucfirst($rule->difficulty ?? 'Any') }}
                                </td>
                                <td class="px-6 py-4 font-mono font-semibold text-gray-800">
                                    {{ $rule->question_count }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-6 py-8 text-center text-sm text-gray-500">
                    <i class="ik ik-sliders mb-1 block text-2xl text-gray-300"></i>
                    No question rules configured for this challenge.
                </div>
            @endif
        </div>
    </div>

    {{-- MANUAL QUESTIONS --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                Manual Questions
            </h2>
        </div>

        <div class="overflow-x-auto">
            @if($challenge->manualQuestions && $challenge->manualQuestions->count())
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50/50">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500 w-12">
                                #
                            </th>
                            <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Question
                            </th>
                            <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Difficulty
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($challenge->manualQuestions as $question)
                            @php
                                $qRaw = $question->question_text ?? $question->question ?? '';
                                $qData = $parseJsonField($qRaw);
                                $qTextEn = !empty($qData['en']) ? $qData['en'] : ($qData['am'] ?? 'Question Text');
                                $qTextAm = !empty($qData['am']) && !empty($qData['en']) ? $qData['am'] : null;

                                $mDiff = strtolower($question->difficulty ?? 'easy');
                                $mBadge = match($mDiff) {
                                    'easy'   => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'medium' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                    'hard'   => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                    default  => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                };
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50/50">
                                <td class="px-6 py-4 font-mono text-xs text-gray-400">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    <a href="{{ route('admin.questions.show', $question->id) }}" class="text-primary-600 hover:underline">
                                        {{ Str::limit($qTextEn, 80) }}
                                    </a>
                                    @if($qTextAm)
                                        <p dir="auto" class="mt-0.5 text-xs text-gray-500 font-normal">
                                            {{ Str::limit($qTextAm, 80) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $mBadge }}">
                                        {{ ucfirst($mDiff) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-6 py-8 text-center text-sm text-gray-500">
                    <i class="ik ik-file-text mb-1 block text-2xl text-gray-300"></i>
                    No manual questions attached.
                </div>
            @endif
        </div>
    </div>

{{-- RECENT ATTEMPTS TABLE --}}
@if(isset($recentAttempts))
<div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
    <div class="border-b border-gray-100 px-6 py-4 flex justify-between items-center">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
            Recent Attempts
        </h2>
        <span class="text-xs text-gray-400 font-mono">
            Showing {{ $recentAttempts->count() }} of {{ $recentAttempts->total() }}
        </span>
    </div>

    <div class="overflow-x-auto">
        @if($recentAttempts->count())
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-100 bg-gray-50/50">
                    <tr>
                        <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Player
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Score
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Correct / Wrong
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Date
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recentAttempts as $attempt)
                        <tr class="transition-colors hover:bg-gray-50/50">
                            <td class="px-6 py-4 font-medium text-gray-800">
                                @if($attempt->user)
                                    <a href="{{ route('admin.users.show', $attempt->user->id) }}" 
                                       class="text-indigo-600 hover:text-indigo-900 hover:underline inline-flex items-center gap-1 font-semibold transition-colors">
                                        {{ $attempt->user->name ?? $attempt->user->email }}
                                    </a>
                                @else
                                    <span class="text-gray-400 italic">Anonymous Player</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono font-semibold text-gray-800">
                                {{ $attempt->score ?? 0 }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                <span class="text-emerald-600 font-semibold">{{ $attempt->correct_answers ?? 0 }}</span>
                                <span class="text-gray-300">/</span>
                                <span class="text-rose-500 font-semibold">{{ $attempt->wrong_answers ?? 0 }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($attempt->passed)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                        Passed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                        Failed
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                {{ $attempt->finished_at ? $attempt->finished_at->diffForHumans() : ($attempt->started_at ? $attempt->started_at->diffForHumans() : '-') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($recentAttempts->hasPages())
                <div class="px-6 py-3 border-t border-gray-100">
                    {{ $recentAttempts->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-8 text-center text-sm text-gray-500">
                <i class="ik ik-users mb-1 block text-2xl text-gray-300"></i>
                No player attempts recorded yet.
            </div>
        @endif
    </div>
</div>
@endif
    

</div>
@endsection