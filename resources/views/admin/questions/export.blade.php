@extends('admin.layouts.main')

@section('title', 'Export Questions')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex items-center gap-4">

        <a
            href="{{ route('admin.questions.index') }}"
            class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
        >
            <i class="ik ik-arrow-left"></i>
        </a>

        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Export Questions
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Export questions from the Teyaqi question bank to Excel.
            </p>
        </div>

    </div>


    {{-- EXPORT CARD --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

        <div class="mb-6">

            <h2 class="font-semibold text-gray-800">
                Export Filters
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Choose which questions you want to export.
            </p>

        </div>


        <form
            method="POST"
            action="{{ route('admin.questions.export.download') }}"
        >

            @csrf

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">

                {{-- CATEGORY --}}
                <div>

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

                            <option value="{{ $category->id }}">
                                {{ $category->name['en'] ?? '-' }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- DIFFICULTY --}}
                <div>

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

                        <option value="easy">
                            Easy
                        </option>

                        <option value="medium">
                            Medium
                        </option>

                        <option value="hard">
                            Hard
                        </option>

                    </select>

                </div>


                {{-- STATUS --}}
                <div>

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

                        <option value="active">
                            Active
                        </option>

                        <option value="inactive">
                            Disabled
                        </option>

                    </select>

                </div>

            </div>


            {{-- ACTIONS --}}
            <div class="mt-6 flex items-center justify-end gap-3">

                <a
                    href="{{ route('admin.questions.index') }}"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700"
                >
                    <i class="ik ik-download"></i>
                    Export Questions
                </button>

            </div>

        </form>

    </div>


    {{-- INFORMATION --}}
    <div class="rounded-xl bg-blue-50 p-5 ring-1 ring-inset ring-blue-600/20">

        <div class="flex gap-3">

            <i class="ik ik-info text-lg text-blue-600"></i>

            <div>

                <h3 class="text-sm font-semibold text-blue-900">
                    Export format
                </h3>

                <p class="mt-1 text-xs leading-5 text-blue-700">
                    The exported file uses the same structure as the question
                    import template. Difficulty scores are not exported because
                    they are automatically calculated from the difficulty level.
                </p>

            </div>

        </div>

    </div>

</div>

@endsection