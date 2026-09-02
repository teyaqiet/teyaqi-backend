@extends('admin.layouts.main')

@section('title', 'Game Session #' . $gameSession->id)

@section('content')

@php
$responses = $gameSession->quizResponses;
$totalTime = $responses->sum('time_taken_ms');
$responseCount = $responses->count();
$averageTime = $responseCount ? $totalTime / $responseCount : 0;
$fastest = $responses->min('time_taken_ms');
$slowest = $responses->max('time_taken_ms');

// Category Accuracy Aggregation

$categoryStats = [];
foreach ($responses as $resp) {
    $rawCatName = $resp->question?->category?->name;

    if (is_array($rawCatName)) {
        $catName = $rawCatName['en'] ?? reset($rawCatName) ?? 'Uncategorized';
    } else {
        $catName = $rawCatName ?? 'Uncategorized';
    }

    if (!isset($categoryStats[$catName])) {
        $categoryStats[$catName] = ['total' => 0, 'correct' => 0];
    }
    $categoryStats[$catName]['total']++;
    if ($resp->is_correct) {
        $categoryStats[$catName]['correct']++;
    }
}

function readableTime($milliseconds)
{
    $seconds = round($milliseconds / 1000);
    if ($seconds < 60) return $seconds . 's';
    $minutes = floor($seconds / 60);
    $rem = $seconds % 60;
    return $rem === 0 ? $minutes . 'm' : $minutes . 'm ' . $rem . 's';
}
@endphp

