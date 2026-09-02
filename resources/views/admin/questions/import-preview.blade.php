@extends('admin.layouts.main')

@section('title', 'Preview Question Import')

@section('content')

<div class="space-y-6">


{{-- ============================================================
    HEADER
============================================================= --}}
<div class="flex items-center gap-4">

    <a
        href="{{ route('admin.questions.import') }}"
        class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-700"
    >
        <i class="ik ik-arrow-left"></i>
    </a>

    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            Preview Import
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Review your questions and fix any issues before importing.
        </p>
    </div>

</div>


{{-- ============================================================
    SUMMARY
============================================================= --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-4">

    {{-- TOTAL --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <p class="text-xs font-medium text-gray-500">
            Total Rows
        </p>

        <p class="mt-2 text-2xl font-bold text-gray-800">
            {{ $totalRows }}
        </p>
    </div>


    {{-- VALID --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <p class="text-xs font-medium text-gray-500">
            Ready to Import
        </p>

        <p class="mt-2 text-2xl font-bold text-emerald-600">
            {{ count($validRows) }}
        </p>
    </div>


    {{-- ERRORS --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <p class="text-xs font-medium text-gray-500">
            Errors
        </p>

        <p class="mt-2 text-2xl font-bold text-rose-600">
            {{ count($errors) }}
        </p>
    </div>


    {{-- WARNINGS --}}
    @php
        $warningRows = [];

        foreach ($validRows as $row) {

            $warnings = [];

            /*
             * ----------------------------------------------------
             * QUESTION TRANSLATIONS
             * ----------------------------------------------------
             */

            $questionEn = trim((string) ($row['question_text']['en'] ?? ''));
            $questionAm = trim((string) ($row['question_text']['am'] ?? ''));

            if ($questionEn === '') {
                $warnings[] = 'English question is missing.';
            }

            if ($questionAm === '') {
                $warnings[] = 'Amharic question translation is missing.';
            }


            /*
             * ----------------------------------------------------
             * OPTIONS
             * ----------------------------------------------------
             */

            $options = [
                'A' => $row['option_a'] ?? [],
                'B' => $row['option_b'] ?? [],
                'C' => $row['option_c'] ?? [],
                'D' => $row['option_d'] ?? [],
            ];


            $englishOptions = [];
            $amharicOptions = [];

            foreach ($options as $letter => $option) {

                $en = trim((string) ($option['en'] ?? ''));
                $am = trim((string) ($option['am'] ?? ''));

                /*
                 * Empty option.
                 */
                if ($en === '') {
                    $warnings[] = "Option {$letter} English text is missing.";
                }

                /*
                 * Missing Amharic translation.
                 */
                if ($am === '') {
                    $warnings[] = "Option {$letter} Amharic translation is missing.";
                }

                /*
                 * Store normalized values for duplicate detection.
                 */
                if ($en !== '') {
                    $englishOptions[$letter] = mb_strtolower(
                        preg_replace('/\s+/', ' ', $en)
                    );
                }

                if ($am !== '') {
                    $amharicOptions[$letter] = mb_strtolower(
                        preg_replace('/\s+/', ' ', $am)
                    );
                }
            }


            /*
             * ----------------------------------------------------
             * DUPLICATE ENGLISH OPTIONS
             * ----------------------------------------------------
             */

            $seenEnglish = [];

            foreach ($englishOptions as $letter => $text) {

                if (isset($seenEnglish[$text])) {

                    $warnings[] =
                        "Option {$letter} has the same English text as option {$seenEnglish[$text]}.";

                } else {

                    $seenEnglish[$text] = $letter;

                }
            }


            /*
             * ----------------------------------------------------
             * DUPLICATE AMHARIC OPTIONS
             * ----------------------------------------------------
             */

            $seenAmharic = [];

            foreach ($amharicOptions as $letter => $text) {

                if (isset($seenAmharic[$text])) {

                    $warnings[] =
                        "Option {$letter} has the same Amharic text as option {$seenAmharic[$text]}.";

                } else {

                    $seenAmharic[$text] = $letter;

                }
            }


            /*
             * ----------------------------------------------------
             * DUPLICATE QUESTION TEXT INSIDE IMPORT FILE
             * ----------------------------------------------------
             *
             * We compare the current question against every
             * other valid row.
             */

            if ($questionEn !== '') {

                $normalizedQuestion =
                    mb_strtolower(
                        preg_replace(
                            '/\s+/',
                            ' ',
                            $questionEn
                        )
                    );

                foreach ($validRows as $otherRow) {

                    if (($otherRow['row'] ?? null) === ($row['row'] ?? null)) {
                        continue;
                    }

                    $otherQuestion =
                        trim(
                            (string) (
                                $otherRow['question_text']['en']
                                ?? ''
                            )
                        );

                    if ($otherQuestion === '') {
                        continue;
                    }

                    $normalizedOtherQuestion =
                        mb_strtolower(
                            preg_replace(
                                '/\s+/',
                                ' ',
                                $otherQuestion
                            )
                        );

                    if ($normalizedQuestion === $normalizedOtherQuestion) {

                        $warnings[] =
                            "This question also appears in row {$otherRow['row']}.";

                        break;
                    }
                }
            }


            /*
             * ----------------------------------------------------
             * STORE WARNINGS
             * ----------------------------------------------------
             */

            if (!empty($warnings)) {

                $warningRows[] = [
                    'row' => $row['row'],
                    'warnings' => array_values(
                        array_unique($warnings)
                    ),
                    'question' => $questionEn,
                ];
            }
        }
    @endphp


    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <p class="text-xs font-medium text-gray-500">
            Warnings
        </p>

        <p class="mt-2 text-2xl font-bold text-amber-500">
            {{ count($warningRows) }}
        </p>
    </div>

</div>


{{-- ============================================================
    IMPORT STATUS BANNER
============================================================= --}}
@if(count($errors) > 0)

    <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-5">

        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
            <i class="ik ik-alert-circle text-lg"></i>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-rose-800">
                Some questions cannot be imported
            </h3>

            <p class="mt-1 text-sm text-rose-700">
                {{ count($errors) }}
                {{ Str::plural('row', count($errors)) }}
                contains validation errors. These rows will be skipped.
            </p>
        </div>

    </div>

@elseif(count($warningRows) > 0)

    <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-5">

        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
            <i class="ik ik-alert-triangle text-lg"></i>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-amber-800">
                Review recommended
            </h3>

            <p class="mt-1 text-sm text-amber-700">
                Some questions have warnings such as missing translations
                or duplicate option text. They can still be imported.
            </p>
        </div>

    </div>

@else

    <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-5">

        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
            <i class="ik ik-check-circle text-lg"></i>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-emerald-800">
                Everything looks good
            </h3>

            <p class="mt-1 text-sm text-emerald-700">
                All questions passed the validation checks.
            </p>
        </div>

    </div>

@endif


{{-- ============================================================
    ERRORS
============================================================= --}}
@if(count($errors))

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="border-b border-gray-100 px-6 py-4">

            <div class="flex items-center gap-3">

                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                    <i class="ik ik-x-circle"></i>
                </div>

                <div>
                    <h2 class="font-semibold text-gray-800">
                        Import Errors
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        These rows will not be imported.
                    </p>
                </div>

            </div>

        </div>


        <div class="divide-y divide-gray-100">

            @foreach($errors as $error)

                <div class="px-6 py-5">

                    <div class="flex items-start gap-4">

                        <span class="inline-flex shrink-0 rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                            Row {{ $error['row'] }}
                        </span>

                        <div class="min-w-0 flex-1">

                            @if(!empty($error['data']['question_en']))

                                <p class="mb-2 text-sm font-medium text-gray-800">
                                    {{ $error['data']['question_en'] }}
                                </p>

                            @endif

                            <div class="space-y-1.5">

                                @foreach($error['errors'] as $message)

                                    <div class="flex items-start gap-2">

                                        <i class="ik ik-x mt-0.5 shrink-0 text-sm text-rose-500"></i>

                                        <p class="text-sm text-rose-700">
                                            {{ $message }}
                                        </p>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    </div>

@endif


{{-- ============================================================
    WARNINGS
============================================================= --}}
@if(count($warningRows))

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="border-b border-gray-100 px-6 py-4">

            <div class="flex items-center gap-3">

                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i class="ik ik-alert-triangle"></i>
                </div>

                <div>
                    <h2 class="font-semibold text-gray-800">
                        Review Warnings
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        These questions can be imported, but should be reviewed.
                    </p>
                </div>

            </div>

        </div>


        <div class="divide-y divide-gray-100">

            @foreach($warningRows as $warning)

                <div class="px-6 py-5">

                    <div class="flex items-start gap-4">

                        <span class="inline-flex shrink-0 rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                            Row {{ $warning['row'] }}
                        </span>

                        <div class="min-w-0 flex-1">

                            <p class="mb-3 text-sm font-medium text-gray-800">
                                {{ $warning['question'] ?: 'Question text missing' }}
                            </p>

                            <div class="space-y-2">

                                @foreach($warning['warnings'] as $message)

                                    <div class="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2">

                                        <i class="ik ik-alert-triangle mt-0.5 shrink-0 text-sm text-amber-500"></i>

                                        <p class="text-xs font-medium text-amber-800">
                                            {{ $message }}
                                        </p>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    </div>

@endif


{{-- ============================================================
    VALID QUESTIONS
============================================================= --}}
@if(count($validRows))

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

        <div class="border-b border-gray-100 px-6 py-4">

            <div class="flex items-center gap-3">

                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <i class="ik ik-check-circle"></i>
                </div>

                <div>
                    <h2 class="font-semibold text-gray-800">
                        Questions Ready to Import
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        {{ count($validRows) }}
                        {{ Str::plural('question', count($validRows)) }}
                        passed the required validation checks.
                    </p>
                </div>

            </div>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead class="border-b border-gray-100 bg-gray-50">

                    <tr>

                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">
                            Row
                        </th>

                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">
                            Question
                        </th>

                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">
                            Category
                        </th>

                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">
                            Difficulty
                        </th>

                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">
                            Answer
                        </th>

                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase text-gray-500">
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">

                    @foreach($validRows as $row)

                        @php
                            $rowHasWarning = false;

                            foreach ($warningRows as $warning) {
                                if ($warning['row'] === $row['row']) {
                                    $rowHasWarning = true;
                                    break;
                                }
                            }
                        @endphp

                        <tr class="transition-colors hover:bg-gray-50/70">

                            {{-- ROW --}}
                            <td class="px-5 py-4 text-sm text-gray-500">
                                {{ $row['row'] }}
                            </td>


                            {{-- QUESTION --}}
                            <td class="max-w-md px-5 py-4">

                                <p class="text-sm font-medium text-gray-800">
                                    {{ $row['question_text']['en'] ?: '—' }}
                                </p>

                                @if(!empty($row['question_text']['am']))

                                    <p
                                        dir="auto"
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        {{ $row['question_text']['am'] }}
                                    </p>

                                @else

                                    <p class="mt-1 text-xs font-medium text-amber-600">
                                        Amharic translation missing
                                    </p>

                                @endif

                            </td>


                            {{-- CATEGORY --}}
                            <td class="px-5 py-4 text-sm text-gray-600">
                                Category #{{ $row['category_id'] }}
                            </td>


                            {{-- DIFFICULTY --}}
                            <td class="px-5 py-4">

                                @php
                                    $difficultyStyles = [
                                        'easy' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'medium' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'hard' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                    ][$row['difficulty']]
                                    ?? 'bg-gray-50 text-gray-700 ring-gray-500/10';
                                @endphp

                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $difficultyStyles }}">
                                    {{ ucfirst($row['difficulty']) }}
                                </span>

                            </td>


                            {{-- ANSWER --}}
                            <td class="px-5 py-4 text-sm font-semibold uppercase text-gray-700">
                                {{ $row['correct_answer'] }}
                            </td>


                            {{-- STATUS --}}
                            <td class="px-5 py-4 text-center">

                                @if($rowHasWarning)

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">

                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>

                                        Warning

                                    </span>

                                @else

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">

                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                                        Ready

                                    </span>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

@endif


{{-- ============================================================
    ACTIONS
============================================================= --}}
<div class="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between">

    <a
        href="{{ route('admin.questions.import') }}"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-800"
    >
        <i class="ik ik-arrow-left"></i>
        Cancel & Upload Again
    </a>


    @if(count($validRows))

        <form
            method="POST"
            action="{{ route('admin.questions.import.store') }}"
        >

            @csrf

            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/30"
            >
                <i class="ik ik-check-circle"></i>

                Import {{ count($validRows) }}
                {{ Str::plural('Question', count($validRows)) }}

            </button>

        </form>

    @else

        <button
            type="button"
            disabled
            class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-500"
        >
            <i class="ik ik-x-circle"></i>
            No Questions Ready to Import
        </button>

    @endif

</div>


</div>

@endsection
