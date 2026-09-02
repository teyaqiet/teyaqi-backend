@extends('admin.layouts.main')

@section('title', 'Topic Details')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a
                href="{{ route('admin.topics.index') }}"
                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm transition-colors hover:bg-gray-50"
            >
                <i class="ik ik-arrow-left"></i>
            </a>

            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ is_array($topic->name) ? ($topic->name['en'] ?? reset($topic->name)) : $topic->name }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Topic details and question overview.
                </p>
            </div>
        </div>

        <a
            href="{{ route('admin.topics.edit', $topic) }}"
            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-700"
        >
            <i class="ik ik-edit-2"></i>
            Edit Topic
        </a>
    </div>

    {{-- TOPIC INFO --}}
    <div class="grid gap-5 md:grid-cols-3">
        {{-- CATEGORY --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">
                Category
            </p>
            <p class="mt-2 text-lg font-semibold text-gray-800">
                {{ $topic->category?->name['en'] ?? $topic->category?->name ?? '-' }}
            </p>
        </div>

        {{-- QUESTIONS COUNT --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">
                Questions
            </p>
            <p class="mt-2 text-lg font-semibold text-gray-800">
                {{ number_format($questions->total()) }}
            </p>
        </div>

        {{-- SLUG --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">
                Slug
            </p>
            <p class="mt-2 font-mono text-base font-semibold text-gray-800">
                {{ $topic->slug }}
            </p>
        </div>
    </div>

    {{-- QUESTIONS TABLE --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-800">
                Questions
            </h2>
            <p class="mt-1 text-xs text-gray-500">
                Questions currently assigned to this topic.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-gray-100 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Question
                        </th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Difficulty
                        </th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                @forelse($questions as $question)
                    <tr class="transition-colors hover:bg-gray-50/50">
                        <td class="px-5 py-4 text-sm text-gray-700">
                            {{ Str::limit(
                                method_exists($question, 'getTranslation') 
                                    ? $question->getTranslation('question_text', 'en') 
                                    : ($question->question_text['en'] ?? $question->question_text),
                                80
                            ) }}
                        </td>

                        <td class="px-5 py-4">
                            @php
                                $badgeStyle = match(strtolower($question->difficulty ?? '')) {
                                    'easy' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'medium' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                    'hard' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                    default => 'bg-gray-100 text-gray-700 ring-gray-500/20',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeStyle }}">
                                {{ ucfirst($question->difficulty ?? 'N/A') }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            @if($question->is_active)
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-300"></span>
                                    Disabled
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-12 text-center">
                            <i class="ik ik-help-circle text-4xl text-gray-300"></i>
                            <p class="mt-2 text-sm font-medium text-gray-800">
                                No questions assigned yet
                            </p>
                            <p class="text-xs text-gray-500">
                                Questions added to this topic will appear here.
                            </p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION FOOTER --}}
        @if($questions->total() > 0)
            <div class="flex flex-col gap-4 border-t border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900">{{ $questions->firstItem() ?? 0 }}</span>
                    to <span class="font-semibold text-gray-900">{{ $questions->lastItem() ?? 0 }}</span>
                    of <span class="font-semibold text-gray-900">{{ number_format($questions->total()) }}</span> results
                </p>

                @if($questions->hasPages())
                    <div>
                        {{ $questions->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>

</div>

@endsection