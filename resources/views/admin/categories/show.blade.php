@extends('admin.layouts.main')

@section('title', 'Category Details')

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

    $nameData = $parseJsonField($category->name);
    $descriptionData = $parseJsonField($category->description);

    $titleEn = !empty($nameData['en']) ? $nameData['en'] : ($nameData['am'] ?? 'Category Details');
    $titleAm = !empty($nameData['am']) && !empty($nameData['en']) ? $nameData['am'] : null;

    // Helper function to resolve dynamic icon classes correctly
    $getIconClass = function ($icon) {
        if (empty($icon)) {
            return 'ik ik-grid';
        }
        
        // Check if icon is already formatted (e.g., 'ik ik-home', 'fa fa-home', 'fas fa-home')
        if (preg_match('/^(ik|fa|fas|far|fab|fal)\s+/', $icon)) {
            return $icon;
        }

        // Fallback prefixing
        return Str::startsWith($icon, 'ik-') ? 'ik ' . $icon : 'fa fa-' . $icon;
    };
@endphp

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <div
                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl text-2xl text-white shadow-sm"
                style="background-color: {{ $category->color ?? '#6366f1' }}"
            >
                <i class="{{ $getIconClass($category->icon) }}"></i>
            </div>

            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ $titleEn }}
                </h1>

                @if($titleAm)
                    <p dir="auto" class="mt-0.5 text-sm text-gray-500">
                        {{ $titleAm }}
                    </p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a
                href="{{ route('admin.categories.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50"
            >
                <i class="ik ik-arrow-left"></i>
                Back
            </a>

            <a
                href="{{ route('admin.categories.edit', $category) }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
                <i class="ik ik-edit"></i>
                Edit Category
            </a>
        </div>
    </div>

    {{-- INFO CARD --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div class="grid gap-5 sm:grid-cols-2 md:grid-cols-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                    Status
                </p>
                <div class="mt-1.5">
                    @if($category->is_active)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                            Disabled
                        </span>
                    @endif
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                    Slug
                </p>
                <p class="mt-1 text-sm font-medium text-gray-800">
                    <code class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-600">{{ $category->slug }}</code>
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                    Created Date
                </p>
                <p class="mt-1 text-sm font-medium text-gray-800">
                    {{ $category->created_at ? $category->created_at->format('M d, Y') : 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                    Sort Order
                </p>
                <p class="mt-1 text-sm font-medium text-gray-800">
                    {{ $category->sort_order }}
                </p>
            </div>
        </div>
    </div>

    {{-- STATISTICS --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
        @php
            $cards = [
                [
                    'title' => 'Questions',
                    'value' => $stats['total_questions'] ?? 0,
                    'icon'  => 'ik-help-circle',
                ],
                [
                    'title' => 'Active',
                    'value' => $stats['active_questions'] ?? 0,
                    'icon'  => 'ik-check-circle',
                ],
                [
                    'title' => 'Easy',
                    'value' => $stats['easy_questions'] ?? 0,
                    'icon'  => 'ik-smile',
                ],
                [
                    'title' => 'Medium',
                    'value' => $stats['medium_questions'] ?? 0,
                    'icon'  => 'ik-alert-circle',
                ],
                [
                    'title' => 'Hard',
                    'value' => $stats['hard_questions'] ?? 0,
                    'icon'  => 'ik-trending-up',
                ],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">
                            {{ $card['title'] }}
                        </p>
                        <p class="mt-2 text-3xl font-bold text-gray-800">
                            {{ number_format($card['value']) }}
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                        <i class="{{ $getIconClass($card['icon']) }} text-xl"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- DESCRIPTION --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <h2 class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-700">
            Description
        </h2>

        <div class="space-y-3">
            <div>
                <p class="text-xs font-medium text-gray-400">English</p>
                <p class="mt-1 text-sm text-gray-700 leading-relaxed">
                    {{ !empty($descriptionData['en']) ? $descriptionData['en'] : 'No description available.' }}
                </p>
            </div>

            @if(!empty($descriptionData['am']))
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs font-medium text-gray-400">Amharic</p>
                    <p dir="auto" class="mt-1 text-sm text-gray-700 leading-relaxed">
                        {{ $descriptionData['am'] }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- RECENT QUESTIONS --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                Recent Questions
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-100 bg-gray-50/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Question
                        </th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Difficulty
                        </th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($questions as $question)
                        @php
                            $rawQuestion = $question->question_text ?? $question->question ?? '';
                            $qData = $parseJsonField($rawQuestion);

                            $qTextEn = !empty($qData['en']) ? $qData['en'] : 'No question text';
                            $qTextAm = !empty($qData['am']) ? $qData['am'] : null;

                            $diff = strtolower($question->difficulty ?? 'easy');

                            $badgeStyle = match($diff) {
                                'easy'   => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                'medium' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                'hard'   => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                default  => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                            };
                        @endphp

                        <tr class="transition-colors hover:bg-gray-50/50">
                            <td class="px-6 py-4 font-medium text-gray-800">
                                <a
                                    href="{{ route('admin.questions.show', $question->id) }}"
                                    class="block text-primary-600 hover:text-primary-700 hover:underline"
                                >
                                    {{ Str::limit($qTextEn, 80) }}
                                </a>

                                @if($qTextAm)
                                    <p dir="auto" class="mt-1 text-xs text-gray-500 font-normal">
                                        {{ Str::limit($qTextAm, 80) }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeStyle }}">
                                    {{ ucfirst($diff) }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                @if($question->is_active)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                        Disabled
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500">
                                <i class="ik ik-inbox mb-2 block text-3xl text-gray-300"></i>
                                No questions found for this category.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($questions->hasPages())
                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $questions->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection