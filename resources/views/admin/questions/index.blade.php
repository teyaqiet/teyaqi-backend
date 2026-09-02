@extends('admin.layouts.main')

@section('title', 'Questions')

@section('content')

<div class="space-y-6">

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
        <div class="flex items-center justify-between rounded-xl bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
            <div class="flex items-center gap-2">
                <i class="ik ik-check-circle text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>

            <button
                onclick="this.parentElement.remove()"
                class="text-emerald-600 hover:text-emerald-900"
            >
                &times;
            </button>
        </div>
    @endif

    {{-- FLASH ERROR --}}
    @if(session('error'))
        <div class="flex items-center justify-between rounded-xl bg-rose-50 p-4 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-600/20">
            <div class="flex items-center gap-2">
                <i class="ik ik-alert-circle text-lg"></i>
                <span>{{ session('error') }}</span>
            </div>

            <button
                onclick="this.parentElement.remove()"
                class="text-rose-600 hover:text-rose-900"
            >
                &times;
            </button>
        </div>
    @endif


    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Questions
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Manage the Teyaqi question bank.
            </p>
        </div>


        <div class="flex flex-wrap items-center gap-3">

            <span class="text-sm font-medium text-gray-500">
                {{ number_format($questions->total()) }}
                {{ Str::plural('Question', $questions->total()) }}
            </span>


            {{-- IMPORT QUESTIONS --}}
            <a
                href="{{ route('admin.questions.import') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
                <i class="ik ik-upload text-base"></i>
                Import
            </a>


            {{-- EXPORT QUESTIONS --}}
            <a
                href="{{ route('admin.questions.export', request()->only(['category', 'difficulty', 'status'])) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-50"
            >
                <i class="ik ik-download text-base"></i>
                Export
            </a>


            {{-- ADD QUESTION --}}
            <a
                href="{{ route('admin.questions.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
                <i class="ik ik-plus text-base"></i>
                Add Question
            </a>

        </div>
    </div>


    {{-- FILTERS --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

        <form
            method="GET"
            action="{{ route('admin.questions.index') }}"
        >

            <div class="flex flex-wrap items-end gap-3">

                {{-- SEARCH --}}
                <div class="min-w-[260px] flex-1">

                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search question text..."
                        class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >

                </div>


                {{-- CATEGORY --}}
                <div class="w-full sm:w-44">

                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        Category
                    </label>

                    <select
                        name="category"
                        class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >

                        <option value="">
                            All Categories
                        </option>

                        @foreach($categories as $category)

                            <option
                                value="{{ $category->id }}"
                                @selected(request('category') == $category->id)
                            >
                                {{ $category->name['en'] ?? '-' }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- DIFFICULTY --}}
                <div class="w-full sm:w-36">

                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        Difficulty
                    </label>

                    <select
                        name="difficulty"
                        class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >

                        <option value="">
                            All Levels
                        </option>

                        @foreach(['easy', 'medium', 'hard'] as $level)

                            <option
                                value="{{ $level }}"
                                @selected(request('difficulty') === $level)
                            >
                                {{ ucfirst($level) }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- STATUS --}}
                <div class="w-full sm:w-36">

                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        Status
                    </label>

                    <select
                        name="status"
                        class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        <option
                            value="active"
                            @selected(request('status') === 'active')
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            @selected(request('status') === 'inactive')
                        >
                            Disabled
                        </option>

                    </select>

                </div>


                {{-- ACTION BUTTONS --}}
                <div class="flex w-full items-center gap-2 sm:w-auto">

                    <button
                        type="submit"
                        class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('admin.questions.index') }}"
                        class="flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm text-gray-600 shadow-sm transition-colors hover:bg-gray-50"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- BULK ACTIONS FORM --}}
    <form
        id="bulk-form"
        method="POST"
        action="{{ route('admin.questions.bulk-delete') }}"
    >

        @csrf


        {{-- QUESTIONS TABLE CONTAINER --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">


            {{-- BULK ACTIONS TOOLBAR --}}
            <div
                id="bulk-toolbar"
                class="hidden border-b border-gray-100 bg-gray-50 px-5 py-3 transition-all"
            >

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <span class="text-xs font-semibold text-gray-700">

                        <span id="selected-count">
                            0
                        </span>

                        question(s) selected

                    </span>


                    <div class="flex items-center gap-2">


                        {{-- EXPORT SELECTED --}}
                        <button
                            type="button"
                            onclick="exportSelectedQuestions()"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
                        >
                            <i class="ik ik-download text-sm"></i>
                            Export Selected
                        </button>


                        {{-- DELETE SELECTED --}}
                        <button
                            type="button"
                            onclick="confirmBulkDelete()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-rose-700"
                        >
                            <i class="ik ik-trash-2 text-sm"></i>
                            Delete Selected
                        </button>

                    </div>

                </div>

            </div>


            {{-- TABLE --}}
            <div class="overflow-x-auto">

                <table class="w-full table-fixed text-left">

                    <thead class="border-b border-gray-100 bg-gray-50/50">

                        <tr>

                            {{-- SELECT ALL --}}
                            <th class="w-[4%] px-4 py-3.5 text-center">

                                <input
                                    type="checkbox"
                                    id="select-all"
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500/20"
                                >

                            </th>


                            {{-- QUESTION --}}
                            <th class="w-[36%] px-4 py-3.5 text-xs font-semibold uppercase text-gray-500">

                                <a
                                    href="{{ request()->fullUrlWithQuery([
                                        'sort' => 'created_at',
                                        'direction' => request('direction') === 'asc' ? 'desc' : 'asc'
                                    ]) }}"
                                    class="inline-flex items-center gap-1.5 hover:text-gray-700"
                                >
                                    Question
                                    <i class="ik ik-arrow-up-down text-gray-400"></i>
                                </a>

                            </th>


                            {{-- CATEGORY --}}
                            <th class="w-[14%] px-4 py-3.5 text-xs font-semibold uppercase text-gray-500">

                                <a
                                    href="{{ request()->fullUrlWithQuery([
                                        'sort' => 'category_id',
                                        'direction' => request('direction') === 'asc' ? 'desc' : 'asc'
                                    ]) }}"
                                    class="inline-flex items-center gap-1.5 hover:text-gray-700"
                                >
                                    Category
                                    <i class="ik ik-arrow-up-down text-gray-400"></i>
                                </a>

                            </th>


                            {{-- DIFFICULTY --}}
                            <th class="w-[12%] px-4 py-3.5 text-xs font-semibold uppercase text-gray-500">

                                <a
                                    href="{{ request()->fullUrlWithQuery([
                                        'sort' => 'difficulty',
                                        'direction' => request('direction') === 'asc' ? 'desc' : 'asc'
                                    ]) }}"
                                    class="inline-flex items-center gap-1.5 hover:text-gray-700"
                                >
                                    Difficulty
                                    <i class="ik ik-arrow-up-down text-gray-400"></i>
                                </a>

                            </th>


                            {{-- SCORE --}}
                            <th class="w-[8%] px-4 py-3.5 text-xs font-semibold uppercase text-gray-500">

                                <a
                                    href="{{ request()->fullUrlWithQuery([
                                        'sort' => 'difficulty_score',
                                        'direction' => request('direction') === 'asc' ? 'desc' : 'asc'
                                    ]) }}"
                                    class="inline-flex items-center gap-1.5 hover:text-gray-700"
                                >
                                    Score
                                    <i class="ik ik-arrow-up-down text-gray-400"></i>
                                </a>

                            </th>


                            {{-- ACCURACY --}}
                            <th class="w-[8%] px-4 py-3.5 text-xs font-semibold uppercase text-gray-500">
                                Accuracy
                            </th>


                            {{-- STATUS --}}
                            <th class="w-[10%] px-4 py-3.5 text-xs font-semibold uppercase text-gray-500">
                                Status
                            </th>


                            {{-- ACTIONS --}}
                            <th class="w-[8%] px-4 py-3.5 text-right text-xs font-semibold uppercase text-gray-500">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        @forelse($questions as $question)

                            @php

                                $shown = max(
                                    $question->times_shown ?? 0,
                                    1
                                );

                                $accuracy = round(
                                    (($question->times_correct ?? 0) / $shown) * 100
                                );

                            @endphp


                            <tr
                                class="group cursor-pointer transition-colors hover:bg-gray-50/80"
                                onclick="window.location='{{ route('admin.questions.show', $question) }}'"
                            >


                                {{-- CHECKBOX --}}
                                <td
                                    class="px-4 py-4 text-center"
                                    onclick="event.stopPropagation()"
                                >

                                    <input
                                        type="checkbox"
                                        name="questions[]"
                                        value="{{ $question->id }}"
                                        class="question-checkbox h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500/20"
                                    >

                                </td>


                                {{-- QUESTION --}}
                                <td class="px-4 py-4">

                                    <div class="space-y-1">

                                        <p class="font-medium text-gray-800 line-clamp-2">

                                            {{ Str::limit(
                                                $question->getTranslation('question_text', 'en'),
                                                90
                                            ) }}

                                        </p>


                                        @if($amharic = $question->getTranslation('question_text', 'am'))

                                            <p
                                                dir="auto"
                                                class="text-xs text-gray-500 line-clamp-1"
                                            >
                                                {{ Str::limit($amharic, 90) }}
                                            </p>

                                        @endif

                                    </div>

                                </td>


                                {{-- CATEGORY --}}
                                <td class="px-4 py-4 text-sm text-gray-600 truncate">

                                    {{ $question->category?->name['en'] ?? '-' }}

                                </td>


                                {{-- DIFFICULTY --}}
                                <td class="px-4 py-4 text-sm">

                                    @php

                                        $difficultyStyles = [

                                            'easy' =>
                                                'bg-emerald-50 text-emerald-700 ring-emerald-600/20',

                                            'medium' =>
                                                'bg-amber-50 text-amber-700 ring-amber-600/20',

                                            'hard' =>
                                                'bg-rose-50 text-rose-700 ring-rose-600/20',

                                        ][$question->difficulty]
                                        ?? 'bg-gray-50 text-gray-600 ring-gray-500/10';

                                    @endphp


                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $difficultyStyles }}"
                                    >
                                        {{ ucfirst($question->difficulty) }}
                                    </span>

                                </td>


                                {{-- SCORE --}}
                                <td class="px-4 py-4 text-sm font-medium text-gray-700">

                                    {{ number_format(
                                        $question->difficulty_score ?? 0,
                                        1
                                    ) }}

                                </td>


                                {{-- ACCURACY --}}
                                <td class="px-4 py-4 text-sm text-gray-600">

                                    <span class="font-medium text-gray-800">
                                        {{ $accuracy }}%
                                    </span>

                                </td>


                                {{-- STATUS --}}
                                <td class="px-4 py-4">

                                    @if($question->is_active)

                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">

                                            <span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>

                                            Active

                                        </span>

                                    @else

                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">

                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>

                                            Disabled

                                        </span>

                                    @endif

                                </td>


                                {{-- ACTIONS --}}
                                <td
                                    class="px-4 py-4 text-right"
                                    onclick="event.stopPropagation()"
                                >

                                    <div class="inline-flex items-center justify-end gap-1">

                                        <a
                                            href="{{ route('admin.questions.edit', $question) }}"
                                            class="rounded p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                            title="Edit"
                                        >
                                            <i class="ik ik-edit-2 text-base"></i>
                                        </a>


                                        <button
                                            type="button"
                                            onclick="deleteSingleQuestion({{ $question->id }})"
                                            class="rounded p-1 text-gray-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                                            title="Delete"
                                        >
                                            <i class="ik ik-trash-2 text-base"></i>
                                        </button>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="py-12 text-center"
                                >

                                    <div class="mx-auto flex max-w-xs flex-col items-center justify-center text-center">

                                        <i class="ik ik-help-circle text-4xl text-gray-300"></i>

                                        <p class="mt-2 text-sm font-medium text-gray-800">
                                            No questions found
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            Try adjusting your filters or search terms.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- PAGINATION --}}
            <div class="border-t border-gray-100 p-4">

                {{ $questions->withQueryString()->links() }}

            </div>

        </div>

    </form>


    {{-- SINGLE DELETE FORM --}}
    <form
        id="single-delete-form"
        method="POST"
        class="hidden"
    >

        @csrf
        @method('DELETE')

    </form>


    {{-- CONFIRMATION MODAL --}}
    @include('admin.components.confirm-modal')