<div class="space-y-6">

    {{-- Header & Stepper --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-800">Game Session #{{ $gameSession->id }}</h1>
                @if($gameSession->is_completed)
                    <span class="rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20">Completed</span>
                @else
                    <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-600/20">Abandoned</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500">Review player answers, speed breakdown, and category performance.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Prev/Next Navigation --}}
            <div class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5 shadow-sm">
                @if($previousSessionId = \App\Models\GameSession::where('id', '<', $gameSession->id)->max('id'))
                    <a href="{{ route('admin.game-sessions.show', $previousSessionId) }}" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-indigo-600" title="Previous Session">← Prev</a>
                @else
                    <span class="px-3 py-1.5 text-xs font-medium text-gray-300 cursor-not-allowed">← Prev</span>
                @endif

                <div class="w-px bg-gray-200"></div>

                @if($nextSessionId = \App\Models\GameSession::where('id', '>', $gameSession->id)->min('id'))
                    <a href="{{ route('admin.game-sessions.show', $nextSessionId) }}" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-indigo-600" title="Next Session">Next →</a>
                @else
                    <span class="px-3 py-1.5 text-xs font-medium text-gray-300 cursor-not-allowed">Next →</span>
                @endif
            </div>

            <a href="{{ route('admin.users.show', $gameSession->user) }}" class="rounded-lg border border-gray-200 bg-white px-3.5 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                View Player
            </a>

            <form method="POST" action="{{ route('admin.game-sessions.destroy', $gameSession) }}" onsubmit="return confirm('Delete this session?')">
                @csrf
                @method('DELETE')
                <button class="rounded-lg bg-red-600 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-500">
                    Delete
                </button>
            </form>
        </div>
    </div>

    {{-- Overview Stats --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Total Questions</p>
            <p class="mt-2 text-2xl font-bold text-gray-800">{{ $gameSession->total_questions }}</p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Accuracy Rate</p>
            <p class="mt-2 text-2xl font-bold text-indigo-600">
                {{ number_format(($gameSession->correct_answers / max($gameSession->total_questions, 1)) * 100, 1) }}%
            </p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Total XP Earned</p>
            <p class="mt-2 text-2xl font-bold text-yellow-500">+{{ number_format($gameSession->xp_earned) }}</p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Pace / Question</p>
            <p class="mt-2 text-2xl font-bold text-purple-600">{{ readableTime($averageTime) }}</p>
        </div>
    </div>

    {{-- Player Info & Category Accuracy --}}
    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100 md:col-span-2">
            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400">Player Metadata</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-4">
                <div>
                    <p class="text-xs text-gray-400">Name</p>
                    <p class="font-semibold text-gray-800">{{ $gameSession->user?->name ?? 'Unknown' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Username</p>
                    <p class="font-semibold text-gray-800">{{ $gameSession->user?->username ? '@'.$gameSession->user->username : 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Lives Lost</p>
                    <p class="font-semibold text-red-500">{{ $gameSession->lives_lost }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Played At</p>
                    <p class="font-semibold text-gray-800">{{ $gameSession->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400">Category Performance</h2>
            <div class="mt-3 space-y-2.5">
                @forelse($categoryStats as $catName => $stat)
                    @php $pct = round(($stat['correct'] / $stat['total']) * 100); @endphp
                    <div>
                        <div class="flex justify-between text-xs">
                            <span class="font-medium text-gray-700">{{ $catName }}</span>
                            <span class="font-bold text-gray-600">{{ $stat['correct'] }}/{{ $stat['total'] }} ({{ $pct }}%)</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $pct >= 70 ? 'bg-green-500' : ($pct >= 40 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No categories found.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Main Columns --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 items-start">

        {{-- Question Responses --}}
        <div class="lg:col-span-2 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
            <div class="border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-gray-800">Question Responses</h2>
                    <p class="mt-0.5 text-xs text-gray-400">Detailed answer breakdown and telemetry.</p>
                </div>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse($responses as $index => $response)
                    @php
                        $isFast = $response->time_taken_ms < 2000;
                        $isSlow = $averageTime > 0 && $response->time_taken_ms > ($averageTime * 2);
                    @endphp

                    <div id="response-{{ $index + 1 }}" class="p-6 transition hover:bg-gray-50/50">
                        {{-- Header & Badges --}}
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600">
                                    {{ $index + 1 }}
                                </span>
                                <span class="font-semibold text-gray-800">Question {{ $index + 1 }}</span>
                               @if($response->question?->category)
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600">
                                        {{ is_array($response->question->category->name) 
                                            ? ($response->question->category->name['en'] ?? reset($response->question->category->name)) 
                                            : $response->question->category->name }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if($isFast)
                                    <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20" title="Answered in less than 2 seconds">
                                        ⚠️ Rapid Guess
                                    </span>
                                @elseif($isSlow)
                                    <span class="rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20" title="Took more than 2x the session average">
                                        🤔 Slow Pace
                                    </span>
                                @endif

                                @if($response->is_correct)
                                    <span class="rounded-md bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Correct</span>
                                @else
                                    <span class="rounded-md bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">Incorrect</span>
                                @endif
                            </div>
                        </div>

                        {{-- Question Prompt --}}
                        <div class="mt-4 rounded-lg bg-gray-50 p-4">
                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Prompt</p>
                            @php $qText = $response->question?->question_text; @endphp
                            @if(is_array($qText))
                                @foreach($qText as $lang => $text)
                                    <p class="text-sm text-gray-800">
                                        <span class="font-semibold text-gray-500">{{ strtoupper($lang) }}:</span> {{ $text }}
                                    </p>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-800">{{ $qText ?? 'Question unavailable' }}</p>
                            @endif
                        </div>

                        {{-- Options Grid --}}
                        <div class="mt-4">
                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Options</p>
                            <div class="grid gap-3 md:grid-cols-2">
                                @php
                                    $options = $response->question?->options ?? [];
                                    $correctAnswer = strtolower($response->question?->correct_answer ?? '');
                                    $selectedAnswer = strtolower($response->selected_option ?? '');
                                @endphp

                                @foreach($options as $key => $option)
                                    @php
                                        $keyLower = strtolower($key);
                                        $isSelected = $keyLower === $selectedAnswer;
                                        $isCorrect = $keyLower === $correctAnswer;
                                    @endphp

                                    <div class="rounded-lg border p-3.5 text-sm transition {{ $isCorrect ? 'border-green-300 bg-green-50/60' : ($isSelected ? 'border-indigo-300 bg-indigo-50/60' : 'border-gray-200 bg-white') }}">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-gray-700">{{ strtoupper($key) }}</span>
                                            <div class="flex gap-1.5">
                                                @if($isCorrect)
                                                    <span class="rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-700">Correct</span>
                                                @endif
                                                @if($isSelected)
                                                    <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-medium text-indigo-700">Chosen</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mt-1.5 text-gray-700">
                                            @if(is_array($option))
                                                @foreach($option as $lang => $val)
                                                    <p class="text-xs">
                                                        <span class="font-semibold text-gray-500">{{ strtoupper($lang) }}:</span> {{ $val }}
                                                    </p>
                                                @endforeach
                                            @else
                                                {{ $option }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Explanation Box --}}
                        @if($response->question?->explanation)
                            <div class="mt-4 rounded-lg bg-blue-50/70 p-3.5 text-xs text-blue-900 ring-1 ring-inset ring-blue-700/10">
                                <p class="font-bold uppercase tracking-wider text-blue-700 text-[10px] mb-1">Explanation</p>
                                @php $exp = $response->question->explanation; @endphp
                                @if(is_array($exp))
                                    @foreach($exp as $lang => $text)
                                        <p><span class="font-semibold">{{ strtoupper($lang) }}:</span> {{ $text }}</p>
                                    @endforeach
                                @else
                                    <p>{{ $exp }}</p>
                                @endif
                            </div>
                        @endif

                        {{-- Footer Meta --}}
                        <div class="mt-4 flex flex-wrap items-center justify-between text-xs text-gray-500 border-t border-gray-100 pt-3">
                            <div class="flex gap-3 items-center">
                                <div>Selected: <span class="font-bold text-gray-800">{{ strtoupper($response->selected_option) }}</span></div>
                                <div>•</div>
                                <div>Duration: <span class="font-bold text-indigo-600">{{ readableTime($response->time_taken_ms) }}</span> ({{ number_format($response->time_taken_ms) }} ms)</div>
                            </div>

                            @if($response->question_id && Route::has('admin.questions.edit'))
                                <a href="{{ route('admin.questions.edit', $response->question_id) }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                    ✏️ Edit Question
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm font-medium text-gray-600">No responses recorded.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right Sidebar: Speed Chart + Timeline --}}
        <div class="space-y-6 lg:col-span-1">

            {{-- Chart Widget --}}
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                <h2 class="font-semibold text-gray-800 text-sm">Response Time Graph</h2>
                <p class="text-xs text-gray-400 mt-0.5">Time spent per question in seconds.</p>
                <div class="mt-4">
                    <canvas id="speedChart" class="max-h-48 w-full"></canvas>
                </div>
            </div>

            {{-- Activity Timeline --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h2 class="font-semibold text-gray-800">Session Timeline</h2>
                <p class="mt-0.5 text-xs text-gray-400">Chronological answer flow.</p>

                <div class="relative mt-6 space-y-6 pl-4 before:absolute before:left-2 before:top-2 before:h-[calc(100%-16px)] before:w-0.5 before:bg-gray-200">
                    @forelse($responses as $index => $response)
                        @php
                            $isFastest = $response->time_taken_ms === $fastest;
                            $isSlowest = $response->time_taken_ms === $slowest;
                        @endphp
                        <div class="relative flex items-start gap-3">
                            <div class="absolute -left-4 top-1 h-2.5 w-2.5 rounded-full ring-4 ring-white {{ $response->is_correct ? 'bg-green-500' : 'bg-red-500' }}"></div>

                            <div class="flex-1">
                                <div class="flex items-center justify-between text-xs">
                                    <a href="#response-{{ $index + 1 }}" class="font-semibold text-gray-800 hover:text-indigo-600 hover:underline">
                                        Q{{ $index + 1 }} Response
                                    </a>
                                    <span class="text-[11px] font-bold {{ $response->is_correct ? 'text-green-600' : 'text-red-500' }}">
                                        {{ readableTime($response->time_taken_ms) }}
                                    </span>
                                </div>

                                <p class="mt-1 text-xs text-gray-500">
                                    Chose option <span class="font-bold text-gray-700">{{ strtoupper($response->selected_option) }}</span>
                                </p>

                                @if($isFastest || $isSlowest)
                                    <div class="mt-1 flex gap-1">
                                        @if($isFastest)
                                            <span class="rounded bg-green-50 px-1.5 py-0.5 text-[10px] font-semibold text-green-700 ring-1 ring-inset ring-green-600/20">⚡ Fastest</span>
                                        @endif
                                        @if($isSlowest)
                                            <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">🐢 Slowest</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">No timeline entries.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>

{{-- Chart.js Integration --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('speedChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($responses->map(fn($r, $i) => 'Q' . ($i + 1))) !!},
                datasets: [{
                    label: 'Seconds',
                    data: {!! json_encode($responses->map(fn($r) => round($r->time_taken_ms / 1000, 1))) !!},
                    backgroundColor: {!! json_encode($responses->map(fn($r) => $r->is_correct ? '#22c55e' : '#ef4444')) !!},
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.raw}s`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 10 } }
                    },
                    x: {
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    });
</script>

@endsection