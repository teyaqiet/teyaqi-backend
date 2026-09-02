@extends('admin.layouts.main')

@section('title', 'Import Questions')

@section('content')

<div class="space-y-6">


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

        <div class="flex items-center gap-4">

            <a
                href="{{ route('admin.questions.index') }}"
                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
            >
                <i class="ik ik-arrow-left"></i>
            </a>

            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    Import Questions
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Import multiple questions using Excel or CSV.
                </p>
            </div>

        </div>

    </div>


    {{-- IMPORT CARD --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

        <div class="mb-6">

            <h2 class="font-semibold text-gray-800">
                Upload Question File
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Upload an Excel (.xlsx) or CSV (.csv) file containing your questions.
            </p>

        </div>


        {{-- UPLOAD FORM --}}
        <form
            method="POST"
            action="{{ route('admin.questions.import.preview') }}"
            enctype="multipart/form-data"
        >

            @csrf

            {{-- DROPZONE --}}
            <label
                for="question-file"
                class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 px-6 py-12 text-center transition-colors hover:bg-gray-50"
            >

                <i class="ik ik-upload text-4xl text-gray-400"></i>

                <p class="mt-4 text-sm font-semibold text-gray-700">
                    Click to upload your file
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    Excel (.xlsx) or CSV (.csv)
                </p>

                <input
                    id="question-file"
                    type="file"
                    name="file"
                    accept=".xlsx,.csv"
                    class="hidden"
                    required
                >

            </label>


            {{-- FILE NAME --}}
            <div
                id="selected-file"
                class="mt-4 hidden rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600"
            >
            </div>


            {{-- ACTIONS --}}
            <div class="mt-6 flex items-center justify-end gap-3">

                <a
                    href="{{ route('admin.questions.index') }}"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700"
                >
                    <i class="ik ik-eye"></i>
                    Preview Questions
                </button>

            </div>

        </form>

    </div>



{{-- TEMPLATE CARD --}}
<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <h2 class="font-semibold text-gray-800">
                Need a template?
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Download the Teyaqi question import template and fill it with your questions.
            </p>

        </div>

        <a
            href="{{ route('admin.questions.import.template') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-900"
        >
            <i class="ik ik-download"></i>
            Download Template
        </a>

    </div>

</div>



</div>


<script>

    const fileInput = document.getElementById('question-file');
    const selectedFile = document.getElementById('selected-file');

    fileInput.addEventListener('change', function () {

        if (!this.files.length) {
            selectedFile.classList.add('hidden');
            return;
        }

        const file = this.files[0];

        selectedFile.textContent = `Selected file: ${file.name}`;
        selectedFile.classList.remove('hidden');

    });

</script>

@endsection