</div>


<script>

    /*
     * ---------------------------------------------------------
     * CONFIRM STORE
     * ---------------------------------------------------------
     */

    function registerConfirmStore() {

        if (!window.Alpine) {
            return;
        }

        if (
            !Alpine.store('confirm') ||
            typeof Alpine.store('confirm').ask !== 'function'
        ) {

            Alpine.store('confirm', {

                show: false,

                title: '',

                message: '',

                icon: 'ik ik-trash-2',

                tone: 'danger',

                cancelText: 'Cancel',

                confirmText: 'Delete',

                onConfirm: null,


                ask(options) {

                    this.title =
                        options.title || 'Are you sure?';

                    this.message =
                        options.message ||
                        'This action cannot be undone.';

                    this.icon =
                        options.icon ||
                        'ik ik-trash-2';

                    this.tone =
                        options.tone ||
                        'danger';

                    this.cancelText =
                        options.cancelText ||
                        'Cancel';

                    this.confirmText =
                        options.confirmText ||
                        'Delete';

                    this.onConfirm =
                        options.onConfirm ||
                        null;

                    this.show = true;
                },


                confirm() {

                    if (
                        typeof this.onConfirm === 'function'
                    ) {
                        this.onConfirm();
                    }

                    this.show = false;
                },


                cancel() {

                    this.show = false;
                }

            });

        }

    }


    if (window.Alpine) {

        registerConfirmStore();

    } else {

        document.addEventListener(
            'alpine:init',
            registerConfirmStore
        );

    }


    /*
     * ---------------------------------------------------------
     * CHECKBOX / BULK TOOLBAR
     * ---------------------------------------------------------
     */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const selectAllCheckbox =
                document.getElementById('select-all');

            const questionCheckboxes =
                document.querySelectorAll(
                    '.question-checkbox'
                );

            const bulkToolbar =
                document.getElementById(
                    'bulk-toolbar'
                );

            const selectedCountSpan =
                document.getElementById(
                    'selected-count'
                );


            function updateToolbar() {

                const checkedCount =
                    document.querySelectorAll(
                        '.question-checkbox:checked'
                    ).length;


                if (selectedCountSpan) {

                    selectedCountSpan.textContent =
                        checkedCount;

                }


                if (bulkToolbar) {

                    if (checkedCount > 0) {

                        bulkToolbar.classList.remove(
                            'hidden'
                        );

                    } else {

                        bulkToolbar.classList.add(
                            'hidden'
                        );

                    }

                }


                if (selectAllCheckbox) {

                    selectAllCheckbox.checked =
                        checkedCount > 0 &&
                        checkedCount ===
                        questionCheckboxes.length;

                    selectAllCheckbox.indeterminate =
                        checkedCount > 0 &&
                        checkedCount <
                        questionCheckboxes.length;

                }

            }


            if (selectAllCheckbox) {

                selectAllCheckbox.addEventListener(
                    'change',
                    function () {

                        questionCheckboxes.forEach(
                            checkbox => {

                                checkbox.checked =
                                    selectAllCheckbox.checked;

                            }
                        );

                        updateToolbar();

                    }
                );

            }


            questionCheckboxes.forEach(
                checkbox => {

                    checkbox.addEventListener(
                        'change',
                        updateToolbar
                    );

                }
            );

        }
    );


    /*
     * ---------------------------------------------------------
     * EXPORT SELECTED QUESTIONS
     * ---------------------------------------------------------
     */

    function exportSelectedQuestions() {

        const selected =
            Array.from(
                document.querySelectorAll(
                    '.question-checkbox:checked'
                )
            );


        if (selected.length === 0) {
            return;
        }


        /*
         * Create a temporary POST form.
         *
         * We use POST because the selected question IDs
         * are submitted in the request body.
         */

        const form =
            document.createElement('form');


        form.method = 'POST';

        form.action =
            "{{ route('admin.questions.export.selected') }}";


        form.style.display = 'none';


        /*
         * CSRF token.
         */

        const csrf =
            document.createElement('input');

        csrf.type = 'hidden';

        csrf.name = '_token';

        csrf.value =
            "{{ csrf_token() }}";

        form.appendChild(csrf);


        /*
         * Add selected question IDs.
         */

        selected.forEach(
            checkbox => {

                const input =
                    document.createElement('input');

                input.type = 'hidden';

                input.name = 'questions[]';

                input.value =
                    checkbox.value;

                form.appendChild(input);

            }
        );


        /*
         * Submit the export request.
         */

        document.body.appendChild(form);

        form.submit();


        /*
         * Remove temporary form after submission.
         */

        setTimeout(
            () => form.remove(),
            1000
        );

    }


    /*
     * ---------------------------------------------------------
     * BULK DELETE
     * ---------------------------------------------------------
     */

    function confirmBulkDelete() {

        const count =
            document.querySelectorAll(
                '.question-checkbox:checked'
            ).length;


        if (count === 0) {
            return;
        }


        if (
            window.Alpine &&
            Alpine.store('confirm')
        ) {

            Alpine.store('confirm').ask({

                title:
                    'Delete Selected Questions?',

                message:
                    `Are you sure you want to permanently delete ${count} question(s)? This action cannot be undone.`,

                icon:
                    'ik ik-trash-2',

                tone:
                    'danger',

                confirmText:
                    'Yes, Delete All',

                cancelText:
                    'Cancel',

                onConfirm: () => {

                    document
                        .getElementById('bulk-form')
                        .submit();

                }

            });

        }

    }


    /*
     * ---------------------------------------------------------
     * SINGLE DELETE
     * ---------------------------------------------------------
     */

    function deleteSingleQuestion(id) {

        if (
            window.Alpine &&
            Alpine.store('confirm')
        ) {

            Alpine.store('confirm').ask({

                title:
                    'Delete Question?',

                message:
                    'Are you sure you want to delete this question? This action cannot be undone.',

                icon:
                    'ik ik-trash-2',

                tone:
                    'danger',

                confirmText:
                    'Delete',

                cancelText:
                    'Cancel',

                onConfirm: () => {

                    const form =
                        document.getElementById(
                            'single-delete-form'
                        );


                    form.action =
                        `/admin/questions/${id}`;


                    form.submit();

                }

            });

        }

    }

</script>

@endsection