@extends('admin.layouts.main')

@section('title', 'Question Details')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a
                href="{{ route('admin.questions.index') }}"
                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
            >
                <i class="ik ik-arrow-left"></i>
            </a>

            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    Question Details
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    View question content, settings, and performance.
                </p>
            </div>
        </div>

        <div class="flex gap-3">
            <a
                href="{{ route('admin.questions.edit', $question) }}"
                class="rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700"
            >
                Edit Question
            </a>

            <a
                href="{{ route('admin.questions.index') }}"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
            >
                Back
            </a>
        </div>
    </div>


    {{-- PERFORMANCE --}}
    @php
        $shown = max($question->times_shown ?? 0, 1);
        $accuracy = round((($question->times_correct ?? 0) / $shown) * 100);

        // Robust image path handling
        $imageUrl = null;
        if (!empty($question->image_url)) {
            $imageUrl = str_starts_with($question->image_url, 'http')
                ? $question->image_url
                : \Illuminate\Support\Facades\Storage::url($question->image_url);
        }
    @endphp

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-800">
                    Performance
                </h2>
                <p class="mt-1 text-xs text-gray-500">
                    Gameplay statistics and difficulty metrics.
                </p>
            </div>

            <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-600">
                Question Analytics
            </span>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card
                value="{{ number_format($question->times_shown ?? 0) }}"
                label="Times Shown"
                color="primary"
                trend="Total plays"
                variant="gradient"
            />

            <x-stat-card
                value="{{ number_format($question->times_correct ?? 0) }}"
                label="Correct Answers"
                color="green"
                trend="Successful answers"
                variant="gradient"
            />

            <x-stat-card
                value="{{ $accuracy }}%"
                label="Accuracy"
                color="accent"
                trend="Answer rate"
                variant="gradient"
            />

            <x-stat-card
                value="{{ number_format($question->difficulty_score ?? 0, 2) }}"
                label="Difficulty Score"
                color="amber"
                trend="Adaptive rating"
                variant="gradient"
            />
        </div>
    </div>

    {{-- MAIN GRID --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- LEFT COLUMN --}}
        <div class="space-y-6 xl:col-span-2">

            {{-- QUESTION CONTENT --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="mb-5">
                    <h2 class="font-semibold text-gray-800">
                        Question Content
                    </h2>
                    <p class="mt-1 text-xs text-gray-500">
                        Multilingual question text.
                    </p>
                </div>

                <div class="space-y-5">
                    <div>
                        <p class="text-xs text-gray-400">English</p>
                        <p class="mt-2 text-lg font-medium text-gray-800">
                            {{ $question->getTranslation('question_text', 'en') }}
                        </p>
                    </div>

                    <div class="border-t border-gray-100 pt-5">
                        <p class="text-xs text-gray-400">Amharic</p>
                        <p dir="auto" class="mt-2 text-lg text-gray-700">
                            {{ $question->getTranslation('question_text', 'am') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- EXPLANATION --}}
            @if($question->explanation)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-4">
                        <h2 class="font-semibold text-gray-800">
                            Explanation
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">
                            Explanation shown after answering.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-gray-400">English</p>
                            <p class="mt-1 text-sm leading-relaxed text-gray-700">
                                {{ is_array($question->explanation) ? ($question->explanation['en'] ?? '-') : $question->explanation }}
                            </p>
                        </div>

                        @if(is_array($question->explanation) && !empty($question->explanation['am']))
                            <div class="border-t border-gray-100 pt-3">
                                <p class="text-xs text-gray-400">Amharic</p>
                                <p dir="auto" class="mt-1 text-sm leading-relaxed text-gray-700">
                                    {{ $question->explanation['am'] }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ANSWERS --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="mb-5">
                    <h2 class="font-semibold text-gray-800">
                        Answer Options
                    </h2>
                    <p class="mt-1 text-xs text-gray-500">
                        Answers with translations.
                    </p>
                </div>

                <div class="space-y-4">
                    @foreach(['a', 'b', 'c', 'd'] as $option)
                        @php
                            $value = $question->{'option_'.$option};

                            if (!is_array($value)) {
                                $value = [
                                    'en' => $value,
                                    'am' => ''
                                ];
                            }

                            $isCorrect = strtolower($question->correct_answer) === $option;
                        @endphp

                        <div class="rounded-xl border p-4 {{ $isCorrect ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-white' }}">
                            <div class="flex gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg font-bold {{ $isCorrect ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600' }}">
                                    {{ strtoupper($option) }}
                                </div>

                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-semibold text-gray-700">
                                            Option {{ strtoupper($option) }}
                                        </h3>

                                        @if($isCorrect)
                                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                Correct
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                                        <div>
                                            <p class="text-xs text-gray-400">English</p>
                                            <p class="mt-1 text-sm text-gray-700">
                                                {{ $value['en'] ?? '-' }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs text-gray-400">Amharic</p>
                                            <p dir="auto" class="mt-1 text-sm text-gray-700">
                                                {{ $value['am'] ?? '-' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- IMAGE --}}
            @if($imageUrl)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">
                            Question Image
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">
                            Image attached to this question.
                        </p>
                    </div>

                    <div class="overflow-hidden rounded-xl bg-gray-50 p-4">
                        <img
                            src="{{ $imageUrl }}"
                            alt="Question image"
                            class="mx-auto max-h-96 rounded-lg object-contain"
                        >
                    </div>
                </div>
            @endif

        </div> {{-- END LEFT COLUMN --}}

        {{-- RIGHT SIDEBAR --}}
        <div class="space-y-6">

            {{-- INFORMATION --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="mb-5">
                    <h2 class="font-semibold text-gray-800">
                        Information
                    </h2>
                </div>

                <div class="space-y-5 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Category</span>
                        <span class="font-medium text-gray-800">
                            {{ $question->category?->name['en'] ?? '-' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Difficulty</span>

                        <span class="rounded-full px-3 py-1 text-xs font-semibold
                            @if($question->difficulty == 'easy')
                                bg-green-100 text-green-700
                            @elseif($question->difficulty == 'medium')
                                bg-yellow-100 text-yellow-700
                            @else
                                bg-red-100 text-red-700
                            @endif"
                        >
                            {{ ucfirst($question->difficulty ?? 'N/A') }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Status</span>

                        @if($question->is_active)
                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                Active
                            </span>
                        @else
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                Disabled
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- QUESTION ANALYTICS --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="mb-5">
                    <h2 class="font-semibold text-gray-800">
                        Analytics
                    </h2>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Difficulty Score</span>
                        <span class="font-semibold text-primary-600">
                            {{ number_format($question->difficulty_score ?? 0, 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Times Shown</span>
                        <span class="font-semibold text-gray-800">
                            {{ number_format($question->times_shown ?? 0) }}
                        </span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Correct Answers</span>
                        <span class="font-semibold text-green-600">
                            {{ number_format($question->times_correct ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- METADATA --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <div class="mb-5">
                    <h2 class="font-semibold text-gray-800">
                        Metadata
                    </h2>
                </div>

                <div class="space-y-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-400">Question ID</p>
                        <p class="mt-1 font-medium text-gray-700">
                            #{{ $question->id }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400">Created</p>
                        <p class="mt-1 font-medium text-gray-700">
                            {{ $question->created_at?->format('M d, Y') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400">Last Updated</p>
                        <p class="mt-1 font-medium text-gray-700">
                            {{ $question->updated_at?->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            </div>

        </div> {{-- END RIGHT SIDEBAR --}}

    </div> {{-- END MAIN GRID --}}

</div>

@endsection